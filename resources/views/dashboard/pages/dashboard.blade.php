

@if ($currentProject)
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
                    <strong>{{ $activeStageCode }}</strong>
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
                <a class="button-secondary" href="{{ route('dashboard.sintak', $menuQuery) }}">Lanjutkan Tahap {{ $activeStageCode }}</a>
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
                <strong>{{ $activeStageCode }}</strong>
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