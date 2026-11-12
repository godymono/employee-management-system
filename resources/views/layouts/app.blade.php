<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PeoplePulse')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-shell page-shell--fluid">
        @auth
            <nav class="app-nav">
                <a href="{{ auth()->user()->isEmployee() ? route('employee.dashboard') : route('dashboard') }}" class="app-nav__brand">PeoplePulse</a>
                <div class="app-nav__links">
                    @if (auth()->user()->isEmployee())
                        <a href="{{ route('employee.dashboard') }}" class="app-nav__link {{ request()->routeIs('employee.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                        <a href="{{ route('profile.show') }}" class="app-nav__link {{ request()->routeIs('profile.show', 'employees.show', 'employees.edit') ? 'is-active' : '' }}">My Profile</a>
                        <a href="{{ route('pms.my') }}" class="app-nav__link {{ request()->routeIs('pms.my', 'pms.plans.*') ? 'is-active' : '' }}">My Performance</a>
                    @else
                        @if (auth()->user()->canManagePeople() || auth()->user()->isLineManager())
                            <a href="{{ route('dashboard') }}" class="app-nav__link {{ request()->routeIs('dashboard', 'employees.*') && ! request()->routeIs('profile.show') ? 'is-active' : '' }}">Employees</a>
                        @endif

                        <a href="{{ route('pms.index') }}" class="app-nav__link {{ request()->routeIs('pms.index', 'pms.cycles.*', 'pms.plans.create') ? 'is-active' : '' }}">Team Performance</a>

                        @if (auth()->user()->employee_id)
                            <a href="{{ route('employee.dashboard') }}" class="app-nav__link {{ request()->routeIs('employee.dashboard') ? 'is-active' : '' }}">My Dashboard</a>
                            <a href="{{ route('profile.show') }}" class="app-nav__link {{ request()->routeIs('profile.show') ? 'is-active' : '' }}">My Profile</a>
                            <a href="{{ route('pms.my') }}" class="app-nav__link {{ request()->routeIs('pms.my') || (request()->routeIs('pms.plans.*') && ! request()->routeIs('pms.plans.create')) ? 'is-active' : '' }}">My Performance</a>
                        @endif
                    @endif

                    <span class="app-nav__user">{{ auth()->user()->name }} · {{ auth()->user()->roleLabel() }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-link">Sign out</button>
                    </form>
                </div>
            </nav>
        @endauth

        @if (session('success'))
            <div class="alert-success fade-up">{{ session('success') }}</div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
