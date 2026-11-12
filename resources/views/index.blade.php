@extends('layouts.app')

@section('title', ($isLineManagerView ?? false) ? 'My Team — PeoplePulse' : 'PeoplePulse — HR Dashboard')

@section('content')
    <header class="page-header fade-up">
        <div class="page-header__copy">
            <p class="brand-title">{{ ($isLineManagerView ?? false) ? 'My team' : 'PeoplePulse' }}</p>
            <p class="page-subtitle">
                @if ($isLineManagerView ?? false)
                    View and update your direct reports — names, profiles, and employment details.
                @else
                    A clear view of your workforce — headcount, status, and every teammate in one place.
                @endif
            </p>
        </div>
        <div class="toolbar-actions">
            <p class="meta-text">Updated {{ now()->format('M j, Y') }}</p>
            @if (auth()->user()->canManagePeople())
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">+ Register</a>
            @endif
        </div>
    </header>

    <section class="stats-grid">
        <article class="panel stat-card fade-up fade-up-delay-1">
            <p class="stat-card__label">Total</p>
            <p class="stat-card__value">{{ $stats['total'] }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-2">
            <p class="stat-card__label">Active</p>
            <p class="stat-card__value is-active">{{ $stats['active'] }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-3">
            <p class="stat-card__label">On leave</p>
            <p class="stat-card__value is-warn">{{ $stats['on_leave'] }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-4">
            <p class="stat-card__label">Departments</p>
            <p class="stat-card__value">{{ $stats['departments'] }}</p>
        </article>
    </section>

    <section class="overview-grid fade-up fade-up-delay-2">
        <div class="panel panel--pad">
            <h2 class="section-heading">Department mix</h2>
            <p class="section-copy">Where your people sit across the organization.</p>
            <div class="mix-list">
                @php $maxDept = max(1, $departmentBreakdown->max() ?? 1); @endphp
                @forelse ($departmentBreakdown as $department => $count)
                    <div class="mix-row">
                        <div class="mix-row__meta">
                            <span class="mix-row__label">{{ $department }}</span>
                            <span class="mix-row__count">{{ $count }}</span>
                        </div>
                        <div class="mix-bar">
                            <div class="mix-bar__fill" style="width: {{ ($count / $maxDept) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="muted">No department data yet.</p>
                @endforelse
            </div>
        </div>

        <div class="panel panel--pad salary-panel">
            <div>
                <h2 class="section-heading">Salary overview</h2>
                <p class="section-copy">Average compensation across the roster.</p>
                <p class="salary-value">{{ \App\Models\Employee::formatTzs($stats['avg_salary']) }}</p>
                <p class="muted">Mean annual salary (TZS)</p>
            </div>
            <div class="mini-stats">
                <div class="mini-stat mini-stat--accent">
                    <span>Active rate</span>
                    <strong>
                        {{ $stats['total'] > 0 ? number_format(($stats['active'] / $stats['total']) * 100, 0) : 0 }}%
                    </strong>
                </div>
                <div class="mini-stat mini-stat--sky">
                    <span>Inactive</span>
                    <strong>{{ $employees->where('status', 'inactive')->count() }}</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="panel panel--flush fade-up fade-up-delay-3">
        <div class="directory-header">
            <h2 class="section-heading">{{ ($isLineManagerView ?? false) ? 'Team members' : 'Employee directory' }}</h2>
            <p class="section-copy">
                @if ($isLineManagerView ?? false)
                    Open a name to view their full profile, or edit their record.
                @else
                    Filter by branch or department, then search the list.
                @endif
            </p>

            <div class="filter-grid">
                <label class="field">
                    <span class="field-label">Branch</span>
                    <select id="filter-branch" class="field-input">
                        <option value="">All branches</option>
                        @foreach ($filterBranches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span class="field-label">Department</span>
                    <select id="filter-department" class="field-input">
                        <option value="">All departments</option>
                        @foreach ($filterDepartments as $department)
                            <option value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span class="field-label">Search</span>
                    <input id="employee-search" type="search" class="field-input" placeholder="Name, role, email…">
                </label>
            </div>

            <div class="filter-meta">
                <p id="filter-count">
                    Showing <span id="visible-count">{{ $employees->count() }}</span> of {{ $employees->count() }}
                </p>
                <button type="button" id="clear-filters" class="btn-link">Clear</button>
            </div>
        </div>

        <div id="employee-cards" class="employee-cards">
            @forelse ($employees as $employee)
                <article
                    class="employee-row employee-card"
                    data-search="{{ strtolower($employee->name.' '.$employee->email.' '.$employee->department.' '.$employee->branch.' '.$employee->position.' '.$employee->employee_id) }}"
                    data-branch="{{ $employee->branch }}"
                    data-department="{{ $employee->department }}"
                >
                    <div class="employee-card__top">
                        @if ($employee->photoUrl())
                            <div class="avatar avatar--md avatar--img">
                                <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}">
                            </div>
                        @else
                            <div class="avatar avatar--md">{{ $employee->initials() }}</div>
                        @endif
                        <div class="person__text">
                            <div class="employee-card__title-row">
                                <a href="{{ route('employees.show', $employee) }}" class="person__name person__name--link">{{ $employee->name }}</a>
                                <span class="status-pill is-{{ $employee->status }}">
                                    {{ str_replace('_', ' ', $employee->status) }}
                                </span>
                            </div>
                            <p class="person__meta">{{ $employee->position }}</p>
                            <p class="person__meta">{{ $employee->employee_id }} · {{ $employee->email }}</p>
                        </div>
                    </div>

                    <div class="employee-card__facts">
                        <div class="fact">
                            <span>Department</span>
                            <strong>{{ $employee->department }}</strong>
                        </div>
                        <div class="fact">
                            <span>Branch</span>
                            <strong>{{ $employee->branch }}</strong>
                        </div>
                        <div class="fact">
                            <span>Position</span>
                            <strong>{{ $employee->position }}</strong>
                        </div>
                        <div class="fact">
                            <span>Salary</span>
                            <strong>{{ $employee->formattedSalary() }}</strong>
                        </div>
                    </div>

                    <div class="employee-card__actions">
                        <div class="action-group">
                            <a href="{{ route('employees.show', $employee) }}">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="is-primary">Edit</a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="empty-state">No employees found. Register your first teammate.</p>
            @endforelse
        </div>

        <div class="table-wrap">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Hired</th>
                        <th class="is-right">Salary</th>
                        <th class="is-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="employee-table-body">
                    @forelse ($employees as $employee)
                        <tr
                            class="employee-row"
                            data-search="{{ strtolower($employee->name.' '.$employee->email.' '.$employee->department.' '.$employee->branch.' '.$employee->position.' '.$employee->employee_id) }}"
                            data-branch="{{ $employee->branch }}"
                            data-department="{{ $employee->department }}"
                        >
                            <td>
                                <div class="person">
                                    @if ($employee->photoUrl())
                                        <div class="avatar avatar--sm avatar--img">
                                            <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}">
                                        </div>
                                    @else
                                        <div class="avatar avatar--sm">{{ $employee->initials() }}</div>
                                    @endif
                                    <div class="person__text">
                                        <a href="{{ route('employees.show', $employee) }}" class="person__name person__name--link">{{ $employee->name }}</a>
                                        <p class="person__meta">{{ $employee->position }} · {{ $employee->employee_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $employee->department }}</td>
                            <td>{{ $employee->branch }}</td>
                            <td>{{ $employee->position }}</td>
                            <td>
                                <span class="status-pill is-{{ $employee->status }}">
                                    {{ str_replace('_', ' ', $employee->status) }}
                                </span>
                            </td>
                            <td class="muted">{{ $employee->hire_date->format('M j, Y') }}</td>
                            <td class="is-right">{{ $employee->formattedSalary() }}</td>
                            <td>
                                <div class="actions-cell">
                                    <div class="action-group">
                                        <a href="{{ route('employees.show', $employee) }}">View</a>
                                        <a href="{{ route('employees.edit', $employee) }}" class="is-primary">Edit</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">No employees found. Register your first teammate.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p id="no-results" class="no-results is-hidden">No employees match your filters.</p>
    </section>
@endsection

@push('scripts')
    <script>
        const search = document.getElementById('employee-search');
        const branchFilter = document.getElementById('filter-branch');
        const departmentFilter = document.getElementById('filter-department');
        const clearFilters = document.getElementById('clear-filters');
        const rows = document.querySelectorAll('.employee-row');
        const noResults = document.getElementById('no-results');
        const visibleCount = document.getElementById('visible-count');
        const totalEmployees = {{ $employees->count() }};

        const applyFilters = () => {
            const q = search.value.trim().toLowerCase();
            const branch = branchFilter.value;
            const department = departmentFilter.value;

            rows.forEach((row) => {
                const matchesSearch = !q || row.dataset.search.includes(q);
                const matchesBranch = !branch || row.dataset.branch === branch;
                const matchesDepartment = !department || row.dataset.department === department;
                row.classList.toggle('is-hidden', !(matchesSearch && matchesBranch && matchesDepartment));
            });

            const shown = window.matchMedia('(min-width: 768px)').matches
                ? [...document.querySelectorAll('#employee-table-body .employee-row')]
                    .filter((row) => !row.classList.contains('is-hidden')).length
                : [...document.querySelectorAll('#employee-cards .employee-row')]
                    .filter((row) => !row.classList.contains('is-hidden')).length;

            if (visibleCount) {
                visibleCount.textContent = String(shown);
            }

            noResults?.classList.toggle('is-hidden', shown > 0 || totalEmployees === 0);
        };

        search?.addEventListener('input', applyFilters);
        branchFilter?.addEventListener('change', applyFilters);
        departmentFilter?.addEventListener('change', applyFilters);
        clearFilters?.addEventListener('click', () => {
            search.value = '';
            branchFilter.value = '';
            departmentFilter.value = '';
            applyFilters();
        });

        window.addEventListener('resize', applyFilters);
        applyFilters();
    </script>
@endpush
