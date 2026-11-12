@extends('layouts.app')

@section('title', 'Performance Management — PeoplePulse')

@section('content')
    <header class="page-header fade-up">
        <div class="page-header__copy">
            <p class="brand-title">Performance Management</p>
            <p class="page-subtitle">
                @if ($isCurrentYear)
                    Working in {{ $cycle->year }} — the current performance year starts automatically.
                @else
                    Viewing {{ $cycle->year }} performance reports. Switch back to {{ $currentCycle->year }} to continue active work.
                @endif
            </p>
        </div>
        <div class="toolbar-actions">
            <form method="GET" action="{{ route('pms.index') }}" class="year-start-form">
                <label class="meta-text" for="year">Year</label>
                <select id="year" name="year" class="field-input year-start-form__select" aria-label="Performance year" onchange="this.form.submit()">
                    @foreach ($cycles as $option)
                        <option value="{{ $option->year }}" @selected((int) $cycle->year === (int) $option->year)>
                            {{ $option->year }}{{ $option->isCurrentCalendarYear() ? ' · current' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if (auth()->user()->employee_id)
                <a href="{{ route('pms.my') }}" class="btn btn-secondary btn-sm">My plan</a>
            @endif

            @if ($isCurrentYear && auth()->user()->canManagePms())
                <a href="{{ route('pms.plans.create', ['cycle' => $cycle->id]) }}" class="btn btn-primary btn-sm">+ Create plan</a>
            @endif
        </div>
    </header>

    <section class="stats-grid">
        <article class="panel stat-card fade-up fade-up-delay-1">
            <p class="stat-card__label">{{ $isCurrentYear ? 'Active year' : 'Viewing year' }}</p>
            <p class="stat-card__value">{{ $cycle->year }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-2">
            <p class="stat-card__label">Plans</p>
            <p class="stat-card__value">{{ $stats['plans'] }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-3">
            <p class="stat-card__label">Pending approval</p>
            <p class="stat-card__value {{ $stats['pending_approval'] > 0 ? 'is-warn' : 'is-active' }}">{{ $stats['pending_approval'] }}</p>
        </article>
        <article class="panel stat-card fade-up fade-up-delay-4">
            <p class="stat-card__label">Finalized</p>
            <p class="stat-card__value">{{ $stats['finalized'] }}</p>
        </article>
    </section>

    <section class="overview-grid fade-up fade-up-delay-2">
        <div class="panel panel--pad">
            <h2 class="section-heading">Yearly flow</h2>
            <p class="section-copy">Default weights, mandatory approvals, then scores on the dashboard.</p>
            <ol class="flow-list">
                <li><strong>Stage 1 — KPI setup</strong> Plans start with template default weights. HR/managers can override; employees populate KPIs and submit for branch manager Accept/Reject.</li>
                <li><strong>Stage 2A — Mid-year</strong> Employee self assessment → branch manager Approve/Reject → HR Accept/Reject.</li>
                <li><strong>Stage 2B — End-year</strong> Same Approve (BM) then Accept (HR) sequence.</li>
                <li><strong>Finalize</strong> HR Accept on end-year finalizes scores on the employee dashboard.</li>
            </ol>
        </div>
        <div class="panel panel--pad">
            <h2 class="section-heading">KPI role templates</h2>
            <p class="section-copy">Imported from Branch Operations Staff KPIs.</p>
            <div class="role-chips">
                @forelse ($roles as $role)
                    <div class="role-chip">
                        <strong>{{ $role->code }}</strong>
                        <span>{{ $role->name }}</span>
                    </div>
                @empty
                    <p class="muted">No templates seeded yet. Run the PMS seeder.</p>
                @endforelse
            </div>
            <p class="meta-text" style="margin-top:1rem;">
                Mid-year: {{ optional($cycle->h1_opens_at)->format('M j') }} – {{ optional($cycle->h1_closes_at)->format('M j, Y') }}
                · End-year: {{ optional($cycle->h2_opens_at)->format('M j') }} – {{ optional($cycle->h2_closes_at)->format('M j, Y') }}
                @if (auth()->user()->canManagePms())
                    · <a href="{{ route('pms.cycles.edit', $cycle) }}" class="btn-link">Edit year</a>
                @endif
            </p>
        </div>
    </section>

    <section class="panel panel--flush fade-up fade-up-delay-3">
        <div class="directory-header">
            <h2 class="section-heading">
                {{ $isCurrentYear ? 'Performance plans' : 'Performance reports' }} · {{ $cycle->year }}
            </h2>
            <p class="section-copy">
                @if ($isCurrentYear)
                    Clear status for employee work and manager approval. Pending approval needs your review.
                @else
                    Read-only view of completed work from {{ $cycle->year }}.
                @endif
            </p>
        </div>

        <div class="table-wrap" style="display:block;">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Your action</th>
                        <th>Mid-year</th>
                        <th>End-year</th>
                        <th>Annual</th>
                        <th class="is-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $flow = $isCurrentYear ? $plan->flowFor(auth()->user()) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="person">
                                    <div class="avatar avatar--sm">{{ $plan->employee->initials() }}</div>
                                    <div class="person__text">
                                        <a href="{{ route('pms.plans.show', $plan) }}" class="person__name person__name--link">{{ $plan->employee->name }}</a>
                                        <p class="person__meta">{{ $plan->employee->employee_id }} · {{ $plan->employee->branch }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $plan->role->code }}</td>
                            <td><span class="status-pill is-{{ $plan->statusTone() }}">{{ $plan->statusLabel() }}</span></td>
                            <td>
                                @if ($flow)
                                    <span class="action-chip is-{{ $flow['tone'] }}">{{ $flow['title'] }}</span>
                                @else
                                    <span class="meta-text">Report</span>
                                @endif
                            </td>
                            <td>{{ $plan->h1_score !== null ? number_format($plan->h1_score, 1).'%' : '—' }}</td>
                            <td>{{ $plan->h2_score !== null ? number_format($plan->h2_score, 1).'%' : '—' }}</td>
                            <td>
                                @if ($plan->annual_score !== null)
                                    <strong>{{ number_format($plan->annual_score, 1) }}%</strong>
                                    <div class="meta-text">{{ $plan->rating_band }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <div class="action-group">
                                        <a href="{{ route('pms.plans.show', $plan) }}">{{ $isCurrentYear ? 'Open' : 'View report' }}</a>
                                        @if ($flow && ! empty($flow['action_url']))
                                            <a href="{{ $flow['action_url'] }}" class="is-primary">{{ $flow['action_label'] }}</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                @if ($isCurrentYear)
                                    No performance plans yet.
                                    @if (auth()->user()->canManagePms())
                                        <a href="{{ route('pms.plans.create', ['cycle' => $cycle->id]) }}">Create the first plan</a>.
                                    @endif
                                @else
                                    No performance reports for {{ $cycle->year }}.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
