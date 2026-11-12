<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsReview extends Model
{
    protected $fillable = [
        'pms_plan_id',
        'period',
        'stage',
        'status',
        'total_score',
        'overall_comment',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PmsPlan::class, 'pms_plan_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PmsReviewScore::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isBmApproved(): bool
    {
        return $this->status === 'bm_approved';
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['submitted', 'rejected'], true);
    }
}
