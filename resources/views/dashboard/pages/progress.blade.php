<section class="panel progress-summary-panel">
    <div class="progress-summary-bar">
        <div class="progress-summary-item">
            <div class="progress-summary-copy">
                <span class="progress-summary-label">Pertemuan ke :</span>
                <strong class="progress-summary-value">{{ $currentProject->pertemuan_ke }}</strong>
            </div>
        </div>

        <div class="progress-summary-divider" aria-hidden="true"></div>

        <div class="progress-summary-item">
            <div class="progress-summary-copy is-text">
                <span class="progress-summary-label">Materi :</span>
                <strong class="progress-summary-value">{{ $currentProject->materi }}</strong>
            </div>
        </div>
    </div>
</section>

<section class="progress-layout">
    <article class="panel progress-card progress-history-card" id="progress-history">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Progress per Sintak BADRUL</h2>
                {{-- <p class="panel-subtitle">Pantau status dan akses terakhir pada setiap tahapan sintak BADRUL.</p> --}}
            </div>
        </div>

        <div class="progress-table">
            <div class="progress-table-head">
                <span>Sintak</span>
                <span>Status</span>
                <span>Terakhir Diakses</span>
            </div>

            @foreach ($stageCards as $card)
                @php
                    $showLastAccess = ($card['status'] ?? 'belum') !== 'belum' && $card['updated_at'];
                @endphp
                <div class="progress-table-row">
                    <div class="progress-table-stage">
                        <span class="progress-stage-chip" style="background: {{ $card['color'] }};">{{ $card['code'] }}</span>
                        <div class="progress-stage-meta">
                            <strong>{{ $card['title'] }}</strong>
                            <span>{{ $card['summary'] }}</span>
                        </div>
                    </div>

                    <span class="progress-status-badge is-{{ $card['status'] }}">{{ $statusLabels[$card['status']] ?? 'Belum' }}</span>
                    <span class="progress-last-access">
                        @if ($showLastAccess)
                            <span class="progress-last-access-date">{{ $card['updated_at']->translatedFormat('d M Y') }}</span>
                            <span class="progress-last-access-time">{{ $card['updated_at']->translatedFormat('H:i') }}</span>
                        @else
                            -
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        <a class="button-outline progress-activity-button" href="{{ route('dashboard.progress', $menuQuery) }}#progress-history">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="5" y="4" width="14" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/>
                <path d="M8 9H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M8 13H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Lihat Riwayat Aktivitas</span>
        </a>
    </article>

    <article class="panel progress-card progress-detail-combined-card">
        <div>
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Detail Proyek</h2>
                    {{-- <p class="panel-subtitle">Ringkasan proyek aktif dan progres keseluruhan yang sudah dicapai.</p> --}}
                </div>
            </div>

            <div class="project-detail-stack">
                <div class="project-detail-section">
                    <span class="detail-label">Nama Proyek</span>
                    <strong class="detail-value-strong">{{ $currentProject->nama_proyek }}</strong>
                </div>

                <div class="project-detail-section">
                    <span class="detail-label">Deskripsi Proyek</span>
                    <span class="detail-value-copy">{{ $currentProject->deskripsi }}</span>
                </div>

                <div class="project-detail-section progress-meter">
                    <span class="detail-label">Progress Keseluruhan</span>
                    <strong>{{ $progressPercentLabel }}%</strong>
                    <div class="progress-meter-bar" aria-hidden="true">
                        <div class="progress-meter-fill" style="width: {{ $progressPercent }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="combined-card-divider" aria-hidden="true"></div>

        <div>
            <div class="reflection-journal-header">
                <div>
                    <h2 class="panel-title">Jurnal Refleksi</h2>
                    <p class="panel-subtitle">Tuliskan pembelajaran, hambatan, dan rencana perbaikan Anda.</p>
                </div>
                <span class="reflection-edit-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 16.5V20H7.5L17.2 10.3L13.7 6.8L4 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12.9 7.6L16.4 11.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>

            <form class="reflection-form" method="POST" action="{{ route('dashboard.reflection.update', $currentProject) }}">
                @csrf
                <input type="hidden" name="page" value="progress">
                <input type="hidden" name="sintak" value="{{ $activeStageCode }}">

                <label class="field" for="isi_refleksi">
                    <textarea id="isi_refleksi" name="isi_refleksi" placeholder="Tuliskan refleksi pembelajaran Anda...">{{ old('isi_refleksi', $latestReflection?->isi_refleksi) }}</textarea>
                    @error('isi_refleksi')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </label>

                <div class="reflection-form-footer">
                    <span class="reflection-saved-at">
                        {{ $latestReflection?->tanggal_refleksi ? 'Disimpan pada: '.$latestReflection->tanggal_refleksi->translatedFormat('d M Y, H.i').' WIB' : 'Belum ada refleksi yang disimpan.' }}
                    </span>
                    <button class="button-secondary reflection-save-button" type="submit">Simpan Refleksi</button>
                </div>
            </form>
        </div>
    </article>

    <aside class="panel progress-card progress-ai-card">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Saran AI untuk Pembelajaran</h2>
                <p class="panel-subtitle">Berdasarkan tahapan sintak yang sedang Anda kerjakan, AILS memberikan saran:</p>
            </div>
        </div>

        <ul class="progress-ai-list">
            @foreach ($reflectionSuggestions as $suggestion)
                <li>{{ $suggestion }}</li>
            @endforeach
        </ul>

        <div class="progress-ai-actions">
            <hr class="progress-ai-divider">

            <button
                class="button-outline progress-ai-launch"
                id="open-reflective-assistant"
                type="button"
                data-assistant-launch
                data-assistant-prompt="{{ $reflectionAssistantPrompt }}"
                @disabled(blank($reflectionAssistantPrompt))
            >
                <span class="progress-ai-launch-copy">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <rect x="6" y="7" width="12" height="10" rx="3.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M9 17V19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M15 17V19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M4 11H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M18 11H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="10" cy="11" r="1" fill="currentColor"/>
                            <circle cx="14" cy="11" r="1" fill="currentColor"/>
                            <path d="M9.5 14H14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    <span>Buka AI Reflective Assitant</span>
                </span>

                <svg class="progress-ai-launch-arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </aside>
</section>