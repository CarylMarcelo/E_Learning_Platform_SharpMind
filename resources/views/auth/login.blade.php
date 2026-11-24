<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#9560d9">
    <title>Welcome back · SharpMind</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/js/login.js'])
</head>

<body class="login-body">
    <main class="login-shell">
        <section class="login-story" aria-label="Welcome to SharpMind">
            <x-brand :href="route('login')" :caption="true" />
            <div class="login-story-content"><span class="eyebrow">A COMMUNITY OF CURIOUS MINDS</span>
                <h1>Big ideas.<br>Brighter futures.</h1>
                <p>Bring your learning community together.<br>One connection. One possibility at a time.</p><x-learning-art id="login" />
            </div>
            <span class="login-story-footer">LEARN. GROW. BELONG.</span>
        </section>
        <section class="login-form-area">
            <div class="login-top-note"><x-icon name="shield" /> Your secure admin workspace</div>
            <div class="login-form-content">
                <span class="eyebrow login-eyebrow">GOOD TO SEE YOU AGAIN</span>
                <h2>Welcome back<span class="welcome-dot">.</span></h2>
                <p class="login-description">Sign in to keep your community growing.</p>
                @if($errors->any())<div class="alert alert-error" role="alert"><x-icon name="info" />{{ $errors->first() }}</div>@endif
                @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
                <form action="{{ route('login.post') }}" method="POST" class="login-form">
                    @csrf
                    <div class="field"><label for="email">Email address</label>
                        <div class="login-input"><x-icon name="mail" /><input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="example@gmail.com" autocomplete="username" required autofocus @if($errors->has('email')) aria-invalid="true" @endif></div>
                    </div>
                    <div class="field"><label for="password">Password</label>
                        <div class="login-input"><x-icon name="lock" /><input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="password-toggle" data-toggle-password aria-label="Show password" aria-pressed="false"><x-icon name="eye" /></button></div>
                    </div>
                    <div class="login-options"><label class="remember-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Keep me signed in</label><span class="login-access-note">Admin access only</span></div>
                    <button type="submit" class="button button-primary login-submit">Sign in to SharpMind <x-icon name="arrow-right" /></button>
                </form>
            </div>
            <footer class="login-footer">© {{ date('Y') }} SharpMind <span>Made for brighter minds ✧</span></footer>
        </section>
    </main>
</body>

</html>
