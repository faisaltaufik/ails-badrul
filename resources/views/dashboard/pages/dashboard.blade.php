
@if ($showDashboardWelcome)
<section class="panel dashboard-welcome-panel">
    <div class="panel-header dashboard-welcome-header">
        <div>
            <h2 class="panel-title">Selamat Datang di Aplikasi Pembelajaran Berbasis Proyek</h2>
            <p class="panel-subtitle">Kelola proyek yang sedang berjalan, lanjutkan sintak aktif, dan pantau progres pembelajaran.</p>
        </div>
    </div>

    <div class="dashboard-welcome-stage">
        <div class="dashboard-welcome-stage-visual">
            <img src="{{ asset('images/sintaks_badrul.png') }}" alt="Sintaks BADRUL" loading="lazy">
        </div>

        <div class="dashboard-welcome-brand-visual">
            <img src="{{ asset('images/ails.png') }}" alt="Artificial Intelligence Learning System" loading="lazy">
            <div class="dashboard-welcome-actions">
                <a class="button-secondary dashboard-welcome-button" href="{{ $currentProject ? route('dashboard.sintak', $menuQuery) : route('dashboard.sintak') }}">
                    {{-- <span class="dashboard-welcome-button-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L20 8L12 12L4 8L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 12L12 16L20 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 16L12 20L20 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span> --}}
                    <span>Masuk ke Pembelajaran</span>
                    <span class="dashboard-welcome-button-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12H19" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M13.5 6.5L19 12L13.5 17.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </a>
            </div>
        </div>
    </div>
</section>
@elseif ($currentProject)
@php
    $dashboardPrimaryActionLabel = $showDashboardPendingProject
        ? 'Sintaks BADRUL'
        : ($dashboardStageLabel === 'Selesai'
        ? "Tinjau Tahap {$activeStageCode}"
        : "Lanjutkan Tahap {$dashboardStageLabel}");
@endphp
<section class="hero-grid">
    <article class="panel dashboard-hero-panel">
        <div class="panel-header dashboard-hero-header">
            <div>
                <h2 class="panel-title">Proyek Aktif dan Pengaturan Pertemuan</h2>
                <p class="panel-subtitle">Kelola proyek yang sedang berjalan, lanjutkan sintak aktif, dan pantau progres pembelajaran dari satu ringkasan utama.</p>
            </div>
        </div>

        <div class="dashboard-project-highlight">
            <span class="dashboard-kicker">Proyek Aktif</span>
            <h3 class="dashboard-project-title">{{ $currentProject->displayName() }}</h3>
            <p class="dashboard-project-description">{{ $currentProject->displayDescription() }}</p>

            <div class="dashboard-chip-row">
                <span class="dashboard-chip">
                    <span>Pertemuan</span>
                    <strong>{{ $currentProject->pertemuan_ke }}</strong>
                </span>
                <span class="dashboard-chip is-wide">
                    <span>Materi</span>
                    <strong>{{ $currentProject->materi }}</strong>
                </span>
                <span class="dashboard-chip">
                    <span>Tahap Aktif</span>
                    <strong>{{ $dashboardStageLabel }}</strong>
                </span>
            </div>
        </div>

        <div class="dashboard-progress-block">
            <div class="dashboard-progress-copy">
                <div class="dashboard-progress-meta">
                    <span class="dashboard-progress-label">Progress BADRUL</span>
                    <strong>{{ $progressPercentLabel }}%</strong>
                </div>
                <p>{{ $dashboardSummary['currentStageLabel'] }}</p>
            </div>

            <div class="dashboard-progress-track" aria-hidden="true">
                <div class="dashboard-progress-fill" style="width: {{ $progressPercent }}%;"></div>
            </div>

            <div class="dashboard-hero-actions">
                <a class="button-secondary" href="{{ route('dashboard.sintak', $menuQuery) }}">{{ $dashboardPrimaryActionLabel }}</a>
                <a class="button-outline" href="{{ route('dashboard.progress', $menuQuery) }}">Buka Refleksi &amp; Progress</a>
            </div>
        </div>

    </article>

    <aside class="panel dashboard-quick-panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Ringkasan Learning Analytics</h2>
                <p class="panel-subtitle">Pantau progres sintak, kelengkapan workspace, dan refleksi proyek secara ringkas.</p>
            </div>
        </div>

        <div class="analytics-grid">
            <article class="analytic-card">
                <span>Progress BADRUL</span>
                <strong>{{ $progressPercentLabel }}%</strong>
            </article>
            <article class="analytic-card">
                <span>Sintak Aktif</span>
                <strong>{{ $dashboardStageLabel }}</strong>
            </article>
            <article class="analytic-card">
                <span>Workspace Tersimpan</span>
                <strong>{{ $analytics['workspace_count'] }}</strong>
            </article>
            <article class="analytic-card">
                <span>Refleksi Terekam</span>
                <strong>{{ $analytics['reflection_count'] }}</strong>
            </article>
        </div>

        <div class="dashboard-alert-card">
            <span class="dashboard-alert-label">Fokus Saat Ini</span>
            <strong>{{ $dashboardSummary['currentStageLabel'] }}</strong>
            <p>Lengkapi workspace tahap ini lalu simpan refleksi setelah sintak selesai agar progres pembelajaran tetap terbaca jelas.</p>
        </div>

        <div class="dashboard-mini-links">
            <a class="button-outline dashboard-mini-link" href="{{ route('dashboard.help', $menuQuery) }}">Panduan BADRUL</a>
            <a class="button-outline dashboard-mini-link" href="{{ route('dashboard.progress', $menuQuery) }}">Lihat Progress</a>
        </div>
    </aside>
</section>

@endif