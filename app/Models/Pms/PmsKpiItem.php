<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmsKpiItem extends Model
{
    protected $fillable = [
        'pms_category_id',
        'name',
        'description',
        'default_weight',
        'default_target',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_weight' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PmsCategory::class, 'pms_category_id');
    }
}
