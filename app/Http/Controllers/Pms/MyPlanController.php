<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPlanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = auth()->user();
        $employee = $user?->employee;

        if (! $employee) {
            return view('pms.my', [
                'employee' => null,
                'message' => 'Your account is not linked to an employee record yet. Ask HR to link your profile.',
                'cycle' => null,
                'cycles' => collect(),
                'currentCycle' => PmsCycle::ensureCurrentYear(),
                'isCurrentYear' => true,
                'plan' => null,
                'flow' => null,
            ]);
        }

        $currentCycle = PmsCycle::ensureCurrentYear();
        $cycles = PmsCycle::query()
            ->availableForReports()
            ->where(function ($query) use ($employee, $currentCycle) {
                $query->where('year', $currentCycle->year)
                    ->orWhereHas('plans', fn ($q) => $q->where('employee_id', $employee->id));
            })
            ->get();

        $selectedYear = $request->integer('year') ?: $currentCycle->year;
        if ($selectedYear > (int) now()->year) {
            $selectedYear = $currentCycle->year;
        }

        $cycle = PmsCycle::findByYear($selectedYear) ?? $currentCycle;
        $isCurrentYear = $cycle->isCurrentCalendarYear();

        $plan = PmsPlan::query()
            ->with(['employee', 'role', 'cycle', 'items', 'reviews'])
            ->where('pms_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();

        $message = null;
        $flow = null;

        if (! $plan) {
            $message = $isCurrentYear
                ? 'No performance plan has been assigned to you for '.$cycle->year.' yet. HR will create it.'
                : 'No performance report for '.$cycle->year.'.';
        } elseif ($isCurrentYear) {
            $flow = $plan->flowFor($user);
        }

        return view('pms.my', [
            'employee' => $employee,
            'message' => $message,
            'cycle' => $cycle,
            'cycles' => $cycles,
            'currentCycle' => $currentCycle,
            'isCurrentYear' => $isCurrentYear,
            'plan' => $plan,
            'flow' => $flow,
            'selfH1' => $plan?->reviews->firstWhere(fn ($r) => $r->period === 'H1' && $r->stage === 'self'),
            'managerH1' => $plan?->reviews->firstWhere(fn ($r) => $r->period === 'H1' && $r->stage === 'manager'),
            'selfH2' => $plan?->reviews->firstWhere(fn ($r) => $r->period === 'H2' && $r->stage === 'self'),
            'managerH2' => $plan?->reviews->firstWhere(fn ($r) => $r->period === 'H2' && $r->stage === 'manager'),
        ]);
    }
}
