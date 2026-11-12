@extends('layouts.app')

@section('title', $employee->name.' — Employee Profile')

@section('content')
    <div class="page-header fade-up">
        <div class="page-header__copy">
            @php
                $backLabel = 'dashboard';
                $backRoute = route('dashboard');
                if (! empty($isOwnProfile)) {
                    $backLabel = 'my dashboard';
                    $backRoute = route('employee.dashboard');
                } elseif (auth()->user()->isLineManager() && ! auth()->user()->canManagePeople()) {
                    $backLabel = 'my team';
                }
            @endphp
            <a href="{{ $backRoute }}" class="btn-ghost">← Back to {{ $backLabel }}</a>
            <h1 class="page-title">{{ ! empty($isOwnProfile) ? 'My profile' : $employee->name }}</h1>
            <p class="page-subtitle">
                @if (! empty($isOwnProfile))
                    {{ $employee->name }} · {{ $employee->position }} · {{ $employee->department }} · {{ $employee->branch }}
                @else
                    {{ $employee->position }} · {{ $employee->department }} · {{ $employee->branch }}
                @endif
            </p>
        </div>
        <div class="toolbar-actions">
            @if (auth()->user()->canManagePeople())
                <a href="{{ route('employees.create') }}" class="btn btn-secondary btn-sm">Register</a>
            @endif
            @if (! empty($canCreatePlan) && $activeCycle)
                <a href="{{ route('pms.plans.create', ['cycle' => $activeCycle->id, 'employee' => $employee->id]) }}" class="btn btn-secondary btn-sm">Create plan</a>
            @endif
            @if (! empty($canEdit))
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm">{{ ! empty($isOwnProfile) ? 'Edit my profile' : 'Edit '.\Illuminate\Support\Str::before($employee->name, ' ') }}</a>
            @endif
        </div>
    </div>

    <section class="panel panel--flush fade-up fade-up-delay-1">
        <div class="profile-banner">
            @if ($employee->photoUrl())
                <div class="avatar avatar--lg avatar--img">
                    <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}">
                </div>
            @else
                <div class="avatar avatar--lg">{{ $employee->initials() }}</div>
            @endif
            <div class="person__text">
                <div class="employee-card__title-row">
                    <h2 class="person__name person__name--lg">{{ $employee->name }}</h2>
                    <span class="status-pill is-{{ $employee->status }}">{{ str_replace('_', ' ', $employee->status) }}</span>
                </div>
                <p class="person__meta">
                    {{ $employee->employee_id }} · {{ $employee->position }} · {{ $employee->department }} · {{ $employee->branch }}
                </p>
                @if ($employee->bio)
                    <p class="profile-bio">{{ $employee->bio }}</p>
                @endif
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <p>Email</p>
                <p>{{ $employee->email }}</p>
            </div>
            <div class="detail-item">
                <p>Phone</p>
                <p>{{ $employee->phone ?: '—' }}</p>
            </div>
            <div class="detail-item">
                <p>City</p>
                <p>{{ $employee->city ?: '—' }}</p>
            </div>
            <div class="detail-item">
                <p>Department</p>
                <p>{{ $employee->department }}</p>
            </div>
            <div class="detail-item">
                <p>Branch</p>
                <p>{{ $employee->branch }}</p>
            </div>
            <div class="detail-item">
                <p>Position</p>
                <p>{{ $employee->position }}</p>
            </div>
            <div class="detail-item">
                <p>Address</p>
                <p>{{ $employee->address ?: '—' }}</p>
            </div>
            <div class="detail-item">
                <p>Emergency contact</p>
                <p>{{ $employee->emergency_contact ?: '—' }}</p>
            </div>
            <div class="detail-item">
                <p>Hire date</p>
                <p>{{ $employee->hire_date->format('F j, Y') }}</p>
            </div>
            <div class="detail-item">
                <p>Tenure</p>
                <p>{{ $employee->hire_date->diffForHumans(now(), true) }}</p>
            </div>
            <div class="detail-item">
                <p>Annual salary (TZS)</p>
                <p>{{ $employee->formattedSalary() }}</p>
            </div>
            <div class="detail-item">
                <p>Line manager</p>
                <p>{{ $employee->lineManager?->name ?? '—' }}</p>
            </div>
            <div class="detail-item">
                <p>KPI role</p>
                <p>{{ $employee->pmsRole?->name ?? '—' }}</p>
            </div>
        </div>
    </section>
@endsection
