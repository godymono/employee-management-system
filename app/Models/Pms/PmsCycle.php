<?php

namespace App\Models\Pms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsCycle extends Model
{
    protected $fillable = [
        'year',
        'title',
        'status',
        'h1_opens_at',
        'h1_closes_at',
        'h2_opens_at',
        'h2_closes_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'h1_opens_at' => 'date',
            'h1_closes_at' => 'date',
            'h2_opens_at' => 'date',
            'h2_closes_at' => 'date',
        ];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(PmsPlan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCurrentCalendarYear(): bool
    {
        return (int) $this->year === (int) now()->year;
    }

    /**
     * Current and past years only — never future years.
     */
    public function scopeAvailableForReports($query)
    {
        return $query->where('year', '<=', (int) now()->year)->orderByDesc('year');
    }

    /**
     * Ensure this calendar year exists and is the only active cycle.
     * Past years stay available for reports; they are marked closed.
     * Future placeholder years are removed.
     */
    public static function ensureCurrentYear(): self
    {
        $year = (int) now()->year;

        // Drop accidental future years (and their plans via cascade if configured).
        static::query()->where('year', '>', $year)->each(function (self $future) {
            $future->plans()->each(function (PmsPlan $plan) {
                $plan->reviews()->each(fn ($review) => $review->scores()->delete());
                $plan->reviews()->delete();
                $plan->items()->delete();
                $plan->delete();
            });
            $future->delete();
        });

        $cycle = static::query()->firstOrCreate(
            ['year' => $year],
            [
                'title' => $year.' Performance Cycle',
                'status' => 'active',
                'h1_opens_at' => "{$year}-01-01",
                'h1_closes_at' => "{$year}-06-30",
                'h2_opens_at' => "{$year}-07-01",
                'h2_closes_at' => "{$year}-12-31",
                'notes' => null,
            ]
        );

        static::query()
            ->where('year', $year)
            ->where('status', '!=', 'active')
            ->update(['status' => 'active']);

        static::query()
            ->where('year', '<', $year)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        return $cycle->fresh();
    }

    public static function findByYear(int $year): ?self
    {
        if ($year > (int) now()->year) {
            return null;
        }

        return static::query()->where('year', $year)->first();
    }
}
