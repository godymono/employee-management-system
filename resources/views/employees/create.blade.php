@extends('layouts.app')

@section('title', 'Register New Employee — PeoplePulse')

@section('content')
    <div class="page-header fade-up">
        <div class="page-header__copy">
            <a href="{{ route('dashboard') }}" class="btn-ghost">← Back to dashboard</a>
            <h1 class="page-title">Register New Employee</h1>
            <p class="page-subtitle">
                Add a teammate to the roster. An employee ID is suggested automatically and can be changed.
            </p>
        </div>
    </div>

    <section class="panel panel--form fade-up fade-up-delay-1">
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf
            @include('employees._form', ['defaults' => $defaults])

            <div class="form-actions">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Register</button>
            </div>
        </form>
    </section>
@endsection
