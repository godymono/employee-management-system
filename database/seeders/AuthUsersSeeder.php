<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Pms\PmsRole;
use App\Models\User;
use App\Services\PmsPlanService;
use App\Models\Pms\PmsCycle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthUsersSeeder extends Seeder
{
    public function run(): void
    {
        $boe = PmsRole::query()->where('code', 'BOE')->first();
        $cso = PmsRole::query()->where('code', 'CSO')->first();

        $managerEmployee = Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-MGR-001'],
            [
                'name' => 'Amina Mwangi',
                'email' => 'manager@peoplepulse.test',
                'department' => 'Operations',
                'branch' => 'Dar es Salaam',
                'position' => 'Branch Operations Supervisor',
                'pms_role_id' => $boe?->id,
                'status' => 'active',
                'hire_date' => '2019-03-12',
                'salary' => 28_500_000,
                'phone' => '+255 700 111 001',
                'city' => 'Dar es Salaam',
            ]
        );

        $staffEmployee = Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-STAFF-001'],
            [
                'name' => 'Daniel Otieno',
                'email' => 'employee@peoplepulse.test',
                'department' => 'Operations',
                'branch' => 'Dar es Salaam',
                'position' => 'Customer Service Officer',
                'pms_role_id' => $cso?->id,
                'line_manager_id' => $managerEmployee->id,
                'status' => 'active',
                'hire_date' => '2022-07-01',
                'salary' => 14_400_000,
                'phone' => '+255 700 222 002',
                'city' => 'Dar es Salaam',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@peoplepulse.test'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'employee_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'hr@peoplepulse.test'],
            [
                'name' => 'Grace Njeri',
                'password' => Hash::make('password'),
                'role' => User::ROLE_HR,
                'employee_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@peoplepulse.test'],
            [
                'name' => $managerEmployee->name,
                'password' => Hash::make('password'),
                'role' => User::ROLE_LINE_MANAGER,
                'employee_id' => $managerEmployee->id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'employee@peoplepulse.test'],
            [
                'name' => $staffEmployee->name,
                'password' => Hash::make('password'),
                'role' => User::ROLE_EMPLOYEE,
                'employee_id' => $staffEmployee->id,
            ]
        );

        $cycle = PmsCycle::query()->where('status', 'active')->first();
        if ($cycle && $cso) {
            $exists = \App\Models\Pms\PmsPlan::query()
                ->where('pms_cycle_id', $cycle->id)
                ->where('employee_id', $staffEmployee->id)
                ->exists();

            if (! $exists) {
                app(PmsPlanService::class)->createPlan($cycle, $staffEmployee, $cso);
            }
        }

        if ($cycle && $boe) {
            $exists = \App\Models\Pms\PmsPlan::query()
                ->where('pms_cycle_id', $cycle->id)
                ->where('employee_id', $managerEmployee->id)
                ->exists();

            if (! $exists) {
                app(PmsPlanService::class)->createPlan($cycle, $managerEmployee, $boe);
            }
        }
    }
}
