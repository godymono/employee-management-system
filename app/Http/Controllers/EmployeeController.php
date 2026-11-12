<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()?->canManagePeople(), 403);

        return view('employees.create', $this->formOptions([
            'employee_id' => $this->nextEmployeeId(),
            'status' => 'active',
            'branch' => 'Headquarters',
            'hire_date' => now()->toDateString(),
        ]));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManagePeople(), 403);

        $employee = Employee::create($request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'New employee registered successfully.');
    }

    public function profile(): View|\Illuminate\Http\RedirectResponse
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return redirect()
                ->route('employee.dashboard')
                ->with('success', 'Your account is not linked to an employee profile yet.');
        }

        return $this->show($employee);
    }

    public function show(Employee $employee): View
    {
        $this->authorizeEmployeeAccess($employee);

        $employee->loadMissing(['lineManager', 'pmsRole']);

        $activeCycle = null;
        $canCreatePlan = false;

        if (auth()->user()?->canManagePms()) {
            $activeCycle = PmsCycle::ensureCurrentYear();

            $canCreatePlan = ! PmsPlan::query()
                ->where('pms_cycle_id', $activeCycle->id)
                ->where('employee_id', $employee->id)
                ->exists();
        }

        $canEdit = auth()->user()?->canEditEmployee($employee) ?? false;
        $isOwnProfile = auth()->user()?->employee_id && (int) auth()->user()->employee_id === (int) $employee->id;

        return view('employees.show', compact(
            'employee',
            'activeCycle',
            'canCreatePlan',
            'canEdit',
            'isOwnProfile',
        ));
    }

    public function edit(Employee $employee): View
    {
        abort_unless(auth()->user()?->canEditEmployee($employee), 403);

        return view('employees.edit', array_merge(
            compact('employee'),
            $this->formOptions()
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditEmployee($employee), 403);

        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->boolean('remove_photo') && $employee->photo) {
            Storage::disk('public')->delete($employee->photo);
            $data['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $data['photo'] = $request->file('photo')->store('employee-photos', 'public');
        }

        $employee->update($data);

        $isOwn = auth()->user()?->employee_id && (int) auth()->user()->employee_id === (int) $employee->id;

        return redirect()
            ->route($isOwn ? 'profile.show' : 'employees.show', $isOwn ? [] : $employee)
            ->with('success', $isOwn ? 'Your profile was updated successfully.' : 'Employee record and profile updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function formOptions(array $defaults = []): array
    {
        return [
            'departments' => [
                'Engineering',
                'Human Resources',
                'Finance',
                'Marketing',
                'Sales',
                'Operations',
            ],
            'branches' => [
                'Headquarters',
                'Dar es Salaam',
                'Arusha',
                'Mwanza',
                'Dodoma',
                'Mbeya',
                'Remote',
            ],
            'statuses' => [
                'active' => 'Active',
                'on_leave' => 'On leave',
                'inactive' => 'Inactive',
            ],
            'defaults' => $defaults,
        ];
    }

    private function nextEmployeeId(): string
    {
        $latest = Employee::query()
            ->orderByDesc('id')
            ->value('employee_id');

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            return 'EMP-'.str_pad((string) ((int) $matches[1] + 1), 4, '0', STR_PAD_LEFT);
        }

        return 'EMP-'.str_pad((string) (Employee::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function authorizeEmployeeAccess(Employee $employee): void
    {
        abort_unless(auth()->user()?->managesEmployee($employee), 403);
    }
}
