<?php

namespace App\Http\Controllers;

use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use Illuminate\View\View;

class EmployeeDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $employee = $user?->employee;

        if ($employee) {
            $employee->loadMissing(['lineManager', 'pmsRole']);
        }

        $cycle = PmsCycle::ensureCurrentYear();
        $plan = null;
        $nextAction = null;

        if ($employee) {
            $plan = PmsPlan::query()
                ->with(['role', 'cycle'])
                ->where('pms_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->first();

            if ($plan) {
                if ($plan->canEditTargets()) {
                    $nextAction = [
                        'label' => 'Set your KPIs',
                        'url' => route('pms.plans.targets', $plan),
                    ];
                } elseif ($plan->canApproveTargets()) {
                    $nextAction = [
                        'label' => 'View pending KPI approval',
                        'url' => route('pms.plans.show', $plan),
                    ];
                } elseif ($plan->canSelfAssess('H1')) {
                    $nextAction = [
                        'label' => 'Mid-year self assessment',
                        'url' => route('pms.plans.assess', [$plan, 'H1', 'self']),
                    ];
                } elseif ($plan->canManagerReview('H1') || $plan->canManagerReview('H2')) {
                    $nextAction = [
                        'label' => 'View pending approval',
                        'url' => route('pms.plans.show', $plan),
                    ];
                } elseif ($plan->canSelfAssess('H2')) {
                    $nextAction = [
                        'label' => 'End-year self assessment',
                        'url' => route('pms.plans.assess', [$plan, 'H2', 'self']),
                    ];
                } elseif ($plan->status === 'finalized' || $plan->hasScores()) {
                    $nextAction = [
                        'label' => 'View performance scores',
                        'url' => route('pms.plans.show', $plan),
                    ];
                } else {
                    $nextAction = [
                        'label' => 'View my performance plan',
                        'url' => route('pms.plans.show', $plan),
                    ];
                }
            }
        }

        return view('employees.dashboard', [
            'user' => $user,
            'employee' => $employee,
            'cycle' => $cycle,
            'plan' => $plan,
            'nextAction' => $nextAction,
        ]);
    }
}
