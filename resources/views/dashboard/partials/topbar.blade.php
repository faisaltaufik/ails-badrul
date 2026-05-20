<header class="topbar">
    <div class="welcome-stack">
        <h1>{{ ! $currentProject ? 'Model PjBL BADRUL Berbantuan AILS' : $headerTitle }}</h1>
        <p>{{ ! $currentProject ? 'Selamat datang di Proyek Baru Anda' : $headerDescription }}</p>
    </div>

    <div class="topbar-side">
        <button class="notification-button" type="button" aria-label="Notifikasi">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4C9.79086 4 8 5.79086 8 8V10.2C8 11.0756 7.69377 11.9235 7.13427 12.5969L6 13.9627V15H18V13.9627L16.8657 12.5969C16.3062 11.9235 16 11.0756 16 10.2V8C16 5.79086 14.2091 4 12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 18C10.3 19.1 11 20 12 20C13 20 13.7 19.1 14 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <div class="user-chip">
            <div class="avatar">🧑</div>
            <div class="user-meta">
                <strong>{{ $user->nama }}</strong>
                <span>{{ $userRoleLabel }}</span>
            </div>
        </div>
    </div>
</header>