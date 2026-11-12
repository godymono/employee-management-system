<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected static int $sequence = 1;

    public function definition(): array
    {
        $departments = [
            'Engineering' => ['Software Engineer', 'DevOps Engineer', 'QA Engineer', 'Tech Lead'],
            'Human Resources' => ['HR Manager', 'Recruiter', 'People Ops', 'HR Specialist'],
            'Finance' => ['Accountant', 'Financial Analyst', 'Controller', 'Payroll Specialist'],
            'Marketing' => ['Marketing Manager', 'Content Strategist', 'Brand Designer', 'Growth Lead'],
            'Sales' => ['Account Executive', 'Sales Manager', 'SDR', 'Customer Success'],
            'Operations' => ['Operations Manager', 'Office Manager', 'Logistics Lead'],
        ];

        $branches = [
            'Headquarters',
            'Dar es Salaam',
            'Arusha',
            'Mwanza',
            'Dodoma',
            'Remote',
        ];

        $department = fake()->randomElement(array_keys($departments));
        $position = fake()->randomElement($departments[$department]);
        $id = self::$sequence++;

        return [
            'employee_id' => 'EMP-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'department' => $department,
            'branch' => fake()->randomElement($branches),
            'position' => $position,
            'status' => fake()->randomElement(['active', 'active', 'active', 'on_leave', 'inactive']),
            'hire_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'salary' => fake()->numberBetween(8_000_000, 45_000_000),
        ];
    }
}
