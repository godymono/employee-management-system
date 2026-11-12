<?php

namespace App\Models\Pms;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsPlan extends Model
{
    public const STATUSES = [
        'draft' => 'Draft — set KPIs',
        'targets_submitted' => 'Pending manager approval (KPIs)',
        'targets_set' => 'KPIs approved',
        'h1_self_draft' => 'Mid-year self assessment in progress',
        'h1_submitted' => 'Pending BM approval (mid-year)',
        'h1_bm_approved' => 'Pending HR acceptance (mid-year)',
        'h1_reviewed' => 'Mid-year accepted',
        'h2_self_draft' => 'End-year self assessment in progress',
        'h2_submitted' => 'Pending BM approval (end-year)',
        'h2_bm_approved' => 'Pending HR acceptance (end-year)',
        'h2_reviewed' => 'End-year accepted',
        'finalized' => 'Finalized',
    ];

    protected $fillable = [
        'pms_cycle_id',
        'employee_id',
        'pms_role_id',
        'status',
        'targets_locked_at',
        'finalized_at',
        'h1_score',
        'h2_score',
        'annual_score',
        'rating_band',
        'final_comment',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'targets_locked_at' => 'datetime',
            'finalized_at' => 'datetime',
            'h1_score' => 'decimal:2',
            'h2_score' => 'decimal:2',
            'annual_score' => 'decimal:2',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PmsCycle::class, 'pms_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PmsRole::class, 'pms_role_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PmsPlanItem::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PmsReview::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public static function periodLabel(string $period): string
    {
        return match (strtoupper($period)) {
            'H1' => 'Mid-year',
            'H2' => 'End-year',
            default => $period,
        };
    }

    public static function assessmentLabel(string $period, string $stage, ?string $decisionVerb = null): string
    {
        $periodLabel = self::periodLabel($period);

        if ($stage !== 'manager') {
            return $periodLabel.' self assessment';
        }

        $verb = $decisionVerb ?? 'Approve';

        return $verb.' '.$periodLabel.' assessment';
    }

    public function decisionVerbFor(?\App\Models\User $user, string $period): string
    {
        if ($user?->canManagePms() && $this->canHrAccept($period)) {
            return 'Accept';
        }

        return 'Approve';
    }

    public function canEditTargets(): bool
    {
        return $this->status === 'draft';
    }

    public function canApproveTargets(): bool
    {
        return $this->status === 'targets_submitted';
    }

    public function canSelfAssess(string $period): bool
    {
        if ($period === 'H1') {
            return in_array($this->status, ['targets_set', 'h1_self_draft'], true);
        }

        return in_array($this->status, ['h1_reviewed', 'h2_self_draft'], true);
    }

    public function canBmApprove(string $period): bool
    {
        if ($period === 'H1') {
            return $this->status === 'h1_submitted';
        }

        return $this->status === 'h2_submitted';
    }

    public function canHrAccept(string $period): bool
    {
        if ($period === 'H1') {
            return $this->status === 'h1_bm_approved';
        }

        return $this->status === 'h2_bm_approved';
    }

    public function canManagerReview(string $period): bool
    {
        return $this->canBmApprove($period) || $this->canHrAccept($period);
    }

    public function isAwaitingManagerApproval(): bool
    {
        return in_array($this->status, [
            'targets_submitted',
            'h1_submitted',
            'h1_bm_approved',
            'h2_submitted',
            'h2_bm_approved',
        ], true);
    }

    public function awaitingPeriod(): ?string
    {
        return match ($this->status) {
            'targets_submitted' => 'targets',
            'h1_submitted', 'h1_bm_approved' => 'H1',
            'h2_submitted', 'h2_bm_approved' => 'H2',
            default => null,
        };
    }

    public function hasScores(): bool
    {
        return $this->h1_score !== null || $this->h2_score !== null || $this->annual_score !== null;
    }

    /**
     * Clear next-step copy for the logged-in role.
     *
     * @return array{tone: string, title: string, detail: string, action_label: ?string, action_url: ?string}
     */
    public function flowFor(?\App\Models\User $user): array
    {
        $this->loadMissing('employee');

        $isOwner = $user && $user->employee_id && (int) $user->employee_id === (int) $this->employee_id;
        $isReviewer = $user && ($user->canManagePms() || $user->isLineManager());

        if ($this->canEditTargets()) {
            $rejection = $this->decision_note
                ? ' Manager note: '.$this->decision_note
                : '';

            return $isOwner || $user?->canManagePms() || ($isReviewer && ! $isOwner)
                ? [
                    'tone' => 'action',
                    'title' => 'Set KPIs from templates',
                    'detail' => 'Default category weights are pre-filled. Populate targets, adjust weights if needed, then submit for branch manager approval.'.$rejection,
                    'action_label' => 'Set KPIs',
                    'action_url' => route('pms.plans.targets', $this),
                ]
                : [
                    'tone' => 'waiting',
                    'title' => 'Waiting on employee',
                    'detail' => $this->employee->name.' still needs to populate KPIs and submit for approval.',
                    'action_label' => null,
                    'action_url' => null,
                ];
        }

        if ($this->canApproveTargets()) {
            return $isReviewer
                ? [
                    'tone' => 'approval',
                    'title' => 'Pending your KPI approval',
                    'detail' => 'Review weights and targets. Accept to open mid-year assessment, or reject to send back.',
                    'action_label' => 'Review KPIs',
                    'action_url' => route('pms.plans.targets', $this),
                ]
                : [
                    'tone' => 'approval',
                    'title' => 'Pending manager approval',
                    'detail' => 'Your KPIs were submitted. Waiting for your branch manager to accept or reject.',
                    'action_label' => null,
                    'action_url' => null,
                ];
        }

        if ($this->canSelfAssess('H1')) {
            return $isOwner || $user?->canManagePms()
                ? [
                    'tone' => 'action',
                    'title' => 'Mid-year self assessment',
                    'detail' => 'Complete and submit your mid-year scores for manager approval.',
                    'action_label' => 'Continue self assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H1', 'self']),
                ]
                : [
                    'tone' => 'waiting',
                    'title' => 'Waiting on employee self assessment',
                    'detail' => $this->employee->name.' is working on mid-year self assessment.',
                    'action_label' => null,
                    'action_url' => null,
                ];
        }

