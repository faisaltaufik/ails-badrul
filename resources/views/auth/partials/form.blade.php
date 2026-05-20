{{-- <div class="form-kicker">{{ $loginFormMeta['kicker'] }}</div> --}}

<form class="form-block" method="POST" action="{{ route('login.store') }}">
    @csrf

    <div class="field">
        <label for="username">Username</label>
        <div class="input-shell {{ $errors->has('username') ? 'is-invalid' : '' }}">
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                placeholder="Masukkan username"
                autocomplete="username"
                spellcheck="false"
                required
                autofocus
            >
        </div>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-shell {{ $errors->has('password') ? 'is-invalid' : '' }}">
            <input
                id="password"
                name="password"
                type="password"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
            >
            <button class="toggle-password" type="button" aria-label="Tampilkan password" data-password-toggle>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 12C3.9 8.1 7.48 6 12 6C16.52 6 20.1 8.1 22 12C20.1 15.9 16.52 18 12 18C7.48 18 3.9 15.9 2 12Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2"/>
                </svg>
            </button>
        </div>
    </div>

    <button class="submit-button" type="submit">Login</button>

    <a class="forgot-link" href="{{ $loginFormMeta['forgotPasswordUrl'] }}">{{ $loginFormMeta['forgotPasswordLabel'] }}</a>
</form>

{{-- <div class="auth-note">
    <strong>{{ $loginFormMeta['noteTitle'] }}</strong>
    <span>{{ $loginFormMeta['noteBody'] }}</span>
</div> --}}