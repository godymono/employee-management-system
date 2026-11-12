<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — PeoplePulse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="login-shell">
        <section class="panel panel--pad login-card fade-up">
            <p class="brand-title">PeoplePulse</p>
            <p class="page-subtitle">Sign in to manage people and performance.</p>

            @if ($errors->any())
                <div class="alert-error" style="margin-top:1rem;">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" style="margin-top:1.25rem;">
                @csrf
                <div class="form-grid">
                    <div class="field span-2">
                        <label class="field-label" for="email">Email</label>
                        <input id="email" type="email" name="email" class="field-input" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="field span-2">
                        <label class="field-label" for="password">Password</label>
                        <input id="password" type="password" name="password" class="field-input" required>
                    </div>
                </div>

                <label class="photo-remove" style="margin-top:0.85rem;">
                    <input type="checkbox" name="remember" value="1">
                    Keep me signed in
                </label>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.1rem;">Sign in</button>
            </form>

            <div class="login-hints">
                <p class="meta-text"><strong>Demo accounts</strong> (password: <code>password</code>)</p>
                <ul>
                    <li>Admin — admin@peoplepulse.test</li>
                    <li>HR — hr@peoplepulse.test</li>
                    <li>Line Manager — manager@peoplepulse.test</li>
                    <li>Employee — employee@peoplepulse.test</li>
                </ul>
            </div>
        </section>
    </div>
</body>
</html>
