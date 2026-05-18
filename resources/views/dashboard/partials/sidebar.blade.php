<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-mark">
            <img src="{{ asset('images/logo_ails_badrul_2.png') }}" alt="Logo AILS BADRUL">
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Menu utama">
        <a class="nav-link {{ $page === 'dashboard' ? 'is-active' : '' }}" href="{{ route('dashboard', $menuQuery) }}">
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 11.5L12 5L20 11.5V20H14.5V14.5H9.5V20H4V11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </span>
            <span class="nav-text">
                <strong>Dashboard</strong>
            </span>
        </a>
        <a class="nav-link {{ $page === 'sintak' ? 'is-active' : '' }}" href="{{ route('dashboard.sintak', $menuQuery) }}">
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L20 8L12 12L4 8L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 12L12 16L20 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 16L12 20L20 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="nav-text">
                <strong>Sintaks BADRUL</strong>
            </span>
        </a>
        <a class="nav-link {{ $page === 'progress' ? 'is-active' : '' }}" href="{{ route('dashboard.progress', $menuQuery) }}">
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 19V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 19V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M19 19V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 19H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </span>
            <span class="nav-text">
                <strong>Refleksi & Progress</strong>
            </span>
        </a>
        <a class="nav-link {{ $page === 'help' ? 'is-active' : '' }}" href="{{ route('dashboard.help', $menuQuery) }}">
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M9.6 9.9C9.7 8.5 10.8 7.5 12.2 7.5C13.7 7.5 14.9 8.6 14.9 10C14.9 11.1 14.2 11.8 13.2 12.4C12.2 13 11.7 13.6 11.7 14.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="11.9" cy="17.4" r="0.8" fill="currentColor"/></svg>
            </span>
            <span class="nav-text">
                <strong>Bantuan</strong>
            </span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="nav-button logout-button" type="submit">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 6H6V18H10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 8L18 12L14 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <span class="nav-text">
                    <strong>Keluar</strong>
                </span>
            </button>
        </form>
    </div>
</aside>