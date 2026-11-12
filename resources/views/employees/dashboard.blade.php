@extends('layouts.app')

@section('title', 'My Dashboard — PeoplePulse')

@section('content')
    <header class="page-header fade-up">
        <div class="page-header__copy">
            <p class="brand-title">Hello, {{ $employee?->name ?? $user->name }}</p>
            <p class="page-subtitle">
                Your personal dashboard — profile, role details, and performance for {{ $cycle->year }}.
            </p>
        </div>
        <div class="toolbar-actions">
            @if ($employee)
                <a href="{{ route('profile.show') }}" class="btn btn-secondary btn-sm">My profile</a>
                @if ($nextAction)
                    <a href="{{ $nextAction['url'] }}" class="btn btn-primary btn-sm">{{ $nextAction['label'] }}</a>
                @else
                    <a href="{{ route('pms.my') }}" class="btn btn-primary btn-sm">My performance</a>
                @endif
            @endif
        </div>
    </header>

    @unless ($employee)
        <section class="panel panel--pad fade-up">
            <h2 class="section-heading">Profile not linked</h2>
            <p class="page-subtitle">Your login is not linked to an employee record yet. Ask HR to connect your account.</p>
        </section>
    @else
        <section class="stats-grid">
            <article class="panel stat-card fade-up fade-up-delay-1">
                <p class="stat-card__label">Status</p>
                <p class="stat-card__value is-active">{{ str_replace('_', ' ', $employee->status) }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-2">
                <p class="stat-card__label">Department</p>
                <p class="stat-card__value" style="font-size:1.15rem;">{{ $employee->department }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-3">
                <p class="stat-card__label">Performance year</p>
                <p class="stat-card__value">{{ $cycle->year }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-4">
                <p class="stat-card__label">Plan status</p>
                <p class="stat-card__value" style="font-size:1.05rem;">{{ $plan?->statusLabel() ?? 'Not assigned' }}</p>
            </article>
        </section>

        <section class="overview-grid fade-up fade-up-delay-2">
            <div class="panel panel--flush">
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
                        <p class="person__meta">{{ $employee->position }}</p>
                        <p class="person__meta">{{ $employee->employee_id }} · {{ $employee->email }}</p>
                        <p class="person__meta">{{ $employee->department }} · {{ $employee->branch }}</p>
                        @if ($employee->lineManager)
                            <p class="person__meta">Line manager: <strong>{{ $employee->lineManager->name }}</strong></p>
                        @endif
                    </div>
                </div>

                <div class="employee-card__actions" style="padding:0 1.1rem 1.1rem;">
                    <div class="action-group">
                        <a href="{{ route('profile.show') }}">View full profile</a>
                        <a href="{{ route('employees.edit', $employee) }}" class="is-primary">Edit profile</a>
                    </div>
                </div>
            </div>

            <div class="panel panel--pad">
                <h2 class="section-heading">{{ $cycle->year }} performance scores</h2>
                @if ($plan)
                    <p class="section-copy">{{ $plan->role->name }} · {{ $plan->statusLabel() }}</p>
                    <div class="mini-stats" style="margin-top:1rem;">
                        <div class="mini-stat mini-stat--accent">
                            <span>Mid-year</span>
                            <strong>{{ $plan->h1_score !== null ? number_format($plan->h1_score, 1).'%' : '—' }}</strong>
                        </div>
                        <div class="mini-stat mini-stat--sky">
                            <span>End-year</span>
                            <strong>{{ $plan->h2_score !== null ? number_format($plan->h2_score, 1).'%' : '—' }}</strong>
                        </div>
                        <div class="mini-stat">
                            <span>Annual</span>
                            <strong>{{ $plan->annual_score !== null ? number_format($plan->annual_score, 1).'%' : '—' }}</strong>
                        </div>
                    </div>
                    @if ($plan->annual_score !== null)
                        <p class="meta-text" style="margin-top:1rem;">
                            Final rating: <strong>{{ $plan->rating_band ?? 'Recorded' }}</strong>
                            · scores appear here after manager acceptance
                        </p>
                    @elseif ($plan->h1_score !== null)
                        <p class="meta-text" style="margin-top:1rem;">
                            Mid-year score recorded after manager acceptance. End-year finalizes the annual score.
                        </p>
                    @else
                        <p class="meta-text" style="margin-top:1rem;">
                            Scores appear here once mid-year and end-year assessments are accepted by your manager.
                        </p>
                    @endif
                    <div class="form-actions" style="margin-top:1.25rem; justify-content:flex-start;">
                        @if ($nextAction)
                            <a href="{{ $nextAction['url'] }}" class="btn btn-primary btn-sm">{{ $nextAction['label'] }}</a>
                        @endif
                        <a href="{{ route('pms.plans.show', $plan) }}" class="btn btn-secondary btn-sm">Open plan</a>
                    </div>
                @else
                    <p class="section-copy">No performance plan has been assigned for {{ $cycle->year }} yet. HR will create it when ready.</p>
                    <a href="{{ route('pms.my') }}" class="btn btn-secondary btn-sm" style="margin-top:1rem;">Check performance</a>
                @endif
            </div>
        </section>

        <section class="panel panel--pad fade-up fade-up-delay-3">
            <h2 class="section-heading">Quick details</h2>
            <div class="detail-grid" style="margin-top:0.85rem;">
                <div class="detail-item">
                    <p>Phone</p>
                    <p>{{ $employee->phone ?: '—' }}</p>
                </div>
                <div class="detail-item">
                    <p>City</p>
                    <p>{{ $employee->city ?: '—' }}</p>
                </div>
                <div class="detail-item">
                    <p>Hire date</p>
                    <p>{{ $employee->hire_date->format('F j, Y') }}</p>
                </div>
                <div class="detail-item">
                    <p>KPI role</p>
                    <p>{{ $employee->pmsRole?->name ?? '—' }}</p>
                </div>
            </div>
        </section>
    @endunless
@endsection
