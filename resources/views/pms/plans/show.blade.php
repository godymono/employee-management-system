@extends('layouts.app')

@section('title', $plan->employee->name.' — Performance Plan')

@section('content')
    @php
        $user = auth()->user();
        $isOwn = $user->employee_id && (int) $user->employee_id === (int) $plan->employee_id;
        $canManage = $user->canManagePms();
        $canReview = $canManage || $user->isLineManager();
        $isCurrentYear = $isCurrentYear ?? $plan->cycle->isCurrentCalendarYear();
        $backRoute = $isOwn
            ? route('pms.my', ['year' => $plan->cycle->year])
            : route('pms.index', ['year' => $plan->cycle->year]);
        $backLabel = $isOwn ? 'My performance' : 'performance';
    @endphp

    <div class="page-header fade-up">
        <div class="page-header__copy">
            <a href="{{ $backRoute }}" class="btn-ghost">← Back to {{ $backLabel }}</a>
            <h1 class="page-title">{{ $isOwn ? 'My performance plan' : $plan->employee->name }}</h1>
            <p class="page-subtitle">
                {{ $plan->cycle->year }} · {{ $plan->role->name }}
                · <span class="status-pill is-{{ $plan->statusTone() }}">{{ $plan->statusLabel() }}</span>
                @unless ($isCurrentYear)
                    · report view
                @endunless
            </p>
        </div>
        @php $flow = $isCurrentYear ? $plan->flowFor($user) : null; @endphp
        <div class="toolbar-actions">
            @if ($flow && ! empty($flow['action_url']))
                <a href="{{ $flow['action_url'] }}" class="btn btn-primary btn-sm">{{ $flow['action_label'] }}</a>
            @endif
        </div>
    </div>

    @include('pms._flow_banner', ['flow' => $flow])

    <section class="stats-grid">
        <article class="panel stat-card">
            <p class="stat-card__label">Weight total</p>
            <p class="stat-card__value">{{ number_format($plan->totalWeight(), 1) }}%</p>
        </article>
        <article class="panel stat-card">
            <p class="stat-card__label">Mid-year score</p>
            <p class="stat-card__value is-active">{{ $plan->h1_score !== null ? number_format($plan->h1_score, 1).'%' : '—' }}</p>
        </article>
        <article class="panel stat-card">
            <p class="stat-card__label">End-year score</p>
            <p class="stat-card__value">{{ $plan->h2_score !== null ? number_format($plan->h2_score, 1).'%' : '—' }}</p>
        </article>
        <article class="panel stat-card">
            <p class="stat-card__label">Annual</p>
            <p class="stat-card__value">
                {{ $plan->annual_score !== null ? number_format($plan->annual_score, 1).'%' : '—' }}
                @if ($plan->rating_band)
                    <span class="meta-text" style="display:block;font-size:0.75rem;">{{ $plan->rating_band }}</span>
                @endif
            </p>
        </article>
    </section>

    <section class="panel panel--pad fade-up">
        <h2 class="section-heading">KPI targets</h2>
        <p class="section-copy">Weighted items for this year’s plan.</p>

        @foreach ($plan->groupedItems() as $category => $items)
            @php
                $categoryWeight = (float) ($items->first()->category_weight ?? $items->sum('weight'));
            @endphp
            <div class="pms-category" style="margin-top:1rem;">
                <div class="edit-panel__header">
                    <h3 class="section-heading" style="font-size:0.95rem;">{{ $category }}</h3>
                    <p class="category-weight">
                        <span class="category-weight__label">Category weight</span>
                        <span class="category-weight__value">{{ number_format($categoryWeight, 0) }}</span>
                    </p>
                </div>
                <div class="pms-item-list" style="margin-top:0.6rem;">
                    @foreach ($items as $item)
                        <div class="pms-item">
                            <div>
                                <strong>{{ $item->kpi_name }}</strong>
                                @if ($item->description)
                                    <p class="meta-text">{{ $item->description }}</p>
                                @endif
                            </div>
                            <div class="pms-item__fields">
                                <div>
                                    <span class="field-label">Weight (%)</span>
                                    <p class="weight-inline weight-inline--block">{{ number_format((float) $item->weight, 2) }}/ {{ number_format($categoryWeight, 0) }}</p>
                                </div>
                                <div>
                                    <span class="field-label">Target</span>
                                    <p class="pms-item__target">{{ $item->target ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
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

    <section class="panel panel--pad fade-up" style="margin-top:1rem;">
        <h2 class="section-heading">Stage 1 · KPI approval</h2>
        <div class="stage-status-list">
            @include('pms._stage_status', [
                'label' => 'Employee KPI setup',
                'state' => $kpiEmployeeState,
                'detail' => $kpisSubmitted ? 'Submitted' : 'Populate KPIs from templates',
            ])
            @include('pms._stage_status', [
                'label' => 'Branch manager decision',
                'state' => $kpiManagerState,
                'detail' => $kpisApproved
                    ? 'Accepted'
                    : ($plan->status === 'targets_submitted' ? 'Accept or reject pending' : 'Waiting on employee submit'),
            ])
        </div>
    </section>

    <section class="overview-grid fade-up" style="margin-top:1rem;">
        <div class="panel panel--pad">
            <h2 class="section-heading">Mid-year</h2>
            <div class="stage-status-list">
                @include('pms._stage_status', [
                    'label' => 'Employee self assessment',
                    'state' => $midSelfState,
                    'detail' => $midSelfDone ? 'Submitted' : ($kpisApproved ? 'Not started / in progress' : 'Opens after KPI approval'),
                ])
                @include('pms._stage_status', [
                    'label' => 'Branch manager approve',
                    'state' => $midBmState,
                    'detail' => $midBmDone ? 'Approved' : ($midSelfDone ? 'Approve or reject pending' : 'Waiting on employee'),
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
                    'label' => 'Employee self assessment',
                    'state' => $endSelfState,
                    'detail' => $endSelfDone ? 'Submitted' : ($midHrDone ? 'Not started / in progress' : 'Opens after mid-year acceptance'),
                ])
                @include('pms._stage_status', [
                    'label' => 'Branch manager approve',
                    'state' => $endBmState,
                    'detail' => $endBmDone ? 'Approved' : ($endSelfDone ? 'Approve or reject pending' : 'Waiting on employee'),
                ])
                @include('pms._stage_status', [
                    'label' => 'HR accept',
                    'state' => $endHrState,
                    'detail' => $endHrDone ? 'Accepted — scores on dashboard' : ($endBmDone ? 'Accept after BM approval' : 'Waiting on BM'),
                ])
            </div>
        </div>
    </section>
@endsection