        if ($this->canBmApprove('H1')) {
            if ($user?->isLineManager() || $user?->isAdmin()) {
                return [
                    'tone' => 'approval',
                    'title' => 'Approve mid-year assessment',
                    'detail' => 'Open the employee’s submitted self assessment, then Approve or Reject.',
                    'action_label' => 'Approve mid-year assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H1', 'manager']),
                ];
            }

            if ($user?->isHr()) {
                return [
                    'tone' => 'waiting',
                    'title' => 'Awaiting branch manager approval',
                    'detail' => 'You can Accept this mid-year assessment after the branch manager approves it.',
                    'action_label' => 'View submitted assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H1', 'manager']),
                ];
            }

            return [
                'tone' => 'approval',
                'title' => 'Pending branch manager approval',
                'detail' => 'Your mid-year self assessment was submitted. Waiting for your branch manager to approve.',
                'action_label' => null,
                'action_url' => null,
            ];
        }

        if ($this->canHrAccept('H1')) {
            if ($user?->canManagePms()) {
                return [
                    'tone' => 'approval',
                    'title' => 'Accept mid-year assessment',
                    'detail' => 'Branch manager approved. Review what the employee wrote, then Accept or Reject.',
                    'action_label' => 'Accept mid-year assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H1', 'manager']),
                ];
            }

            return [
                'tone' => 'approval',
                'title' => 'Pending HR acceptance',
                'detail' => 'Branch manager approved your mid-year assessment. Waiting for HR to Accept.',
                'action_label' => null,
                'action_url' => null,
            ];
        }

        if ($this->canSelfAssess('H2')) {
            return $isOwner || $user?->canManagePms()
                ? [
                    'tone' => 'action',
                    'title' => 'End-year self assessment',
                    'detail' => 'Complete and submit your end-year scores for manager approval.',
                    'action_label' => 'Continue self assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H2', 'self']),
                ]
                : [
                    'tone' => 'waiting',
                    'title' => 'Waiting on employee self assessment',
                    'detail' => $this->employee->name.' is working on end-year self assessment.',
                    'action_label' => null,
                    'action_url' => null,
                ];
        }

        if ($this->canBmApprove('H2')) {
            if ($user?->isLineManager() || $user?->isAdmin()) {
                return [
                    'tone' => 'approval',
                    'title' => 'Approve end-year assessment',
                    'detail' => 'Open the employee’s submitted self assessment, then Approve or Reject.',
                    'action_label' => 'Approve end-year assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H2', 'manager']),
                ];
            }

            if ($user?->isHr()) {
                return [
                    'tone' => 'waiting',
                    'title' => 'Awaiting branch manager approval',
                    'detail' => 'You can Accept this end-year assessment after the branch manager approves it.',
                    'action_label' => 'View submitted assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H2', 'manager']),
                ];
            }

            return [
                'tone' => 'approval',
                'title' => 'Pending branch manager approval',
                'detail' => 'Your end-year self assessment was submitted. Waiting for your branch manager to approve.',
                'action_label' => null,
                'action_url' => null,
            ];
        }

        if ($this->canHrAccept('H2')) {
            if ($user?->canManagePms()) {
                return [
                    'tone' => 'approval',
                    'title' => 'Accept end-year assessment',
                    'detail' => 'Branch manager approved. Accept to finalize scores on the dashboard, or Reject.',
                    'action_label' => 'Accept end-year assessment',
                    'action_url' => route('pms.plans.assess', [$this, 'H2', 'manager']),
                ];
            }

            return [
                'tone' => 'approval',
                'title' => 'Pending HR acceptance',
                'detail' => 'Branch manager approved your end-year assessment. Waiting for HR to Accept.',
                'action_label' => null,
                'action_url' => null,
            ];
        }

        if ($this->status === 'finalized' || $this->status === 'h2_reviewed') {
            return [
                'tone' => 'done',
                'title' => 'Cycle complete',
                'detail' => 'Final scores are recorded on your dashboard'
                    .($this->annual_score !== null
                        ? ': '.number_format((float) $this->annual_score, 1).'%'.($this->rating_band ? ' · '.$this->rating_band : '')
                        : '.'),
                'action_label' => null,
                'action_url' => null,
            ];
        }

        return [
            'tone' => 'waiting',
            'title' => $this->statusLabel(),
            'detail' => 'No action required from you right now.',
            'action_label' => null,
            'action_url' => null,
        ];
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'targets_submitted', 'h1_submitted', 'h1_bm_approved', 'h2_submitted', 'h2_bm_approved' => 'approval',
            'finalized', 'h1_reviewed', 'h2_reviewed', 'targets_set' => 'done',
            'draft', 'h1_self_draft', 'h2_self_draft' => 'action',
            default => 'waiting',
        };
    }

    public function totalWeight(): float
    {
        return (float) $this->items->sum('weight');
    }

    public function groupedItems()
    {
        return $this->items->groupBy('category_name');
    }

    public static function ratingBandFor(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 90 => 'Outstanding',
            $score >= 80 => 'Exceeds expectations',
            $score >= 70 => 'Meets expectations',
            $score >= 50 => 'Needs improvement',
            default => 'Unsatisfactory',
        };
    }
}
