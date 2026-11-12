@extends('layouts.app')

@section('title', 'Set KPIs — '.$plan->employee->name)

@section('content')
    @php
        $user = auth()->user();
        $isOwn = $user->employee_id && (int) $user->employee_id === (int) $plan->employee_id;
        $isReviewer = $user->canManagePms() || $user->isLineManager();
        $awaitingApproval = $plan->canApproveTargets();
        $canEdit = $plan->canEditTargets() || ($isReviewer && $awaitingApproval);
        $readonly = ! $canEdit;
        $backTargets = $isOwn
            ? route('pms.my', ['year' => $plan->cycle->year])
            : route('pms.plans.show', $plan);
        $backTargetsLabel = $isOwn ? 'My performance' : 'plan';
    @endphp

    <div class="page-header fade-up">
        <div class="page-header__copy">
            <a href="{{ $backTargets }}" class="btn-ghost">← Back to {{ $backTargetsLabel }}</a>
            <h1 class="page-title">Stage 1 · KPI setup</h1>
            <p class="page-subtitle">
                Default weights are pre-filled from the role template. Populate targets (and override weights if needed).
                Branch manager approval is required before mid-year opens.
                @if ($isOwn)
                    Your {{ $plan->role->code }} plan · {{ $plan->cycle->year }}
                @else
                    {{ $plan->employee->name }} · {{ $plan->role->code }} · {{ $plan->cycle->year }}
                @endif
            </p>
        </div>
        <div class="toolbar-actions">
            <div class="weight-meter panel panel--pad">
                <span class="meta-text">Total weight</span>
                <strong id="weight-total">0.00%</strong>
            </div>
        </div>
    </div>

    @if ($plan->decision_note && $plan->canEditTargets())
        <div class="alert-error fade-up">
            KPIs were rejected. Manager note: {{ $plan->decision_note }}
        </div>
    @endif

    @if ($awaitingApproval)
        <div class="alert-success fade-up">
            @if ($isReviewer)
                KPIs are awaiting your decision. You can override weights, then Accept or Reject.
            @else
                KPIs submitted — waiting for your branch manager to Accept or Reject.
            @endif
        </div>
    @endif

    <form
        method="POST"
        action="{{ $awaitingApproval && $isReviewer
            ? route('pms.plans.targets.decide', $plan)
            : route('pms.plans.targets.save', $plan) }}"
        class="fade-up fade-up-delay-1"
        id="targets-form"
    >
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @foreach ($plan->groupedItems() as $category => $items)
            <section class="panel panel--pad pms-category">
                <div class="edit-panel__header">
                    <div>
                        <h2 class="section-heading">{{ $category }}</h2>
                        <p class="section-copy">{{ $items->count() }} KPI items · template defaults applied</p>
                    </div>
                    <p class="category-weight">
                        <span class="category-weight__label">Category weight</span>
                        <span class="category-weight__value">{{ number_format((float) $items->sum('weight'), 0) }}</span>
                    </p>
                </div>

                <div class="pms-item-list">
                    @foreach ($items as $item)
                        <div class="pms-item">
                            <div class="pms-item__copy">
                                <strong>{{ $item->kpi_name }}</strong>
                                <p class="meta-text">{{ $item->description }}</p>
                            </div>
                            <div class="pms-item__fields">
                                <label class="field">
                                    <span class="field-label">Weight (%)</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="items[{{ $item->id }}][weight]"
                                        class="field-input weight-input"
                                        value="{{ old('items.'.$item->id.'.weight', $item->weight) }}"
                                        {{ $readonly ? 'readonly' : 'required' }}
                                    >
                                </label>
                                <label class="field">
                                    <span class="field-label">Target</span>
                                    <input
                                        type="text"
                                        name="items[{{ $item->id }}][target]"
                                        class="field-input"
                                        value="{{ old('items.'.$item->id.'.target', $item->target) }}"
                                        placeholder="e.g. 120mil / 100% compliance"
                                        {{ $readonly ? 'readonly' : '' }}
                                    >
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @unless ($readonly)
            <div class="edit-footer">
                @if ($awaitingApproval && $isReviewer)
                    <div class="edit-footer__stack">
                        <label class="field" style="max-width:36rem;">
                            <span class="field-label">Decision note (required to reject)</span>
                            <textarea
                                name="decision_note"
                                class="field-input"
                                rows="2"
                                placeholder="Explain what the employee should fix"
                            >{{ old('decision_note') }}</textarea>
                        </label>
                        <div class="form-actions form-actions--inline">
                            <button type="submit" name="decision" value="reject" class="btn btn-secondary btn-sm">Reject</button>
                            <button type="submit" name="decision" value="accept" class="btn btn-primary btn-sm">Accept KPIs</button>
                        </div>
                    </div>
                @else
                    <div class="edit-footer__hint muted">
                        Save draft anytime. Submit for approval when weights total 100% and targets are complete.
                        @if ($isReviewer && $plan->canEditTargets())
                            As HR/manager you can also Accept KPIs directly.
                        @endif
                    </div>
                    <div class="form-actions form-actions--inline">
                        <button type="submit" name="intent" value="draft" class="btn btn-secondary btn-sm">Save draft</button>
                        @if ($plan->canEditTargets())
                            <button type="submit" name="intent" value="submit" class="btn btn-primary btn-sm">Submit for approval</button>
                        @endif
                        @if ($isReviewer && $plan->canEditTargets())
                            <button type="submit" name="intent" value="approve" class="btn btn-primary btn-sm">Accept KPIs</button>
                        @endif
                    </div>
                @endif
            </div>
        @endunless
    </form>
@endsection

@push('scripts')
    <script>
        const inputs = document.querySelectorAll('.weight-input');
        const totalEl = document.getElementById('weight-total');

        const refresh = () => {
            const total = [...inputs].reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
            totalEl.textContent = total.toFixed(2) + '%';
            totalEl.style.color = Math.abs(total - 100) < 0.05 ? 'var(--color-accent)' : 'var(--color-warn)';
        };

        inputs.forEach((input) => input.addEventListener('input', refresh));
        refresh();
    </script>
@endpush
