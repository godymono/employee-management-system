<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use App\Models\Pms\PmsRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmsDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isEmployee()) {
            return redirect()->route('pms.my');
        }

        $currentCycle = PmsCycle::ensureCurrentYear();
        $cycles = PmsCycle::query()->availableForReports()->get();

        $selectedYear = $request->integer('year') ?: $currentCycle->year;
        if ($selectedYear > (int) now()->year) {
            $selectedYear = $currentCycle->year;
        }
        $cycle = PmsCycle::findByYear($selectedYear) ?? $currentCycle;
        $isCurrentYear = $cycle->isCurrentCalendarYear();

        $plansQuery = PmsPlan::query()
            ->with(['employee', 'role'])
            ->where('pms_cycle_id', $cycle->id)
            ->latest();

        if ($user->isLineManager() && $user->employee_id) {
            $teamIds = $user->employee->directReports()->pluck('id')->push($user->employee_id);
            $plansQuery->whereIn('employee_id', $teamIds);
        }

        $plans = $plansQuery->get();

        $pendingApproval = $plans->whereIn('status', [
            'targets_submitted',
            'h1_submitted',
            'h1_bm_approved',
            'h2_submitted',
            'h2_bm_approved',
        ]);

        $stats = [
            'plans' => $plans->count(),
            'targets_set' => $plans->whereIn('status', [
                'targets_set',
                'h1_self_draft',
                'h1_submitted',
                'h1_bm_approved',
                'h1_reviewed',
                'h2_self_draft',
                'h2_submitted',
                'h2_bm_approved',
                'h2_reviewed',
                'finalized',
            ])->count(),
            'pending_approval' => $pendingApproval->count(),
            'finalized' => $plans->where('status', 'finalized')->count(),
            'avg_score' => round((float) $plans->whereNotNull('annual_score')->avg('annual_score'), 1),
        ];

        return view('pms.index', [
            'cycle' => $cycle,
            'currentCycle' => $currentCycle,
            'cycles' => $cycles,
            'isCurrentYear' => $isCurrentYear,
            'plans' => $plans,
            'roles' => PmsRole::query()->orderBy('name')->get(),
            'stats' => $stats,
            'user' => $user,
        ]);
    }
}
