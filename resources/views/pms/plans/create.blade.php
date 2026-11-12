@extends('layouts.app')

@section('title', 'Create Performance Plan — PeoplePulse')

@section('content')
    <div class="page-header fade-up">
        <div class="page-header__copy">
            <a href="{{ route('pms.index') }}" class="btn-ghost">← Back to performance</a>
            <h1 class="page-title">Create plan · {{ $cycle->year }}</h1>
            <p class="page-subtitle">Pick a KPI role for each person and create their plan. Admin and HR only.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert-error fade-up">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="panel panel--flush fade-up fade-up-delay-1">
        <div class="directory-header">
            <h2 class="section-heading">People without a {{ $cycle->year }} plan</h2>
            <p class="section-copy">Select the KPI template in the row, then create.</p>
        </div>

        <div class="table-wrap" style="display:block;">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th class="is-right">KPI role template</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $row)
                        <tr @if (isset($focusEmployeeId) && (int) $focusEmployeeId === (int) $row->id) class="is-highlighted" @endif>
                            <td>
                                <div class="person">
                                    <div class="avatar avatar--sm">{{ $row->initials() }}</div>
                                    <div class="person__text">
                                        <p class="person__name">{{ $row->name }}</p>
                                        <p class="person__meta">{{ $row->employee_id }} · {{ $row->branch }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $row->position }}</td>
                            <td class="is-right">
                                <form method="POST" action="{{ route('pms.plans.store') }}" class="year-start-form">
                                    @csrf
                                    <input type="hidden" name="pms_cycle_id" value="{{ $cycle->id }}">
                                    <input type="hidden" name="employee_id" value="{{ $row->id }}">
                                    <select name="pms_role_id" class="field-input year-start-form__select" aria-label="KPI role for {{ $row->name }}" required>
                                        <option value="">Select role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" @selected((int) $row->pms_role_id === (int) $role->id)>
                                                {{ $role->code }} — {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Create</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-state">Everyone already has a {{ $cycle->year }} plan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
