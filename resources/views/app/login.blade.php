<div style="max-width:24rem;margin:2rem auto;padding:1rem;">
    <h1>{{ __('app.login') }}</h1>

    @if ($errors->any())
        <div style="color:#DC2626;" aria-live="polite" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('app.login.store') }}">
        @csrf

        <label for="email">{{ __('auth.email') }}</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email" spellcheck="false">

        <label for="password">{{ __('auth.password_label') }}</label>
        <input type="password" name="password" id="password" required autocomplete="current-password">

        <button type="submit">{{ __('app.login') }}</button>
    </form>
</div>
