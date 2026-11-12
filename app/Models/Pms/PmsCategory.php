<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsCategory extends Model
{
    protected $fillable = [
        'pms_role_id',
        'name',
        'default_weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_weight' => 'decimal:2',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PmsRole::class, 'pms_role_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PmsKpiItem::class)->orderBy('sort_order');
    }
}
