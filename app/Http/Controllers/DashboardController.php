<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isEmployee()) {
            return redirect()->route('employee.dashboard');
        }

        $employeesQuery = Employee::query()->orderBy('name');

        if ($user->isLineManager() && $user->employee_id) {
            $employeesQuery->where(function ($q) use ($user) {
                $q->where('line_manager_id', $user->employee_id)
                    ->orWhere('id', $user->employee_id);
            });
        }

        $employees = $employeesQuery->get();

        $stats = [
            'total' => $employees->count(),
            'active' => $employees->where('status', 'active')->count(),
            'on_leave' => $employees->where('status', 'on_leave')->count(),
            'departments' => $employees->pluck('department')->unique()->count(),
            'avg_salary' => $employees->avg('salary') ?? 0,
        ];

        $departmentBreakdown = $employees
            ->groupBy('department')
            ->map->count()
            ->sortDesc();

        $filterDepartments = $employees->pluck('department')->unique()->sort()->values();
        $filterBranches = $employees->pluck('branch')->unique()->sort()->values();

        return view('index', [
            'employees' => $employees,
            'stats' => $stats,
            'departmentBreakdown' => $departmentBreakdown,
            'filterDepartments' => $filterDepartments,
            'filterBranches' => $filterBranches,
            'isLineManagerView' => $user->isLineManager() && ! $user->canManagePeople(),
        ]);
    }
}
