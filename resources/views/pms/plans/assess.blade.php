@extends('layouts.app')

@section('title', \App\Models\Pms\PmsPlan::assessmentLabel($period, $stage, $decisionVerb ?? null).' — '.$plan->employee->name)

@section('content')
    @php
        $user = auth()->user();
        $decisionVerb = $decisionVerb ?? 'Approve';
        $canDecide = $canDecide ?? true;
        $title = \App\Models\Pms\PmsPlan::assessmentLabel($period, $stage, $stage === 'manager' ? $decisionVerb : null);
        $readonly = $review->isLocked() || ($stage === 'manager' && ! $canDecide);
        $groupedItems = $plan->groupedItems();
        $categories = $groupedItems->keys()->values();
        $lastCategory = $categories->last();
        $selectedCategory = $activeCategory ?? ($stage === 'manager' ? 'all' : $categories->first());
        if ($selectedCategory !== 'all' && $selectedCategory && ! $categories->contains($selectedCategory)) {
            $selectedCategory = $categories->first();
        }
        $showAll = $selectedCategory === 'all';
        $selfScoreMap = $selfScoreMap ?? collect();
        $isManagerStage = $stage === 'manager';
        $isOwn = $user->employee_id && (int) $user->employee_id === (int) $plan->employee_id;
        $backAssess = $isOwn
            ? route('pms.my', ['year' => $plan->cycle->year])
            : route('pms.plans.show', $plan);
        $backAssessLabel = $isOwn ? 'My performance' : 'plan';
    @endphp

    @if ($isManagerStage)
        <div class="page-header page-header--approval fade-up">
            <div class="page-header__copy">
                <a href="{{ $backAssess }}" class="btn-ghost">← Back to {{ $backAssessLabel }}</a>
                <p class="approval-eyebrow">{{ $title }} · {{ $plan->cycle->year }}</p>
            </div>
            <div class="toolbar-actions">
                <div class="weight-meter panel panel--pad">
                    <span class="meta-text">Total score</span>
                    <strong id="live-score">0.00 / 100</strong>
                </div>
                @unless ($readonly)
                    <button type="submit" form="assess-form" name="intent" value="close" class="btn btn-secondary btn-sm">Save and close</button>
                @endunless
            </div>
        </div>

        <section class="panel panel--pad approval-subject fade-up">
            <div class="approval-subject__identity">
                @if ($plan->employee->photoUrl())
                    <div class="avatar avatar--hero avatar--img">
                        <img src="{{ $plan->employee->photoUrl() }}" alt="{{ $plan->employee->name }}">
                    </div>
                @else
                    <div class="avatar avatar--hero">{{ $plan->employee->initials() }}</div>
                @endif
                <div class="approval-subject__text">
                    <p class="approval-subject__label">Employee being assessed</p>
                    <h1 class="approval-subject__name">{{ $plan->employee->name }}</h1>
                    <p class="approval-subject__meta">
                        {{ $plan->employee->position ?: '—' }}
                        <span class="pms-item__meta-sep" aria-hidden="true">·</span>
                        {{ $plan->employee->department ?: '—' }}
                        <span class="pms-item__meta-sep" aria-hidden="true">·</span>
                        {{ $plan->employee->branch ?: '—' }}
                    </p>
                    <p class="approval-subject__meta">
                        {{ $plan->employee->employee_id }}
                        <span class="pms-item__meta-sep" aria-hidden="true">·</span>
                        KPI role {{ $plan->role->code }}
                        <span class="pms-item__meta-sep" aria-hidden="true">·</span>
                        {{ \App\Models\Pms\PmsPlan::periodLabel($period) }}
                    </p>
                </div>
            </div>
            <div class="approval-subject__side">
                <span class="status-pill is-{{ $canDecide ? 'approval' : 'waiting' }}">
                    {{ $canDecide ? 'Awaiting your '.$decisionVerb : 'Awaiting BM approval' }}
                </span>
                @if ($selfReview)
                    <div class="approval-subject__score">
                        <span class="meta-text">Submitted total</span>
                        <strong>{{ $selfReview->total_score !== null ? number_format((float) $selfReview->total_score, 2).' / 100' : '—' }}</strong>
                        @if ($selfReview->submitted_at)
                            <span class="meta-text">{{ $selfReview->submitted_at->format('M j, Y g:i A') }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <p class="approval-instruction fade-up">
            @if ($canDecide && $decisionVerb === 'Approve')
                Review {{ $plan->employee->name }}’s KPI points below, then Approve or Reject.
            @elseif ($canDecide && $decisionVerb === 'Accept')
                Branch manager already approved {{ $plan->employee->name }}. Confirm the totals, then Accept or Reject.
            @else
                Viewing {{ $plan->employee->name }}’s submission. HR can Accept after branch manager approval.
            @endif
        </p>

        @unless ($selfReview)
            <div class="alert-error fade-up">No employee self assessment was found for this period.</div>
        @endunless
    @else
        <div class="page-header fade-up">
            <div class="page-header__copy">
                <a href="{{ $backAssess }}" class="btn-ghost">← Back to {{ $backAssessLabel }}</a>
                <h1 class="page-title">{{ $title }}</h1>
                <p class="page-subtitle">
                    Score each KPI in points from 0 up to its weight. Weights already total 100 — the overall score is the sum of all KPI points.
                    {{ $plan->employee->name }} · {{ $plan->role->code }} · {{ $plan->cycle->year }}
                </p>
            </div>
            <div class="toolbar-actions">
                <div class="weight-meter panel panel--pad">
                    <span class="meta-text">Live total</span>
                    <strong id="live-score">0.00 / 100</strong>
                </div>
                @unless ($readonly)
                    <button type="submit" form="assess-form" name="intent" value="close" class="btn btn-secondary btn-sm">Save and close</button>
                @endunless
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('pms.plans.assess.save', [$plan, $period, $stage]) }}" class="fade-up fade-up-delay-1" id="assess-form">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($review->isLocked())
            <div class="alert-success">This decision is already locked.</div>
        @elseif ($isManagerStage && ! $canDecide)
            <div class="alert-success">Waiting for branch manager approval before HR can Accept.</div>
        @endif

        @if ($stage === 'self' && $plan->decision_note && in_array($plan->status, ['h1_self_draft', 'h2_self_draft'], true))
            <div class="alert-error">Assessment was rejected. Manager note: {{ $plan->decision_note }}</div>
        @endif

        @if ($categories->isNotEmpty())
            <section class="panel panel--pad category-filter fade-up">
                <div class="category-filter__row">
                    <label class="field category-filter__field">
                        <span class="field-label">Category</span>
                        <select id="category-filter" class="field-input" aria-label="Filter by KPI category">
                            <option
                                value="all"
                                data-last="1"
                                data-all="1"
                                @selected($showAll)
                            >
                                All categories
                            </option>
                            @foreach ($categories as $index => $category)
                                <option
                                    value="{{ $category }}"
                                    data-index="{{ $index }}"
                                    data-last="{{ $category === $lastCategory ? '1' : '0' }}"
                                    data-all="0"
                                    @selected(! $showAll && $selectedCategory === $category)
                                >
                                    {{ $index + 1 }}. {{ $category }}
                                    @if ($category === $lastCategory)
                                        (last)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p class="meta-text category-filter__hint" id="category-filter-hint">
                        @if ($isManagerStage)
                            Showing the employee’s submitted scores for your decision.
                        @else
                            Work one category at a time, or choose All categories.
                        @endif
                    </p>
                </div>
            </section>
        @endif

        @foreach ($groupedItems as $category => $items)
            @php
                $categoryWeight = (float) ($items->first()->category_weight ?? $items->sum('weight'));
                $categoryIndex = $categories->search($category);
                $isVisible = $showAll || $selectedCategory === $category;
            @endphp
            <section
                class="panel panel--pad pms-category"
                data-category="{{ $category }}"
                data-category-index="{{ $categoryIndex }}"
                data-is-last="{{ $category === $lastCategory ? '1' : '0' }}"
                @unless ($isVisible) hidden @endunless
            >
                <div class="edit-panel__header">
                    <div>
                        <h2 class="section-heading">{{ $category }}</h2>
                        <p class="section-copy">{{ $items->count() }} KPI items</p>
                    </div>
                    <p class="category-weight">
                        <span class="category-weight__label">Category weight</span>
                        <span class="category-weight__value">{{ number_format($categoryWeight, 0) }}</span>
                    </p>
                </div>

                <div class="pms-item-list">
                    @foreach ($items as $item)
                        @php
                            $score = $scoreMap->get($item->id);
                            $selfScore = $selfScoreMap->get($item->id);
                            $itemWeight = (float) $item->weight;
                            $pointsFrom = function ($row) use ($itemWeight) {
                                if (! $row) {
                                    return null;
                                }
                                if ($row->weighted_score !== null) {
                                    return (float) $row->weighted_score;
                                }
                                // Legacy rows stored points in achievement before the fix.
                                if ($row->achievement !== null && (float) $row->achievement <= $itemWeight + 0.05) {
                                    return (float) $row->achievement;
                                }
                                if ($row->achievement !== null && $itemWeight > 0) {
                                    return round(((float) $row->achievement / 100) * $itemWeight, 2);
                                }

                                return null;
                            };
                            $pointsValue = old(
                                'scores.'.$item->id.'.achievement',
                                $pointsFrom($score) ?? $pointsFrom($selfScore)
                            );
                            $commentValue = old(
                                'scores.'.$item->id.'.comment',
                                $score?->comment ?? $selfScore?->comment
                            );
                            $selfPoints = $pointsFrom($selfScore);
                        @endphp
                        <div class="pms-item pms-item--assess {{ $isManagerStage ? 'pms-item--approval' : '' }}" data-weight="{{ $itemWeight }}">
                            <div class="pms-item__copy">
                                <strong>{{ $item->kpi_name }}</strong>
                                <p class="pms-item__meta">
                                    Target: {{ $item->target ?: '—' }}
                                    <span class="pms-item__meta-sep" aria-hidden="true">·</span>
                                    Weight <span class="weight-inline">{{ number_format($itemWeight, 2) }} / {{ number_format($categoryWeight, 0) }}</span>
                                </p>
                                @if ($item->description)
                                    <p class="meta-text">{{ $item->description }}</p>
                                @endif
                            </div>
                            @if ($isManagerStage)
                                <div class="approval-kpi-grid">
                                    <div class="employee-submission">
                                        <p class="employee-submission__label">{{ $plan->employee->name }} submitted</p>
                                        <p class="employee-submission__value">
                                            <strong>{{ $selfPoints !== null ? number_format($selfPoints, 2) : '—' }}</strong>
                                            <span class="meta-text">/ {{ number_format($itemWeight, 2) }}</span>
                                        </p>
                                        @if ($selfScore?->comment)
                                            <p class="employee-submission__comment">“{{ $selfScore->comment }}”</p>
                                        @endif
                                    </div>
                                    <div class="pms-item__fields pms-item__fields--assess">
                                        <label class="field">
                                            <span class="field-label">Your decision score</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $itemWeight }}"
                                                name="scores[{{ $item->id }}][achievement]"
                                                class="field-input achievement-input"
                                                value="{{ $pointsValue }}"
                                                placeholder="0 – {{ number_format($itemWeight, 2) }}"
                                                {{ $readonly ? 'readonly' : '' }}
                                            >
                                        </label>
                                        <label class="field">
                                            <span class="field-label">Decision remark</span>
                                            <input
                                                type="text"
                                                name="scores[{{ $item->id }}][comment]"
                                                class="field-input"
                                                value="{{ $commentValue }}"
                                                placeholder="Optional note on this KPI"
                                                {{ $readonly ? 'readonly' : '' }}
                                            >
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="pms-item__fields pms-item__fields--assess">
                                    <label class="field">
                                        <span class="field-label">Score (points)</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $itemWeight }}"
                                            name="scores[{{ $item->id }}][achievement]"
                                            class="field-input achievement-input"
                                            value="{{ $pointsValue }}"
                                            placeholder="0 – {{ number_format($itemWeight, 2) }}"
                                            {{ $readonly ? 'readonly' : '' }}
                                        >
                                    </label>
                                    <label class="field">
                                        <span class="field-label">Comment / remarks</span>
                                        <input
                                            type="text"
                                            name="scores[{{ $item->id }}][comment]"
                                            class="field-input"
                                            value="{{ $commentValue }}"
                                            placeholder="Add a short remark"
                                            {{ $readonly ? 'readonly' : '' }}
                                        >
                                    </label>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @unless ($readonly)
            <div class="edit-footer">
                @if ($isManagerStage && $canDecide)
                    <div class="edit-footer__stack">
                        <label class="field" style="max-width:36rem;">
                            <span class="field-label">Decision note (required to reject)</span>
                            <textarea
                                name="decision_note"
                                class="field-input"
                                rows="2"
                                placeholder="Explain the decision if rejecting"
                            >{{ old('decision_note') }}</textarea>
                        </label>
                        <div class="edit-footer__hint muted" id="assess-footer-hint">
                            {{ $decisionVerb }} {{ $plan->employee->name }}’s assessment to record the score. Reject sends it back.
                        </div>
                        <div class="form-actions form-actions--inline">
                            <button type="submit" name="intent" value="draft" class="btn btn-secondary btn-sm">Save draft</button>
                            <button
                                type="submit"
                                name="intent"
                                value="reject"
                                class="btn btn-secondary btn-sm"
                                id="assess-reject-btn"
                                @unless ($showAll || $selectedCategory === $lastCategory) hidden @endunless
                            >
                                Reject {{ $plan->employee->name }}
                            </button>
                            <button
                                type="submit"
                                name="intent"
                                value="accept"
                                class="btn btn-primary btn-sm"
                                id="assess-submit-btn"
                                @unless ($showAll || $selectedCategory === $lastCategory) hidden @endunless
                            >
                                {{ $decisionVerb }} {{ $plan->employee->name }}
                            </button>
                        </div>
                    </div>
                @elseif (! $isManagerStage)
                    <div class="edit-footer__hint muted" id="assess-footer-hint">
                        Save draft continues to the next unfinished category. Use Save and close at the top to leave.
                    </div>
                    <div class="form-actions form-actions--inline">
                        <button type="submit" name="intent" value="draft" class="btn btn-secondary btn-sm">Save draft</button>
                        <button
                            type="submit"
                            name="intent"
                            value="submit"
                            class="btn btn-primary btn-sm"
                            id="assess-submit-btn"
                            @unless ($showAll || $selectedCategory === $lastCategory) hidden @endunless
                        >
                            Submit for approval
                        </button>
                    </div>
                @endif
            </div>
        @endunless
    </form>
