@extends('layouts.app')

@section('title', 'Edit Employee Record — '.$employee->name)

@section('content')
    <div class="page-header fade-up">
        <div class="page-header__copy">
            @php
                $isOwn = auth()->user()->employee_id && (int) auth()->user()->employee_id === (int) $employee->id;
                $profileRoute = $isOwn ? route('profile.show') : route('employees.show', $employee);
            @endphp
            <a href="{{ $profileRoute }}" class="btn-ghost">← Back to profile</a>
            <h1 class="page-title">{{ $isOwn ? 'Edit my profile' : 'Edit '.$employee->name }}</h1>
            <p class="page-subtitle">
                {{ $employee->position }} · {{ $employee->employee_id }} · Update employment details and profile.
            </p>
        </div>
        <div class="toolbar-actions">
            <a href="{{ $profileRoute }}" class="btn btn-secondary btn-sm">View profile</a>
            @if (auth()->user()->canManagePeople())
                <a href="{{ route('employees.create') }}" class="btn btn-secondary btn-sm">Register</a>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" class="fade-up fade-up-delay-1">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert-error">
                <p><strong>Please fix the following:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="edit-layout">
            <section class="panel panel--pad edit-panel">
                <div class="edit-panel__header">
                    <h2 class="section-heading">Employment record</h2>
                    <p class="section-copy">Core HR details used across the directory and reports.</p>
                </div>
                @include('employees._form', ['defaults' => [], 'hideErrors' => true])
            </section>

            <section class="panel panel--pad edit-panel">
                <div class="edit-panel__header">
                    <h2 class="section-heading">Profile & photo</h2>
                    <p class="section-copy">Personal contact details and a clear profile image.</p>
                </div>
                @include('employees._profile_form')
            </section>
        </div>

        <div class="edit-footer">
            <div class="edit-footer__hint muted">
                Changes save both the employment record and profile in one step.
            </div>
            <div class="form-actions form-actions--inline">
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Save all changes</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photo-preview');
        const photoFilename = document.getElementById('photo-filename');

        photoInput?.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (!file) {
                return;
            }

            if (photoFilename) {
                photoFilename.textContent = file.name;
            }

            const reader = new FileReader();
            reader.onload = () => {
                photoPreview.innerHTML = `<img src="${reader.result}" alt="Selected profile photo">`;
            };
            reader.readAsDataURL(file);
        });
    </script>
@endpush
