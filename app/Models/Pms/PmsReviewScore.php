<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmsReviewScore extends Model
{
    protected $fillable = [
        'pms_review_id',
        'pms_plan_item_id',
        'achievement',
        'actual_result',
        'comment',
        'weighted_score',
    ];

    protected function casts(): array
    {
        return [
            'achievement' => 'decimal:2',
            'weighted_score' => 'decimal:2',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PmsReview::class, 'pms_review_id');
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(PmsPlanItem::class, 'pms_plan_item_id');
    }

    /**
     * Points contributed to the overall /100 score (same as weighted_score).
     */
    public function points(): ?float
    {
        if ($this->weighted_score !== null) {
            return (float) $this->weighted_score;
        }

        return null;
    }
}
