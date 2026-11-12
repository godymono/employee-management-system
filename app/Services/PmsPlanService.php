<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use App\Models\Pms\PmsPlanItem;
use App\Models\Pms\PmsReview;
use App\Models\Pms\PmsReviewScore;
use App\Models\Pms\PmsRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PmsPlanService
{
    public function createPlan(PmsCycle $cycle, Employee $employee, PmsRole $role): PmsPlan
    {
        return DB::transaction(function () use ($cycle, $employee, $role) {
            $plan = PmsPlan::query()->create([
                'pms_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
                'pms_role_id' => $role->id,
                'status' => 'draft',
            ]);

            $sort = 1;
            $role->load('categories.items');

            foreach ($role->categories as $category) {
                foreach ($category->items as $item) {
                    PmsPlanItem::query()->create([
                        'pms_plan_id' => $plan->id,
                        'pms_kpi_item_id' => $item->id,
                        'category_name' => $category->name,
                        'category_weight' => $category->default_weight,
                        'kpi_name' => $item->name,
                        'description' => $item->description,
                        'weight' => $item->default_weight,
                        'target' => $item->default_target,
                        'sort_order' => $sort++,
                    ]);
                }
            }

            if (! $employee->pms_role_id) {
                $employee->update(['pms_role_id' => $role->id]);
            }

            return $plan->load(['items', 'employee', 'role', 'cycle']);
        });
    }

    /**
     * @param  array<int, array{weight: mixed, target: mixed}>  $items
     */
    public function saveTargets(PmsPlan $plan, array $items, string $intent = 'draft'): PmsPlan
    {
        $editable = $plan->canEditTargets() || $plan->canApproveTargets();
        if (! $editable) {
            throw ValidationException::withMessages([
                'plan' => 'Targets can no longer be edited for this plan.',
            ]);
        }

        $this->assertWeightsTotalOneHundred($items);

        if ($intent === 'submit' && ! $plan->canEditTargets() && ! $plan->canApproveTargets()) {
            throw ValidationException::withMessages([
                'plan' => 'KPIs cannot be submitted in the current status.',
            ]);
        }

        DB::transaction(function () use ($plan, $items, $intent) {
            $this->persistItemWeights($plan, $items);

            if ($intent === 'submit') {
                $plan->update([
                    'status' => 'targets_submitted',
                    'targets_locked_at' => null,
                    'decision_note' => null,
                ]);
            } elseif ($intent === 'approve') {
                $plan->update([
                    'status' => 'targets_set',
                    'targets_locked_at' => now(),
                    'decision_note' => null,
                ]);
            } else {
                // Keep awaiting approval if already submitted; otherwise stay draft.
                $plan->update([
                    'status' => $plan->canApproveTargets() ? 'targets_submitted' : 'draft',
                    'targets_locked_at' => null,
                ]);
            }
        });

        return $plan->fresh(['items', 'employee', 'role', 'cycle']);
    }

    /**
     * @param  array<int, array{weight: mixed, target: mixed}>|null  $items
     */
    public function decideTargets(PmsPlan $plan, string $decision, ?string $note = null, ?array $items = null): PmsPlan
    {
        if (! $plan->canApproveTargets()) {
            throw ValidationException::withMessages([
                'plan' => 'There are no KPIs awaiting approval.',
            ]);
        }

        if (! in_array($decision, ['accept', 'reject'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Choose Accept or Reject.',
            ]);
        }

        if ($decision === 'reject' && blank($note)) {
            throw ValidationException::withMessages([
                'decision_note' => 'Add a short note explaining the rejection.',
            ]);
        }

        if ($items !== null) {
            $this->assertWeightsTotalOneHundred($items);
        }

        DB::transaction(function () use ($plan, $decision, $note, $items) {
            if ($items !== null) {
                $this->persistItemWeights($plan, $items);
            }

            if ($decision === 'accept') {
                $plan->update([
                    'status' => 'targets_set',
                    'targets_locked_at' => now(),
                    'decision_note' => null,
                ]);
            } else {
                $plan->update([
                    'status' => 'draft',
                    'targets_locked_at' => null,
                    'decision_note' => $note,
                ]);
            }
        });

        return $plan->fresh(['items', 'employee', 'role', 'cycle']);
    }

    /**
     * @param  array<int, array{achievement: mixed, actual_result?: mixed, comment?: mixed}>  $scores
     */
    public function saveReview(
        PmsPlan $plan,
        string $period,
        string $stage,
        array $scores,
        ?string $overallComment,
        bool $submit = false,
        string $decision = 'accept'
    ): PmsReview {
        $period = strtoupper($period);

        if (! in_array($period, ['H1', 'H2'], true) || ! in_array($stage, ['self', 'manager'], true)) {
            throw ValidationException::withMessages(['review' => 'Invalid review period or stage.']);
        }

        if ($stage === 'self' && ! $plan->canSelfAssess($period)) {
            throw ValidationException::withMessages(['review' => 'Self-assessment is not open for this period.']);
        }

        if ($stage === 'manager' && ! $plan->canManagerReview($period)) {
            throw ValidationException::withMessages(['review' => 'Manager review is not open for this period.']);
        }

        if ($stage === 'manager' && $submit && ! in_array($decision, ['accept', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose Accept or Reject.']);
        }

        if ($stage === 'manager' && $submit && $decision === 'reject' && blank($overallComment)) {
            throw ValidationException::withMessages([
                'decision_note' => 'Add a short note explaining the rejection.',
            ]);
        }

        return DB::transaction(function () use ($plan, $period, $stage, $scores, $overallComment, $submit, $decision) {
            $review = PmsReview::query()->firstOrCreate(
                [
                    'pms_plan_id' => $plan->id,
                    'period' => $period,
                    'stage' => $stage,
                ],
                ['status' => 'draft']
            );

            if ($review->isLocked() && ! $submit) {
                throw ValidationException::withMessages(['review' => 'This review is already locked.']);
            }

            if ($review->isSubmitted() && $submit) {
                throw ValidationException::withMessages(['review' => 'This review is already submitted.']);
            }

            $total = 0;
            $plan->loadMissing('items');

            foreach ($plan->items as $item) {
                $row = $scores[$item->id] ?? [];
                // Form collects points for this KPI (0 .. item weight). Weights already total 100.
                $rawPoints = $row['achievement'] ?? $row['score'] ?? null;
                $hasPoints = $rawPoints !== null && $rawPoints !== '';
                $weight = (float) $item->weight;

                if ($hasPoints) {
                    $points = round((float) $rawPoints, 2);

                    if ($points < 0 || $points > $weight + 0.009) {
                        throw ValidationException::withMessages([
                            "scores.{$item->id}.achievement" => "{$item->kpi_name}: score must be between 0 and {$weight} (its weight).",
                        ]);
                    }

                    $points = max(0, min($weight, $points));
                    $achievementPct = $weight > 0 ? round(($points / $weight) * 100, 2) : 0.0;
                    $weighted = $points;
                    $total += $weighted;
                } else {
                    $achievementPct = null;
                    $weighted = null;
                }

                PmsReviewScore::query()->updateOrCreate(
                    [
                        'pms_review_id' => $review->id,
                        'pms_plan_item_id' => $item->id,
                    ],
                    [
                        'achievement' => $achievementPct,
                        'actual_result' => $row['actual_result'] ?? null,
                        'comment' => $row['comment'] ?? null,
                        'weighted_score' => $weighted,
                    ]
                );
            }

            if ($submit && $stage === 'manager' && $decision === 'reject') {
                $review->update([
                    'total_score' => round($total, 2),
                    'overall_comment' => $overallComment,
                    'status' => 'rejected',
                    'submitted_at' => now(),
                ]);

                $this->rejectAssessment($plan, $period, $overallComment);

                return $review->fresh('scores');
            }

            $reviewStatus = 'draft';
            $submittedAt = null;

            if ($submit) {
                $submittedAt = now();
                if ($stage === 'manager' && in_array($plan->status, ['h1_submitted', 'h2_submitted'], true)) {
                    // Branch manager approved — keep editable for HR acceptance.
                    $reviewStatus = 'bm_approved';
                } else {
                    $reviewStatus = 'submitted';
                }
            }

            $review->update([
                'total_score' => round($total, 2),
                'overall_comment' => $overallComment,
                'status' => $reviewStatus,
                'submitted_at' => $submittedAt,
            ]);

            if ($submit) {
                $this->advanceStatusAfterSubmit($plan, $period, $stage, round($total, 2));
            } elseif ($stage === 'self') {
                if ($period === 'H1' && $plan->status === 'targets_set') {
                    $plan->update(['status' => 'h1_self_draft', 'decision_note' => null]);
                }
                if ($period === 'H2' && $plan->status === 'h1_reviewed') {
                    $plan->update(['status' => 'h2_self_draft', 'decision_note' => null]);
                }
            }

            return $review->fresh('scores');
        });
    }

    private function rejectAssessment(PmsPlan $plan, string $period, ?string $note): void
    {
        $selfStatus = $period === 'H1' ? 'h1_self_draft' : 'h2_self_draft';

        $selfReview = PmsReview::query()
            ->where('pms_plan_id', $plan->id)
            ->where('period', $period)
            ->where('stage', 'self')
            ->first();

        if ($selfReview) {
            $selfReview->update([
                'status' => 'draft',
                'submitted_at' => null,
            ]);
        }

        $plan->update([
            'status' => $selfStatus,
            'decision_note' => $note,
            'h1_score' => $period === 'H1' ? null : $plan->h1_score,
            'h2_score' => $period === 'H2' ? null : $plan->h2_score,
            'annual_score' => null,
            'rating_band' => null,
            'finalized_at' => null,
        ]);
    }

    private function advanceStatusAfterSubmit(PmsPlan $plan, string $period, string $stage, float $score): void
    {
        if ($stage === 'self' && $period === 'H1') {
            $plan->update([
                'status' => 'h1_submitted',
                'decision_note' => null,
            ]);
        }

        if ($stage === 'manager' && $period === 'H1') {
            if ($plan->status === 'h1_submitted') {
                // Branch manager approval — HR acceptance still required.
                $plan->update([
                    'status' => 'h1_bm_approved',
                    'h1_score' => $score,
                    'decision_note' => null,
                ]);
            } else {
                // HR acceptance after BM approval.
                $plan->update([
                    'status' => 'h1_reviewed',
                    'h1_score' => $score,
                    'decision_note' => null,
                ]);
            }
        }

        if ($stage === 'self' && $period === 'H2') {
            $plan->update([
                'status' => 'h2_submitted',
                'decision_note' => null,
            ]);
        }

        if ($stage === 'manager' && $period === 'H2') {
            if ($plan->status === 'h2_submitted') {
                $plan->update([
                    'status' => 'h2_bm_approved',
                    'h2_score' => $score,
                    'decision_note' => null,
                ]);
            } else {
                $h1 = (float) ($plan->h1_score ?? 0);
                $annual = round(($h1 + $score) / 2, 2);

                $plan->update([
                    'status' => 'finalized',
                    'h2_score' => $score,
                    'annual_score' => $annual,
                    'rating_band' => PmsPlan::ratingBandFor($annual),
                    'finalized_at' => now(),
                    'decision_note' => null,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{weight: mixed, target: mixed}>  $items
     */
    private function assertWeightsTotalOneHundred(array $items): void
    {
        $total = collect($items)->sum(fn ($row) => (float) ($row['weight'] ?? 0));
        if (abs($total - 100) > 0.05) {
            throw ValidationException::withMessages([
                'weights' => 'Total KPI weight must equal 100%. Current total: '.number_format($total, 2).'%.',
            ]);
        }
    }

    /**
     * @param  array<int, array{weight: mixed, target: mixed}>  $items
     */
    private function persistItemWeights(PmsPlan $plan, array $items): void
    {
        foreach ($items as $itemId => $payload) {
            $plan->items()
                ->whereKey($itemId)
                ->update([
                    'weight' => (float) $payload['weight'],
                    'target' => $payload['target'] ?? null,
                ]);
        }

        $plan->load('items');
        foreach ($plan->groupedItems() as $categoryItems) {
            $categoryWeight = round((float) $categoryItems->sum('weight'), 2);
            $plan->items()
                ->whereIn('id', $categoryItems->pluck('id'))
                ->update(['category_weight' => $categoryWeight]);
        }
    }
}
