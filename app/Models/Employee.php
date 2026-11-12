<?php

namespace App\Models;

use App\Models\Pms\PmsPlan;
use App\Models\Pms\PmsRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'department',
        'branch',
        'position',
        'pms_role_id',
        'line_manager_id',
        'status',
        'hire_date',
        'salary',
        'photo',
        'phone',
        'city',
        'address',
        'emergency_contact',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return asset('storage/'.$this->photo);
    }

    public function initials(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }

    /**
     * Format salary in Tanzanian Shillings (TZS).
     */
    public function formattedSalary(int $decimals = 0): string
    {
        return self::formatTzs($this->salary, $decimals);
    }

    public static function formatTzs(float|int|string|null $amount, int $decimals = 0): string
    {
        if ($amount === null || $amount === '') {
            return 'TZS —';
        }

        return 'TZS '.number_format((float) $amount, $decimals);
    }

    public function pmsRole(): BelongsTo
    {
        return $this->belongsTo(PmsRole::class, 'pms_role_id');
    }

    public function pmsPlans(): HasMany
    {
        return $this->hasMany(PmsPlan::class);
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'line_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'line_manager_id');
    }
}
