@php
    $employeeModel = $employee ?? null;
    $isEdit = $employeeModel !== null;
    $formDefaults = $defaults ?? [];

    $value = function (string $key, mixed $fallback = '') use ($employeeModel, $formDefaults, $isEdit) {
        if ($isEdit) {
            $current = $employeeModel->{$key};
            if ($key === 'hire_date') {
                $current = $employeeModel->hire_date?->format('Y-m-d');
            }

            return old($key, $current);
        }

        return old($key, $formDefaults[$key] ?? $fallback);
    };
@endphp

@if ($errors->any() && empty($hideErrors))
    <div class="alert-error">
        <p><strong>Please fix the following:</strong></p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-grid">
    <div class="field">
        <label class="field-label" for="employee_id">Employee ID</label>
        <input id="employee_id" name="employee_id" type="text" class="field-input" value="{{ $value('employee_id') }}" required>
    </div>

    <div class="field">
        <label class="field-label" for="status">Status</label>
        <select id="status" name="status" class="field-input" required>
            @foreach ($statuses as $statusValue => $label)
                <option value="{{ $statusValue }}" @selected($value('status', 'active') === $statusValue)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field span-2">
        <label class="field-label" for="name">Full name</label>
        <input id="name" name="name" type="text" class="field-input" value="{{ $value('name') }}" required>
    </div>

    <div class="field span-2">
        <label class="field-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="field-input" value="{{ $value('email') }}" required>
    </div>

    <div class="field">
        <label class="field-label" for="department">Department</label>
        <select id="department" name="department" class="field-input" required>
            @php $selectedDepartment = $value('department', $departments[0]); @endphp
            @foreach ($departments as $department)
                <option value="{{ $department }}" @selected($selectedDepartment === $department)>{{ $department }}</option>
            @endforeach
            @if ($selectedDepartment && ! in_array($selectedDepartment, $departments, true))
                <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }}</option>
            @endif
        </select>
    </div>

    <div class="field">
        <label class="field-label" for="branch">Branch</label>
        <select id="branch" name="branch" class="field-input" required>
            @php $selectedBranch = $value('branch', $branches[0]); @endphp
            @foreach ($branches as $branch)
                <option value="{{ $branch }}" @selected($selectedBranch === $branch)>{{ $branch }}</option>
            @endforeach
            @if ($selectedBranch && ! in_array($selectedBranch, $branches, true))
                <option value="{{ $selectedBranch }}" selected>{{ $selectedBranch }}</option>
            @endif
        </select>
    </div>

    <div class="field span-2">
        <label class="field-label" for="position">Position</label>
        <input id="position" name="position" type="text" class="field-input" value="{{ $value('position') }}" required>
    </div>

    <div class="field">
        <label class="field-label" for="hire_date">Hire date</label>
        <input id="hire_date" name="hire_date" type="date" class="field-input" value="{{ $value('hire_date') }}" required>
    </div>

    <div class="field">
        <label class="field-label" for="salary">Annual salary (TZS)</label>
        <input id="salary" name="salary" type="number" step="1" min="0" class="field-input" value="{{ $value('salary') }}" placeholder="e.g. 18000000" required>
    </div>
</div>
