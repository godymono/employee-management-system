@extends('layouts.app')

@section('title', 'My Performance — PeoplePulse')

@section('content')
    <header class="page-header fade-up">
        <div class="page-header__copy">
            <h1 class="page-title">My performance</h1>
            <p class="page-subtitle">
                @if ($plan)
                    Your {{ $cycle->year }} scorecard · {{ $plan->role->name }}
                    · <span class="status-pill is-{{ $plan->statusTone() }}">{{ $plan->statusLabel() }}</span>
                @else
                    Your personal performance home for the selected year.
                @endif
            </p>
        </div>
        <div class="toolbar-actions">
            @if ($cycles->count() > 1)
                <form method="GET" action="{{ route('pms.my') }}" class="year-start-form">
                    <label class="meta-text" for="year">Year</label>
                    <select id="year" name="year" class="field-input year-start-form__select" onchange="this.form.submit()">
                        @foreach ($cycles as $option)
                            <option value="{{ $option->year }}" @selected((int) $cycle?->year === (int) $option->year)>
                                {{ $option->year }}{{ $option->isCurrentCalendarYear() ? ' · current' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if (! empty($flow['action_url']))
                <a href="{{ $flow['action_url'] }}" class="btn btn-primary btn-sm">{{ $flow['action_label'] }}</a>
            @elseif ($plan)
                <a href="{{ route('pms.plans.show', $plan) }}" class="btn btn-secondary btn-sm">Open plan</a>
            @endif
        </div>
    </header>

    @if ($message)
        <section class="panel panel--pad fade-up">
            <h2 class="section-heading">Nothing to show yet</h2>
            <p class="page-subtitle">{{ $message }}</p>
            @isset($cycle)
                <p class="meta-text">Year: {{ $cycle->year }}</p>
            @endisset
        </section>
    @else
        @include('pms._flow_banner', ['flow' => $flow])

        <section class="stats-grid">
            <article class="panel stat-card fade-up fade-up-delay-1">
                <p class="stat-card__label">{{ $isCurrentYear ? 'Active year' : 'Viewing year' }}</p>
                <p class="stat-card__value">{{ $cycle->year }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-2">
                <p class="stat-card__label">Mid-year score</p>
                <p class="stat-card__value is-active">{{ $plan->h1_score !== null ? number_format($plan->h1_score, 1).'%' : '—' }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-3">
                <p class="stat-card__label">End-year score</p>
                <p class="stat-card__value">{{ $plan->h2_score !== null ? number_format($plan->h2_score, 1).'%' : '—' }}</p>
            </article>
            <article class="panel stat-card fade-up fade-up-delay-4">
                <p class="stat-card__label">Annual</p>
                <p class="stat-card__value">
                    {{ $plan->annual_score !== null ? number_format($plan->annual_score, 1).'%' : '—' }}
                    @if ($plan->rating_band)
                        <span class="meta-text" style="display:block;font-size:0.75rem;">{{ $plan->rating_band }}</span>
                    @endif
                </p>
            </article>
        </section>

        @php
            $kpisSubmitted = in_array($plan->status, ['targets_submitted', 'targets_set', 'h1_self_draft', 'h1_submitted', 'h1_bm_approved', 'h1_reviewed', 'h2_self_draft', 'h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $kpisApproved = in_array($plan->status, ['targets_set', 'h1_self_draft', 'h1_submitted', 'h1_bm_approved', 'h1_reviewed', 'h2_self_draft', 'h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $midSelfDone = $selfH1?->isSubmitted()
                || in_array($plan->status, ['h1_submitted', 'h1_bm_approved', 'h1_reviewed', 'h2_self_draft', 'h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $midBmDone = in_array($plan->status, ['h1_bm_approved', 'h1_reviewed', 'h2_self_draft', 'h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $midHrDone = $managerH1?->isSubmitted()
                || in_array($plan->status, ['h1_reviewed', 'h2_self_draft', 'h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $endSelfDone = $selfH2?->isSubmitted()
                || in_array($plan->status, ['h2_submitted', 'h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $endBmDone = in_array($plan->status, ['h2_bm_approved', 'h2_reviewed', 'finalized'], true);
            $endHrDone = $managerH2?->isSubmitted()
                || in_array($plan->status, ['h2_reviewed', 'finalized'], true);

            $kpiEmployeeState = $kpisSubmitted ? 'done' : ($plan->status === 'draft' ? 'pending' : 'waiting');
            $kpiManagerState = $kpisApproved ? 'done' : ($plan->status === 'targets_submitted' ? 'approval' : 'waiting');
            $midSelfState = $midSelfDone ? 'done' : ($kpisApproved ? 'pending' : 'waiting');
            $midBmState = $midBmDone ? 'done' : ($midSelfDone ? 'approval' : 'waiting');
            $midHrState = $midHrDone ? 'done' : ($midBmDone ? 'approval' : 'waiting');
            $endSelfState = $endSelfDone ? 'done' : ($midHrDone ? 'pending' : 'waiting');
            $endBmState = $endBmDone ? 'done' : ($endSelfDone ? 'approval' : 'waiting');
            $endHrState = $endHrDone ? 'done' : ($endBmDone ? 'approval' : 'waiting');
        @endphp

        <section class="panel panel--pad fade-up fade-up-delay-3">
            <h2 class="section-heading">Stage 1 · KPI approval</h2>
            <div class="stage-status-list">
                @include('pms._stage_status', [
                    'label' => 'Your KPI setup',
                    'state' => $kpiEmployeeState,
                    'detail' => $kpisSubmitted ? 'Submitted' : 'Populate KPIs from templates',
                ])
                @include('pms._stage_status', [
                    'label' => 'Branch manager decision',
                    'state' => $kpiManagerState,
                    'detail' => $kpisApproved
                        ? 'Accepted'
                        : ($plan->status === 'targets_submitted' ? 'Accept or reject pending' : 'Submit KPIs for approval'),
                ])
            </div>
        </section>

        <section class="overview-grid fade-up fade-up-delay-3">
            <div class="panel panel--pad">
                <h2 class="section-heading">Mid-year</h2>
                <div class="stage-status-list">
                    @include('pms._stage_status', [
                        'label' => 'Your self assessment',
                        'state' => $midSelfState,
                        'detail' => $midSelfDone ? 'Submitted' : ($kpisApproved ? 'Not started / in progress' : 'Opens after KPI approval'),
                    ])
                    @include('pms._stage_status', [
                        'label' => 'Branch manager approve',
                        'state' => $midBmState,
                        'detail' => $midBmDone ? 'Approved' : ($midSelfDone ? 'Approve or reject pending' : 'Waiting on you'),
                    ])
                    @include('pms._stage_status', [
                        'label' => 'HR accept',
                        'state' => $midHrState,
                        'detail' => $midHrDone ? 'Accepted' : ($midBmDone ? 'Accept after BM approval' : 'Waiting on BM'),
                    ])
                </div>
            </div>
            <div class="panel panel--pad">
                <h2 class="section-heading">End-year</h2>
                <div class="stage-status-list">
                    @include('pms._stage_status', [
                        'label' => 'Your self assessment',
                        'state' => $endSelfState,
                        'detail' => $endSelfDone
                            ? 'Submitted'
                            : ($midHrDone ? 'Not started / in progress' : 'Opens after mid-year acceptance'),
                    ])
                    @include('pms._stage_status', [
                        'label' => 'Branch manager approve',
                        'state' => $endBmState,
                        'detail' => $endBmDone ? 'Approved' : ($endSelfDone ? 'Approve or reject pending' : 'Waiting on you'),
                    ])
                    @include('pms._stage_status', [
                        'label' => 'HR accept',
                        'state' => $endHrState,
                        'detail' => $endHrDone
                            ? 'Accepted — scores on dashboard'
                            : ($endBmDone ? 'Accept after BM approval' : 'Waiting on BM'),
                    ])
                </div>
            </div>
        </section>

        <section class="panel panel--pad fade-up" style="margin-top:1rem;">
            <h2 class="section-heading">Plan overview</h2>
            <p class="section-copy">{{ $plan->role->name }} · {{ $plan->items->count() }} KPI items · weight total {{ number_format($plan->totalWeight(), 1) }}%</p>
            <div class="form-actions" style="justify-content:flex-start; margin-top:1rem;">
                <a href="{{ route('pms.plans.show', $plan) }}" class="btn btn-secondary btn-sm">Open full plan</a>
            </div>
        </section>
    @endif
@endsection