@endsection

@push('scripts')
    <script>
        const rows = document.querySelectorAll('.pms-item--assess');
        const live = document.getElementById('live-score');
        const filter = document.getElementById('category-filter');
        const sections = document.querySelectorAll('.pms-category');
        const submitBtn = document.getElementById('assess-submit-btn');
        const rejectBtn = document.getElementById('assess-reject-btn');
        const footerHint = document.getElementById('assess-footer-hint');
        const filterHint = document.getElementById('category-filter-hint');
        const isManager = @json($isManagerStage);
        const decisionVerb = @json($decisionVerb);

        const refreshScore = () => {
            let total = 0;
            rows.forEach((row) => {
                const points = parseFloat(row.querySelector('.achievement-input')?.value);
                if (!Number.isNaN(points)) {
                    total += points;
                }
            });
            live.textContent = total.toFixed(2) + ' / 100';
            live.style.color = Math.abs(total - 100) < 0.05
                ? 'var(--color-accent)'
                : (total > 100 ? 'var(--color-warn)' : 'inherit');
        };

        const applyCategoryFilter = () => {
            if (!filter) {
                return;
            }

            const selected = filter.options[filter.selectedIndex];
            const selectedCategory = selected?.value || 'all';
            const showAll = selected?.dataset.all === '1' || selectedCategory === 'all';
            const canSubmit = showAll || selected?.dataset.last === '1';

            sections.forEach((section) => {
                section.hidden = ! (showAll || section.dataset.category === selectedCategory);
            });

            if (submitBtn) {
                submitBtn.hidden = !canSubmit;
            }
            if (rejectBtn) {
                rejectBtn.hidden = !canSubmit;
            }

            if (footerHint) {
                footerHint.textContent = isManager
                    ? (canSubmit
                        ? decisionVerb + ' records scores, or Reject to send back. Use Save and close at the top to leave.'
                        : 'Save draft continues to the next unfinished category. Use Save and close at the top to leave.')
                    : (canSubmit
                        ? 'You can submit for approval now, or save a draft. Use Save and close at the top to leave.'
                        : 'Save draft continues to the next unfinished category. Use Save and close at the top to leave.');
            }

            if (filterHint) {
                filterHint.textContent = isManager
                    ? (showAll
                        ? 'Showing all employee-submitted KPIs for your decision.'
                        : 'Filter categories while reviewing the employee submission.')
                    : (showAll
                        ? 'Showing all categories. Submit is available.'
                        : (canSubmit
                            ? 'You are on the last category. Submit is available.'
                            : 'Work one category at a time. Submit unlocks on the last category or All.'));
            }

            const url = new URL(window.location.href);
            if (showAll) {
                url.searchParams.set('category', 'all');
            } else {
                url.searchParams.set('category', selectedCategory);
            }
            window.history.replaceState({}, '', url);
        };

        document.querySelectorAll('.achievement-input').forEach((input) => {
            input.addEventListener('input', refreshScore);
        });

        filter?.addEventListener('change', applyCategoryFilter);

        refreshScore();
        applyCategoryFilter();
    </script>
@endpush
