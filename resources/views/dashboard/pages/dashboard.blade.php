<section class="hero-grid">
    <article class="panel dashboard-hero-panel">
        <div class="panel-header dashboard-hero-header">
            <div>
                <h2 class="panel-title">Proyek Aktif dan Pengaturan Pertemuan</h2>
                <p class="panel-subtitle">Kelola proyek yang sedang berjalan, lanjutkan sintak aktif, dan pantau progres pembelajaran dari satu ringkasan utama.</p>
            </div>
            <a class="button-outline dashboard-hero-link" href="{{ route('dashboard.sintak', $menuQuery) }}">Buka Sintak BADRUL</a>
        </div>

        <div class="dashboard-project-highlight">
            <span class="dashboard-kicker">Proyek Aktif</span>
            <h3 class="dashboard-project-title">{{ $currentProject->nama_proyek }}</h3>
            <p class="dashboard-project-description">{{ $currentProject->deskripsi }}</p>

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
                    <strong>{{ $progressPercent }}%</strong>
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
                <strong>{{ $progressPercent }}%</strong>
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

{{-- <section class="dashboard-secondary-grid">
    <article class="panel dashboard-profile-panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Profil Proyek</h2>
                <p class="panel-subtitle">Pilih proyek, buat proyek baru, lalu perbarui informasi inti proyek sebelum masuk ke area kerja sintak.</p>
            </div>
        </div>

        <div class="toolbar-row dashboard-toolbar-row">
            <form class="inline-form" method="GET" action="{{ route('dashboard') }}">
                <select name="proyek" aria-label="Pilih proyek">
                    @foreach ($projects as $project)
                        <option value="{{ $project->id_proyek }}" {{ $project->id_proyek === $currentProject->id_proyek ? 'selected' : '' }}>
                            {{ $project->nama_proyek }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="sintak" value="{{ $activeStageCode }}">
                <button class="button-outline" type="submit">Buka Proyek</button>
            </form>

            <form method="POST" action="{{ route('dashboard.projects.create') }}">
                @csrf
                <input type="hidden" name="page" value="dashboard">
                <button class="button-secondary" type="submit">Proyek Baru</button>
            </form>
        </div>

        <form class="project-form" method="POST" action="{{ route('dashboard.projects.update', $currentProject) }}">
            @csrf
            <input type="hidden" name="page" value="dashboard">
            <input type="hidden" name="sintak" value="{{ $activeStageCode }}">

            <div class="field span-2">
                <label for="nama_proyek">Nama Proyek *</label>
                <input id="nama_proyek" name="nama_proyek" type="text" value="{{ old('nama_proyek', $currentProject->nama_proyek) }}">
                @error('nama_proyek')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="pertemuan_ke">Pertemuan Ke *</label>
                <select id="pertemuan_ke" name="pertemuan_ke">
                    @foreach ($meetingOptions as $meetingOption)
                        <option value="{{ $meetingOption }}" {{ (int) old('pertemuan_ke', $currentProject->pertemuan_ke) === (int) $meetingOption ? 'selected' : '' }}>
                            {{ $meetingOption }}
                        </option>
                    @endforeach
                </select>
                @error('pertemuan_ke')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="materi">Materi *</label>
                <select id="materi" name="materi">
                    @foreach ($materialOptions as $materialOption)
                        <option value="{{ $materialOption }}" {{ old('materi', $currentProject->materi) === $materialOption ? 'selected' : '' }}>
                            {{ $materialOption }}
                        </option>
                    @endforeach
                </select>
                @error('materi')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field span-2">
                <label for="deskripsi">Deskripsi Proyek *</label>
                <textarea id="deskripsi" name="deskripsi">{{ old('deskripsi', $currentProject->deskripsi) }}</textarea>
                @error('deskripsi')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field span-2">
                <button class="button-secondary" type="submit">Simpan Profil Proyek</button>
            </div>
        </form>
    </article>

    <aside class="dashboard-side-stack">
        <article class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Fokus Tahap BADRUL</h2>
                    <p class="panel-subtitle">Ringkasan tahap yang sedang aktif agar langkah berikutnya tetap jelas.</p>
                </div>
            </div>

            <div class="dashboard-stage-focus">
                <span class="dashboard-stage-badge" style="background: {{ $activeStage['color'] ?? 'var(--blue-700)' }};">{{ $activeStageCode }}</span>
                <div class="dashboard-stage-copy">
                    <strong>{{ $activeStage['title'] }}</strong>
                    <p>{{ $activeStage['description'] ?? $activeStage['workspace_intro'] ?? 'Lengkapi area kerja sintak untuk meneruskan progres proyek.' }}</p>
                </div>
            </div>

            <a class="button-secondary dashboard-stage-button" href="{{ route('dashboard.sintak', $menuQuery) }}">Masuk Area Kerja Sintak</a>
        </article>

        <article class="panel dashboard-timeline-panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Aktivitas Sintak Terbaru</h2>
                    <p class="panel-subtitle">Riwayat singkat tahap yang sudah disentuh pada proyek ini.</p>
                </div>
            </div>

            <div class="timeline">
                @foreach ($dashboardSummary['recentStageCards'] as $card)
                    <article class="timeline-item">
                        <span class="timeline-index" style="background: {{ $card['color'] }};">{{ $card['code'] }}</span>
                        <div class="timeline-copy">
                            <strong>{{ $card['title'] }} · {{ $statusLabels[$card['status']] ?? 'Belum' }}</strong>
                            <span>{{ $card['updated_at'] ? 'Terakhir diakses '.$card['updated_at']->translatedFormat('d M Y H:i') : 'Belum ada aktivitas yang tercatat pada sintak ini.' }}</span>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="dashboard-reflection-note">
                <span class="dashboard-reflection-label">Refleksi Terakhir</span>
                <p class="reflection-copy">{{ $dashboardSummary['latestReflectionPreview'] }}</p>
            </div>
        </article>
    </aside>
</section> --}}