@extends('layouts.app')

@section('title', 'Edit '.$cycle->year.' Year — PeoplePulse')

@section('content')
    <div class="page-header fade-up">
        <div class="page-header__copy">
            <a href="{{ route('pms.index', ['year' => $cycle->year]) }}" class="btn-ghost">← Back to performance</a>
            <h1 class="page-title">Edit {{ $cycle->year }}</h1>
            <p class="page-subtitle">
                Adjust title or assessment windows.
                Status is automatic: {{ $cycle->isCurrentCalendarYear() ? 'current year stays active' : 'past years stay closed for reports' }}.
            </p>
        </div>
    </div>

    <section class="panel panel--form fade-up fade-up-delay-1">
        <form method="POST" action="{{ route('pms.cycles.update', $cycle) }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert-error">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="form-grid">
                <div class="field">
                    <label class="field-label">Year</label>
                    <input class="field-input" value="{{ $cycle->year }}" disabled>
                </div>
                <div class="field">
                    <label class="field-label">Status</label>
                    <input class="field-input" value="{{ $cycle->isCurrentCalendarYear() ? 'Active (current year)' : 'Closed (report year)' }}" disabled>
                </div>
                <div class="field span-2">
                    <label class="field-label" for="title">Title</label>
                    <input id="title" name="title" type="text" class="field-input" value="{{ old('title', $cycle->title) }}" required>
                </div>
                <div class="field">
                    <label class="field-label" for="h1_opens_at">Mid-year opens</label>
                    <input id="h1_opens_at" name="h1_opens_at" type="date" class="field-input" value="{{ old('h1_opens_at', optional($cycle->h1_opens_at)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label class="field-label" for="h1_closes_at">Mid-year closes</label>
                    <input id="h1_closes_at" name="h1_closes_at" type="date" class="field-input" value="{{ old('h1_closes_at', optional($cycle->h1_closes_at)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label class="field-label" for="h2_opens_at">End-year opens</label>
                    <input id="h2_opens_at" name="h2_opens_at" type="date" class="field-input" value="{{ old('h2_opens_at', optional($cycle->h2_opens_at)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label class="field-label" for="h2_closes_at">End-year closes</label>
                    <input id="h2_closes_at" name="h2_closes_at" type="date" class="field-input" value="{{ old('h2_closes_at', optional($cycle->h2_closes_at)->format('Y-m-d')) }}">
                </div>
                <div class="field span-2">
                    <label class="field-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="field-input field-textarea" rows="3">{{ old('notes', $cycle->notes) }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('pms.index', ['year' => $cycle->year]) }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
            </div>
        </form>
    </section>
@endsection
