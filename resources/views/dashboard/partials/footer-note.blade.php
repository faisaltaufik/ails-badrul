<div class="footer-note {{ in_array($page, ['progress', 'users'], true) ? 'is-info' : '' }}">
    <div class="footer-note-copy">
        <span class="footer-note-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                @if (in_array($page, ['progress', 'users'], true))
                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M12 11.2V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="12" cy="8.2" r="1" fill="currentColor"/>
                @else
                    <path d="M9 18H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M10 21H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M8.3 14.5C7.5 13.8 7 12.8 7 11.7C7 9.1 9.1 7 11.7 7C14.3 7 16.4 9.1 16.4 11.7C16.4 12.8 15.9 13.8 15.1 14.5C14.5 15.1 14.1 15.8 14 16.6H9.4C9.3 15.8 8.9 15.1 8.3 14.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                @endif
            </svg>
        </span>
        <strong>
            @if ($page === 'progress')
                Refleksi yang Anda tulis akan membantu Anda dan dosen dalam memantau perkembangan pembelajaran berbasis proyek.
            @elseif ($page === 'users')
                Kelola akses pengguna secara terpusat agar akun admin, dosen, dan mahasiswa tetap tertata dan mudah dipantau.
            @else
                Kerjakan setiap tahapan sintak secara berurutan untuk hasil proyek yang lebih terstruktur dan optimal.
            @endif
        </strong>
    </div>

    @if (! in_array($page, ['progress', 'users'], true))
        <a class="footer-link" href="{{ route('dashboard.help', $menuQuery) }}">
            <span class="footer-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M12 11.2V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="12" cy="8.2" r="1" fill="currentColor"/>
                </svg>
            </span>
            <span>{{ $page === 'sintak' ? 'Apa itu BADRUL?' : 'Apa itu BADRUL' }}</span>
        </a>
    @endif
</div>