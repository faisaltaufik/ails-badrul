
<section class="panel sintak-toolbar-panel">
    @php
        $sintakProjects = $projects->sortBy('pertemuan_ke')->values();
        $selectedProjectId = (int) ($currentProject?->id_proyek ?? $sintakProjects->first()?->id_proyek ?? 0);
    @endphp

    <form id="sintak-toolbar-form" class="sintak-toolbar-form" method="GET" action="{{ route('dashboard.sintak') }}">
        <input type="hidden" name="proyek" value="{{ $selectedProjectId }}" data-sintak-project-input>

        <div class="sintak-toolbar-item">
            <span class="sintak-toolbar-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 3V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M16 3V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M4 10H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <rect x="4" y="5" width="16" height="15" rx="3" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </span>
            <div class="sintak-toolbar-copy">
                <label for="sintak-pertemuan">Pertemuan Ke</label>
                <select id="sintak-pertemuan" class="sintak-toolbar-select" data-sintak-autosubmit>
                    @foreach ($sintakProjects as $projectOption)
                        <option value="{{ $projectOption->id_proyek }}" {{ $selectedProjectId === (int) $projectOption->id_proyek ? 'selected' : '' }}>
                            {{ $projectOption->pertemuan_ke }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="sintak-toolbar-divider" aria-hidden="true"></div>

        <div class="sintak-toolbar-item">
            <span class="sintak-toolbar-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 6.5C5 5.67157 5.67157 5 6.5 5H10.5C11.8807 5 13 6.11929 13 7.5V18C13 16.8954 12.1046 16 11 16H6.5C5.67157 16 5 16.6716 5 17.5V6.5Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M19 6.5C19 5.67157 18.3284 5 17.5 5H13.5C12.1193 5 11 6.11929 11 7.5V18C11 16.8954 11.8954 16 13 16H17.5C18.3284 16 19 16.6716 19 17.5V6.5Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </span>
            <div class="sintak-toolbar-copy">
                <label for="sintak-materi">Materi</label>
                <select
                    id="sintak-materi"
                    class="sintak-toolbar-select"
                    data-material-display
                    data-sintak-autosubmit
                >
                    @foreach ($sintakProjects as $projectOption)
                        <option value="{{ $projectOption->id_proyek }}" {{ $selectedProjectId === (int) $projectOption->id_proyek ? 'selected' : '' }}>
                            {{ $projectOption->materi }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</section>

<section class="panel sintak-board">
    <h2 class="section-title">Model PjBL Sintaks B A D R U L</h2>

    <div class="sintak-grid">
        @foreach ($stageCards as $card)
            <a
                href="{{ route('dashboard.sintak', ['proyek' => $currentProject->id_proyek, 'sintak' => $card['code']]) }}"
                class="sintak-card is-{{ $card['status'] }} {{ $card['code'] === $activeStageCode ? 'is-active' : '' }}"
                style="--stage-color: {{ $card['color'] }};"
            >
                <div class="stage-head">
                    <div class="stage-badge" style="background: {{ $card['color'] }};">{{ $card['code'] }}</div>
                    <div class="stage-copy">
                        <strong>{{ $card['title'] }}</strong>
                    </div>
                </div>

                <p class="stage-description">{{ $card['summary'] }}</p>

                <span class="stage-entry-button">Masuk</span>

                <div class="stage-footer" style="color: {{ $statusColors[$card['status']] ?? 'var(--pending)' }};">
                    <span class="stage-tag">{{ $statusLabels[$card['status']] ?? 'Belum' }}</span>
                    <span class="stage-status-dot" aria-hidden="true"></span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="legend">
        <span><i style="background: var(--success);"></i> Selesai</span>
        <span><i style="background: var(--warning);"></i> Proses</span>
        <span><i style="background: var(--pending);"></i> Belum</span>
    </div>
</section>

@php
    $activeStageCard = collect($stageCards)->firstWhere('code', $activeStageCode);
    $activeStageColor = $activeStageCard['color'] ?? ($activeStage['color'] ?? '#ff7b1a');
@endphp

<section class="workspace-grid" style="--stage-color: {{ $activeStageColor }};">
    <article class="panel workspace-panel">
        <div class="panel-header workspace-header">
            <div>
                <h2 class="panel-title" style="color: {{ $activeStageColor }};">{{ $activeStage['workspace_title'] }}</h2>
                <p class="panel-subtitle">{{ $activeStage['workspace_intro'] }}</p>
            </div>
        </div>

        <form class="workspace-form" method="POST" action="{{ route('dashboard.stages.update', ['proyek' => $currentProject->id_proyek, 'sintak' => $activeStageCode]) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="page" value="sintak">

            @foreach ($activeStage['fields'] as $field)
                @php
                    $fieldType = $field['type'] ?? 'textarea';
                    $fieldValue = isset($field['project_attribute'])
                        ? old($field['name'], data_get($currentProject, $field['project_attribute']))
                        : old($field['name'], $workspaceValues[$field['name']] ?? '');
                    $storedFile = is_array($fieldValue) ? $fieldValue : null;
                    $fieldLabel = trim((string) preg_replace('/\s*\*+\s*$/', '', $field['label'] ?? ''));
                @endphp

                <div class="field {{ ($field['span'] ?? 1) === 2 ? 'span-2' : '' }}">
                    <label for="{{ $field['name'] }}">
                        {{ $fieldLabel }}

                        @if ($field['required'])
                            <span class="required-marker">*</span>
                        @endif
                    </label>

                    @if (! empty($field['helper']))
                        <span class="field-helper">{{ $field['helper'] }}</span>
                    @endif

                    @if ($fieldType === 'textarea')
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] }}">{{ is_string($fieldValue) ? $fieldValue : '' }}</textarea>
                    @elseif ($fieldType === 'file')
                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="file">

                        @if (($storedFile['name'] ?? null) !== null)
                            <span class="field-file-meta">File tersimpan: {{ $storedFile['name'] }}</span>
                        @endif
                    @else
                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="text" value="{{ is_string($fieldValue) ? $fieldValue : '' }}" placeholder="{{ $field['placeholder'] }}">
                    @endif

                    @error($field['name'])
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach

            <div class="workspace-form-footer">
                <span class="workspace-required-note">* Wajib diisi</span>
                <input type="hidden" name="advance" value="1">
                <button class="button workspace-submit" type="submit" style="background: {{ $activeStageColor }}; border-color: {{ $activeStageColor }}; box-shadow: 0 14px 28px rgba(8, 21, 64, 0.16);">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M7 12H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M13 8L17 12L13 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Simpan dan Lanjutkan Sintak</span>
                </button>
            </div>
        </form>
    </article>

    <aside
        id="assistant-panel"
        class="panel assistant-panel"
    >
        <div class="assistant-header">
            <div class="assistant-title-row">
                <span class="assistant-icon" aria-hidden="true">
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
                </span>
                <div>
                    <h2 class="panel-title" style="color: {{ $activeStageColor }};">AI Assistant untuk Sintak {{ $activeStageCode }}</h2>
                    <p class="panel-subtitle">Pilih assistant yang sesuai dengan kebutuhan Anda.</p>
                </div>
            </div>
        </div>

        <div class="assistant-list">
            @php
                $assistantDefaultTexts = collect($activeStage['assistants'] ?? [])
                    ->mapWithKeys(fn (array $assistantDefinition) => [
                        $assistantDefinition['name'] => $assistantDefinition['default_extra_context'] ?? '',
                    ]);
            @endphp

            @forelse ($assistants as $assistant)
                <label class="assistant-option {{ $assistant->id_ai === $selectedAssistantId ? 'is-selected' : '' }}">
                    <input
                        type="radio"
                        name="assistant_picker"
                        value="{{ $assistant->id_ai }}"
                        data-ai-name="{{ e($assistant->nama_ai) }}"
                        data-ai-description="{{ e($assistant->deskripsi_ai) }}"
                        data-default-extra-context="{{ e($assistantDefaultTexts[$assistant->nama_ai] ?? '') }}"
                        {{ $assistant->id_ai === $selectedAssistantId ? 'checked' : '' }}
                    >
                    <span class="assistant-option-copy">
                        <strong>{{ $assistant->nama_ai }}</strong>
                        <span>{{ $assistant->deskripsi_ai }}</span>
                    </span>
                </label>
            @empty
                <div class="assistant-option">
                    <span>
                        <strong>Belum ada assistant</strong>
                        <span>Seed master data BADRUL terlebih dahulu agar assistant untuk sintak ini tersedia.</span>
                    </span>
                </div>
            @endforelse
        </div>

        <div class="assistant-tools">
            <label class="field" for="assistant-extra-context">
                <span class="assistant-field-heading">
                    <span class="assistant-field-label">Tambahkan Prompt Anda (Opsional)</span>
                    <span class="assistant-field-info" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 10V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="12" cy="7.5" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                </span>
                <span class="assistant-field-hint">Lengkapi kebutuhan atau pertanyaan spesifik Anda.</span>
                <textarea id="assistant-extra-context" placeholder="Tambahkan kebutuhan atau pertanyaan Anda..."></textarea>
            </label>

            <div class="assistant-actions">
                <button class="button assistant-launch-button" id="open-chatgpt-assistant" type="button" disabled>Buka AI Assistant</button>
                <span class="assistant-field-hint assistant-launch-note">Setelah ChatGPT terbuka, tempel prompt pada kolom chat dengan Ctrl+V.</span>
                <span class="assistant-field-hint assistant-launch-note" id="assistant-launch-feedback" hidden>Prompt disalin ke clipboard lalu ChatGPT Web dibuka.</span>
            </div>
        </div>
    </aside>
</section>
