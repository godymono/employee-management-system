<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsPlanItem extends Model
{
    protected $fillable = [
        'pms_plan_id',
        'pms_kpi_item_id',
        'category_name',
        'category_weight',
        'kpi_name',
        'description',
        'weight',
        'target',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category_weight' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PmsPlan::class, 'pms_plan_id');
    }

    public function kpiItem(): BelongsTo
    {
        return $this->belongsTo(PmsKpiItem::class, 'pms_kpi_item_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PmsReviewScore::class);
    }
}
