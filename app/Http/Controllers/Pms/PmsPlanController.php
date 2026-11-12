<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use App\Models\Pms\PmsReview;
use App\Models\Pms\PmsRole;
use App\Models\User;
use App\Services\PmsPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmsPlanController extends Controller
{
    public function __construct(private readonly PmsPlanService $plans) {}

    public function create(Request $request): View
    {
        $this->authorizePeopleManagers();

        $current = PmsCycle::ensureCurrentYear();
        $cycle = PmsCycle::query()->findOrFail($request->integer('cycle') ?: $current->id);
        abort_unless($cycle->isCurrentCalendarYear(), 403, 'Plans can only be created for the current year.');

        $assignedIds = PmsPlan::query()
            ->where('pms_cycle_id', $cycle->id)
            ->pluck('employee_id');

        $available = Employee::query()
            ->with('pmsRole')
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get();

        return view('pms.plans.create', [
            'cycle' => $cycle,
            'employees' => $available,
            'roles' => PmsRole::query()->orderBy('name')->get(),
            'focusEmployeeId' => $request->integer('employee') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePeopleManagers();

        $data = $request->validate([
            'pms_cycle_id' => ['required', 'exists:pms_cycles,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'pms_role_id' => ['required', 'exists:pms_roles,id'],
        ]);

        $cycle = PmsCycle::query()->findOrFail($data['pms_cycle_id']);
        abort_unless($cycle->isCurrentCalendarYear(), 403, 'Plans can only be created for the current year.');

        $exists = PmsPlan::query()
            ->where('pms_cycle_id', $data['pms_cycle_id'])
            ->where('employee_id', $data['employee_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['employee_id' => 'This employee already has a plan for the selected year.']);
        }

        $plan = $this->plans->createPlan(
            $cycle,
            Employee::query()->findOrFail($data['employee_id']),
            PmsRole::query()->findOrFail($data['pms_role_id']),
        );

        return redirect()
            ->route('pms.plans.targets', $plan)
            ->with('success', 'Plan created with default category weights. Populate KPIs next.');
    }

    public function show(PmsPlan $plan): View
    {
        $this->authorizePlanAccess($plan);
        $plan->load(['employee', 'role', 'cycle', 'items', 'reviews.scores']);

        return view('pms.plans.show', [
            'plan' => $plan,
            'isCurrentYear' => $plan->cycle->isCurrentCalendarYear(),
            'selfH1' => $plan->reviews->firstWhere(fn ($r) => $r->period === 'H1' && $r->stage === 'self'),
            'managerH1' => $plan->reviews->firstWhere(fn ($r) => $r->period === 'H1' && $r->stage === 'manager'),
            'selfH2' => $plan->reviews->firstWhere(fn ($r) => $r->period === 'H2' && $r->stage === 'self'),
            'managerH2' => $plan->reviews->firstWhere(fn ($r) => $r->period === 'H2' && $r->stage === 'manager'),
        ]);
    }

    public function targets(PmsPlan $plan): View
    {
        $this->authorizeTargetsAccess($plan);
        $this->authorizeCurrentYear($plan);
        $plan->load(['employee', 'role', 'cycle', 'items']);

        return view('pms.plans.targets', compact('plan'));
    }

    public function saveTargets(Request $request, PmsPlan $plan): RedirectResponse
    {
        $this->authorizeTargetsAccess($plan);
        $this->authorizeCurrentYear($plan);

        /** @var User $user */
        $user = auth()->user();
        $isReviewer = $user->canManagePms() || $this->isTeamManagerOf($plan);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.target' => ['nullable', 'string', 'max:255'],
            'intent' => ['nullable', 'in:draft,submit,approve'],
        ]);

        $intent = $data['intent'] ?? 'draft';

        if ($intent === 'approve') {
            abort_unless($isReviewer && ($plan->canEditTargets() || $plan->canApproveTargets()), 403);
        }

        if ($intent === 'submit') {
            abort_unless($plan->canEditTargets(), 403);
        }

        if ($intent === 'draft') {
            abort_unless($plan->canEditTargets() || ($isReviewer && $plan->canApproveTargets()), 403);
        }

        $this->plans->saveTargets($plan, $data['items'], $intent);

        $message = match ($intent) {
            'submit' => 'KPIs submitted for branch manager approval.',
            'approve' => 'KPIs approved. Mid-year self assessment is now open.',
            default => 'KPI draft saved.',
        };

        return redirect()
            ->route('pms.plans.show', $plan)
            ->with('success', $message);
    }

    public function decideTargets(Request $request, PmsPlan $plan): RedirectResponse
    {
        $this->authorizeManagerDecision($plan);
        $this->authorizeCurrentYear($plan);

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.weight' => ['required_with:items', 'numeric', 'min:0', 'max:100'],
            'items.*.target' => ['nullable', 'string', 'max:255'],
            'decision' => ['required', 'in:accept,reject'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->plans->decideTargets(
            $plan,
            $data['decision'],
            $data['decision_note'] ?? null,
            $data['items'] ?? null,
        );

        return redirect()
            ->route('pms.plans.show', $plan)
            ->with('success', $data['decision'] === 'accept'
                ? 'KPIs accepted. Mid-year self assessment is now open.'
                : 'KPIs rejected and sent back to the employee.');
    }

    public function assess(Request $request, PmsPlan $plan, string $period, string $stage): View
    {
        $this->authorizeAssessment($plan, $stage);
        $this->authorizeCurrentYear($plan);
        $period = strtoupper($period);
        abort_unless(in_array($period, ['H1', 'H2'], true), 404);
        abort_unless(in_array($stage, ['self', 'manager'], true), 404);

        $plan->load(['employee', 'role', 'cycle', 'items']);

        $review = PmsReview::query()->firstOrCreate(
            [
                'pms_plan_id' => $plan->id,
                'period' => $period,
                'stage' => $stage,
            ],
            ['status' => 'draft']
        )->load('scores');

        $selfReview = null;
        $selfScoreMap = collect();

        if ($stage === 'manager') {
            $selfReview = PmsReview::query()
                ->where('pms_plan_id', $plan->id)
                ->where('period', $period)
                ->where('stage', 'self')
                ->with('scores')
                ->first();

            $selfScoreMap = $selfReview?->scores->keyBy('pms_plan_item_id') ?? collect();

            // Prefill blank manager scores from the employee's submitted self assessment.
            if ($selfScoreMap->isNotEmpty()) {
                foreach ($plan->items as $item) {
                    $managerScore = $review->scores->firstWhere('pms_plan_item_id', $item->id);
                    $selfScore = $selfScoreMap->get($item->id);

                    if (! $selfScore) {
                        continue;
                    }

                    if (! $managerScore) {
                        $review->scores->push(new \App\Models\Pms\PmsReviewScore([
                            'pms_review_id' => $review->id,
                            'pms_plan_item_id' => $item->id,
                            'achievement' => $selfScore->achievement,
                            'comment' => $selfScore->comment,
                            'actual_result' => $selfScore->actual_result,
                            'weighted_score' => $selfScore->weighted_score ?? $selfScore->achievement,
                        ]));
                    } elseif ($managerScore->weighted_score === null && $managerScore->achievement === null) {
                        $managerScore->achievement = $selfScore->achievement;
                        $managerScore->comment = $managerScore->comment ?: $selfScore->comment;
                        $managerScore->weighted_score = $selfScore->weighted_score ?? $selfScore->achievement;
                    }
                }
            }
        }

        $scoreMap = $review->scores->keyBy('pms_plan_item_id');
        $activeCategory = $request->query('category');

        if ($activeCategory === null) {
            $activeCategory = $stage === 'manager'
                ? 'all'
                : ($this->firstIncompleteCategory($plan, $review)
                    ?? $plan->groupedItems()->keys()->first());
        }

        /** @var User $user */
        $user = auth()->user();
        $canDecide = $stage !== 'manager'
            || ($plan->canBmApprove($period) && ($user->isLineManager() || $user->isAdmin()))
            || ($plan->canHrAccept($period) && $user->canManagePms());
        $decisionVerb = $stage === 'manager'
            ? ($canDecide ? $plan->decisionVerbFor($user, $period) : 'View')
            : null;

        return view('pms.plans.assess', compact(
            'plan',
            'period',
            'stage',
            'review',
            'scoreMap',
            'activeCategory',
            'selfReview',
            'selfScoreMap',
            'decisionVerb',
            'canDecide',
        ));
    }

    public function saveAssessment(Request $request, PmsPlan $plan, string $period, string $stage): RedirectResponse
    {
        $this->authorizeAssessment($plan, $stage);
        $this->authorizeCurrentYear($plan);
        $period = strtoupper($period);

        $intent = $request->input('intent', 'draft');
        $isManager = $stage === 'manager';
        $submitting = in_array($intent, ['submit', 'accept', 'reject'], true);
        $decision = match ($intent) {
            'reject' => 'reject',
            'accept', 'submit' => 'accept',
            default => 'accept',
        };
        $requiresScores = $submitting && ! ($isManager && $decision === 'reject');

        /** @var User $user */
        $user = auth()->user();

        if ($isManager && $submitting) {
            if ($plan->canBmApprove($period)) {
                abort_unless($user->isLineManager() || $user->isAdmin(), 403, 'Only the branch manager can approve at this step.');
            } elseif ($plan->canHrAccept($period)) {
                abort_unless($user->canManagePms(), 403, 'Only HR can accept after branch manager approval.');
            } else {
                abort(403, 'This assessment is not awaiting a decision.');
            }
        }

        $data = $request->validate([
            'scores' => ['required', 'array'],
            // Points per KPI (0 .. item weight). Service enforces the per-item weight cap.
            'scores.*.achievement' => [$requiresScores ? 'required' : 'nullable', 'numeric', 'min:0'],
            'scores.*.comment' => ['nullable', 'string', 'max:1000'],
            'intent' => ['nullable', 'in:draft,close,submit,accept,reject'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($isManager && $submitting && $decision === 'reject' && blank($data['decision_note'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['decision_note' => 'Add a short note explaining the rejection.']);
        }

        $wasBmStep = $plan->canBmApprove($period);

        $review = $this->plans->saveReview(
            $plan,
            $period,
            $stage,
            $data['scores'],
            $data['decision_note'] ?? null,
            $submitting,
            $decision
        );

        if ($submitting) {
            $message = match (true) {
                $isManager && $decision === 'accept' && $wasBmStep && $period === 'H1' => 'Mid-year assessment approved by branch manager. Waiting for HR to Accept.',
                $isManager && $decision === 'accept' && $wasBmStep && $period === 'H2' => 'End-year assessment approved by branch manager. Waiting for HR to Accept.',
                $isManager && $decision === 'accept' && $period === 'H2' => 'End-year assessment accepted. Final scores are now on the dashboard.',
                $isManager && $decision === 'accept' => 'Mid-year assessment accepted. Score recorded.',
                $isManager && $decision === 'reject' => PmsPlan::periodLabel($period).' assessment rejected and sent back to the employee.',
                default => PmsPlan::assessmentLabel($period, $stage).' submitted for manager approval.',
            };

            return redirect()
                ->route('pms.plans.show', $plan)
                ->with('success', $message);
        }

        $plan->loadMissing('cycle');
        $user = auth()->user();
        $isOwn = $user?->employee_id && (int) $user->employee_id === (int) $plan->employee_id;
        $closeRoute = $isOwn
            ? route('pms.my', ['year' => $plan->cycle->year])
            : route('pms.plans.show', $plan);

        if ($intent === 'close') {
            return redirect()
                ->to($closeRoute)
                ->with('success', 'Assessment draft saved.');
        }

        $nextCategory = $this->firstIncompleteCategory($plan->fresh(['items']), $review->fresh('scores'));

        return redirect()
            ->route('pms.plans.assess', [
                'plan' => $plan,
                'period' => $period,
                'stage' => $stage,
                'category' => $nextCategory ?? 'all',
            ])
            ->with('success', $nextCategory
                ? 'Draft saved. Continue with '.$nextCategory.'.'
                : 'Draft saved. All categories have scores — review and decide when ready.');
    }

    private function firstIncompleteCategory(PmsPlan $plan, PmsReview $review): ?string
    {
        $scoreMap = $review->scores->keyBy('pms_plan_item_id');

        foreach ($plan->groupedItems() as $category => $items) {
            foreach ($items as $item) {
                $score = $scoreMap->get($item->id);
                if ($score === null || ($score->weighted_score === null && $score->achievement === null)) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function authorizePeopleManagers(): void
    {
        abort_unless(auth()->user()?->canManagePms(), 403);
    }

    private function authorizeCurrentYear(PmsPlan $plan): void
    {
        $plan->loadMissing('cycle');
        abort_unless($plan->cycle?->isCurrentCalendarYear(), 403, 'Past years are view-only reports.');
    }

    private function authorizePlanAccess(PmsPlan $plan, bool $ownerOrHr = false): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->canManagePms()) {
            return;
        }

        if ($user->employee_id && (int) $user->employee_id === (int) $plan->employee_id) {
            return;
        }

        if (! $ownerOrHr && $this->isTeamManagerOf($plan)) {
            return;
        }

        abort(403);
    }

    private function authorizeTargetsAccess(PmsPlan $plan): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->canManagePms() || $this->isTeamManagerOf($plan)) {
            return;
        }

        if ($user->employee_id && (int) $user->employee_id === (int) $plan->employee_id) {
            abort_unless($plan->canEditTargets() || $plan->canApproveTargets(), 403);

            return;
        }

        abort(403);
    }

    private function authorizeManagerDecision(PmsPlan $plan): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->canManagePms() || $this->isTeamManagerOf($plan)) {
            return;
        }

        abort(403);
    }

    private function isTeamManagerOf(PmsPlan $plan): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->isLineManager() || ! $user->employee_id) {
            return false;
        }

        $user->loadMissing('employee');

        return $user->employee
            ->directReports()
            ->pluck('id')
            ->contains($plan->employee_id);
    }

    private function authorizeAssessment(PmsPlan $plan, string $stage): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($stage === 'self') {
            abort_unless(
                $user->canManagePms() || ((int) $user->employee_id === (int) $plan->employee_id),
                403
            );

            return;
        }

        if ($user->canManagePms() || $this->isTeamManagerOf($plan)) {
            return;
        }

        abort(403);
    }
}
