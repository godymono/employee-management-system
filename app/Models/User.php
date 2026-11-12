<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_HR = 'hr';

    public const ROLE_LINE_MANAGER = 'line_manager';

    public const ROLE_EMPLOYEE = 'employee';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isHr(): bool
    {
        return $this->role === self::ROLE_HR;
    }

    public function isLineManager(): bool
    {
        return $this->role === self::ROLE_LINE_MANAGER;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function canManagePeople(): bool
    {
        return $this->isAdmin() || $this->isHr();
    }

    public function canManagePms(): bool
    {
        return $this->isAdmin() || $this->isHr();
    }

    public function canReviewTeam(): bool
    {
        return $this->isAdmin() || $this->isHr() || $this->isLineManager();
    }

    public function managesEmployee(Employee $employee): bool
    {
        if ($this->canManagePeople()) {
            return true;
        }

        if ($this->employee_id && (int) $this->employee_id === (int) $employee->id) {
            return true;
        }

        if ($this->isLineManager() && $this->employee_id) {
            return (int) $employee->line_manager_id === (int) $this->employee_id
                || (int) $employee->id === (int) $this->employee_id;
        }

        return false;
    }

    public function canEditEmployee(Employee $employee): bool
    {
        return $this->managesEmployee($employee);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_HR => 'HR',
            self::ROLE_LINE_MANAGER => 'Line Manager',
            default => 'Employee',
        };
    }
}
