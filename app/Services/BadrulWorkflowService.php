<?php

namespace App\Services;

use App\Models\AiAssistant;
use App\Models\ProgressSintak;
use App\Models\Proyek;
use App\Models\Refleksi;
use App\Models\SintakBadrul;
use App\Models\User;
use App\Models\WorkspaceSintak;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BadrulWorkflowService
{
    public function stageDefinitions(): array
    {
        $stages = config('badrul.stages', []);

        uasort($stages, fn (array $left, array $right) => $left['order'] <=> $right['order']);

        return $stages;
    }

    public function stageCodes(): array
    {
        return array_keys($this->stageDefinitions());
    }

    public function stageDefinition(string $code): array
    {
        $definition = $this->stageDefinitions()[$code] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Sintak {$code} tidak terdaftar.");
        }

        return $definition;
    }

    public function meetingOptions(): array
    {
        return config('badrul.meeting_options', []);
    }

    public function materialOptions(): array
    {
        return config('badrul.material_options', []);
    }

    public function materialForMeeting(int $meetingNumber): string
    {
        $materials = array_values($this->materialOptions());

        if ($materials === []) {
            return '-';
        }

        $index = max(0, min(count($materials) - 1, $meetingNumber - 1));

        return $materials[$index] ?? $materials[0];
    }

    public function syncMasterData(): void
    {
        DB::transaction(function (): void {
            foreach ($this->stageDefinitions() as $code => $definition) {
                $stage = SintakBadrul::query()->updateOrCreate(
                    ['kode_sintak' => $code],
                    ['nama_sintak' => $definition['title']],
                );

                $assistantNames = collect($definition['assistants'] ?? [])->pluck('name')->all();

                if ($assistantNames === []) {
                    AiAssistant::query()
                        ->where('id_sintak', $stage->id_sintak)
                        ->delete();
                } else {
                    AiAssistant::query()
                        ->where('id_sintak', $stage->id_sintak)
                        ->whereNotIn('nama_ai', $assistantNames)
                        ->delete();
                }

                foreach ($definition['assistants'] as $assistant) {
                    AiAssistant::query()->updateOrCreate(
                        [
                            'id_sintak' => $stage->id_sintak,
                            'nama_ai' => $assistant['name'],
                        ],
                        [
                            'deskripsi_ai' => $assistant['description'],
                            'prompt_otomatis' => $assistant['prompt'],
                        ],
                    );
                }
            }
        });
    }

    public function createProject(User $user, array $attributes = [], bool $withDemoData = false): Proyek
    {
        $defaults = config('badrul.default_project', []);
        $meetingNumber = (int) ($attributes['pertemuan_ke'] ?? $defaults['pertemuan_ke'] ?? 1);

        $project = $user->proyek()->create([
            'pertemuan_ke' => $meetingNumber,
            'materi' => $this->materialForMeeting($meetingNumber),
            'nama_proyek' => $attributes['nama_proyek'] ?? $defaults['nama_proyek'] ?? 'Proyek BADRUL',
            'deskripsi' => $attributes['deskripsi'] ?? $defaults['deskripsi'] ?? '-',
            'tanggal_buat' => now(),
        ]);

        if ($withDemoData) {
            $this->seedDemoProjectData($project);

            return $project;
        }

        $this->ensureProjectWorkflow($project);

        return $project;
    }

    public function ensureUserProjects(User $user): Collection
    {
        $this->syncMasterData();

        $stages = SintakBadrul::query()
            ->whereIn('kode_sintak', $this->stageCodes())
            ->get()
            ->keyBy('kode_sintak');

        foreach ($this->meetingOptions() as $meetingOption) {
            $meetingNumber = (int) $meetingOption;
            $material = $this->materialForMeeting($meetingNumber);

            $project = $user->proyek()->firstOrCreate(
                ['pertemuan_ke' => $meetingNumber],
                [
                    'materi' => $material,
                    'nama_proyek' => '',
                    'deskripsi' => '',
                    'tanggal_buat' => now(),
                ],
            );

            if ($project->materi !== $material) {
                $project->update(['materi' => $material]);
            }

            $this->ensureProjectWorkflowRecords($project, $stages);
        }

        return $user->proyek()->orderBy('pertemuan_ke')->get();
    }

    public function loginDashboardQuery(User $user): array
    {
        $projects = $this->ensureUserProjects($user)
            ->load(['progressSintak.sintakBadrul', 'workspaceSintak', 'refleksi']);

        if (! $this->projectsHaveMeaningfulActivity($projects)) {
            return [];
        }

        $project = $this->loginLandingProject($projects);

        if (! $project instanceof Proyek) {
            return [];
        }

        return [
            'proyek' => $project->id_proyek,
            'sintak' => $this->currentStageCode($project),
        ];
    }

    public function ensureProjectWorkflow(Proyek $project): void
    {
        $this->syncMasterData();

        $stages = SintakBadrul::query()
            ->whereIn('kode_sintak', $this->stageCodes())
            ->get()
            ->keyBy('kode_sintak');

        $this->ensureProjectWorkflowRecords($project, $stages);
    }

    private function ensureProjectWorkflowRecords(Proyek $project, Collection $stages): void
    {
        foreach ($this->stageCodes() as $index => $code) {
            $stage = $stages->get($code);

            if (! $stage) {
                continue;
            }

            ProgressSintak::query()->firstOrCreate(
                [
                    'id_proyek' => $project->id_proyek,
                    'id_sintak' => $stage->id_sintak,
                ],
                [
                    'status' => 'belum',
                    'terakhir_update' => now(),
                ],
            );
        }
    }

    public function seedDemoProjectData(Proyek $project): void
    {
        $this->syncMasterData();

        $stages = SintakBadrul::query()
            ->whereIn('kode_sintak', $this->stageCodes())
            ->get()
            ->keyBy('kode_sintak');

        $demoProgress = config('badrul.demo_progress', []);

        foreach ($this->stageDefinitions() as $code => $definition) {
            $stage = $stages->get($code);

            if (! $stage) {
                continue;
            }

            ProgressSintak::query()->updateOrCreate(
                [
                    'id_proyek' => $project->id_proyek,
                    'id_sintak' => $stage->id_sintak,
                ],
                [
                    'status' => $demoProgress[$code] ?? 'belum',
                    'terakhir_update' => now(),
                ],
            );

            $payload = $definition['demo_payload'] ?? [];

            if ($payload !== []) {
                WorkspaceSintak::query()->updateOrCreate(
                    [
                        'id_proyek' => $project->id_proyek,
                        'id_sintak' => $stage->id_sintak,
                    ],
                    [
                        'judul_file' => $definition['title'],
                        'isi_field' => $this->encodeWorkspace($payload),
                        'tanggal_update' => now(),
                    ],
                );
            }
        }

        Refleksi::query()->updateOrCreate(
            ['id_proyek' => $project->id_proyek],
            [
                'isi_refleksi' => config('badrul.demo_reflection'),
                'tanggal_refleksi' => now(),
            ],
        );
    }

    public function stageModel(string $code): ?SintakBadrul
    {
        return SintakBadrul::query()->where('kode_sintak', $code)->first();
    }

    public function stageWorkspace(Proyek $project, string $code): ?WorkspaceSintak
    {
        $stage = $this->stageModel($code);

        if (! $stage) {
            return null;
        }

        return WorkspaceSintak::query()
            ->where('id_proyek', $project->id_proyek)
            ->where('id_sintak', $stage->id_sintak)
            ->first();
    }

    public function workspaceValues(?WorkspaceSintak $workspace): array
    {
        if (! $workspace || ! $workspace->isi_field) {
            return [];
        }

        $decoded = json_decode($workspace->isi_field, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function currentStageCode(Proyek $project, ?string $requestedCode = null): string
    {
        if ($requestedCode && in_array($requestedCode, $this->stageCodes(), true)) {
            return $requestedCode;
        }

        $progressRows = $project->progressSintak()
            ->with('sintakBadrul')
            ->get()
            ->sortBy(fn (ProgressSintak $progress) => $this->stageDefinition($progress->sintakBadrul->kode_sintak)['order']);

        $current = $progressRows->firstWhere('status', 'proses')
            ?? $progressRows->firstWhere('status', 'belum')
            ?? $progressRows->last();

        return $current?->sintakBadrul?->kode_sintak ?? $this->stageCodes()[0];
    }

    public function inProgressStageCode(Proyek $project): ?string
    {
        $progressRows = $project->progressSintak()
            ->with('sintakBadrul')
            ->get()
            ->sortBy(fn (ProgressSintak $progress) => $this->stageDefinition($progress->sintakBadrul->kode_sintak)['order']);

        return $progressRows->firstWhere('status', 'proses')?->sintakBadrul?->kode_sintak;
    }

    public function startStage(Proyek $project, string $requestedCode): string
    {
        if (! in_array($requestedCode, $this->stageCodes(), true)) {
            throw new InvalidArgumentException("Sintak {$requestedCode} tidak terdaftar.");
        }

        $startableCode = $this->currentStageCode($project);
        $codeToStart = $requestedCode;

        $progressRows = $project->progressSintak()
            ->with('sintakBadrul')
            ->get()
            ->keyBy(fn (ProgressSintak $progress) => $progress->sintakBadrul->kode_sintak);

        $requestedProgress = $progressRows->get($requestedCode);

        if (($requestedProgress?->status ?? 'belum') === 'belum' && $requestedCode !== $startableCode) {
            $codeToStart = $startableCode;
        }

        $progress = $progressRows->get($codeToStart);

        if (! $progress) {
            throw new InvalidArgumentException("Progress sintak {$codeToStart} tidak ditemukan.");
        }

        if ($progress->status === 'belum') {
            $progress->update([
                'status' => 'proses',
                'terakhir_update' => now(),
            ]);
        }

        return $codeToStart;
    }

    public function stageCards(Proyek $project): array
    {
        $progressByCode = $project->progressSintak()
            ->with('sintakBadrul')
            ->get()
            ->mapWithKeys(fn (ProgressSintak $progress) => [$progress->sintakBadrul->kode_sintak => $progress]);

        $cards = [];

        foreach ($this->stageDefinitions() as $code => $definition) {
            $progress = $progressByCode->get($code);

            $cards[] = [
                'code' => $code,
                'title' => $definition['title'],
                'summary' => $definition['summary'],
                'description' => $definition['description'],
                'color' => $definition['color'],
                'status' => $progress?->status ?? 'belum',
                'updated_at' => $progress?->terakhir_update,
            ];
        }

        return $cards;
    }

    public function assistantsForStage(string $code): Collection
    {
        $stage = $this->stageModel($code);

        if (! $stage) {
            return collect();
        }

        $assistantOrder = collect($this->stageDefinition($code)['assistants'] ?? [])
            ->pluck('name')
            ->flip();

        return AiAssistant::query()
            ->where('id_sintak', $stage->id_sintak)
            ->get()
            ->sortBy(fn (AiAssistant $assistant) => $assistantOrder->get($assistant->nama_ai, PHP_INT_MAX))
            ->values();
    }

    public function promptPreview(Proyek $project, string $code, ?AiAssistant $assistant = null): string
    {
        $assistant ??= $this->assistantsForStage($code)->first();

        if (! $assistant) {
            return '';
        }

        return strtr($assistant->prompt_otomatis, [
            ':nama_proyek' => $project->displayName(),
            ':materi' => $project->materi,
            ':deskripsi' => $project->displayDescription(),
            ':sintak' => $this->stageDefinition($code)['title'],
        ]);
    }

    public function updateProject(Proyek $project, array $payload): Proyek
    {
        $meetingNumber = (int) $payload['pertemuan_ke'];

        $project->update([
            'nama_proyek' => $payload['nama_proyek'],
            'pertemuan_ke' => $meetingNumber,
            'materi' => $this->materialForMeeting($meetingNumber),
            'deskripsi' => $payload['deskripsi'],
        ]);

        return $project->fresh();
    }

    public function saveStage(Proyek $project, string $code, array $payload, bool $advance = true): string
    {
        $stage = $this->stageModel($code);

        if (! $stage) {
            throw new InvalidArgumentException("Sintak {$code} tidak ditemukan.");
        }

        WorkspaceSintak::query()->updateOrCreate(
            [
                'id_proyek' => $project->id_proyek,
                'id_sintak' => $stage->id_sintak,
            ],
            [
                'judul_file' => $this->stageDefinition($code)['title'],
                'isi_field' => $this->encodeWorkspace($payload),
                'tanggal_update' => now(),
            ],
        );

        if ($code === 'L' && filled($payload['refleksi_pembelajaran'] ?? null)) {
            Refleksi::query()->updateOrCreate(
                ['id_proyek' => $project->id_proyek],
                [
                    'isi_refleksi' => $payload['refleksi_pembelajaran'],
                    'tanggal_refleksi' => now(),
                ],
            );
        }

        $progressRows = $project->progressSintak()
            ->with('sintakBadrul')
            ->get()
            ->keyBy(fn (ProgressSintak $progress) => $progress->sintakBadrul->kode_sintak);

        $current = $progressRows->get($code);

        if ($current && $current->status !== 'selesai') {
            $current->update([
                'status' => $advance ? 'selesai' : 'proses',
                'terakhir_update' => now(),
            ]);
        }

        $nextCode = $code;

        if ($advance) {
            $orderedCodes = $this->stageCodes();
            $currentIndex = array_search($code, $orderedCodes, true);
            $nextCode = $orderedCodes[$currentIndex + 1] ?? $code;

            $next = $progressRows->get($nextCode);

            if ($next && $next->status === 'belum') {
                $next->update([
                    'status' => 'proses',
                    'terakhir_update' => now(),
                ]);
            }
        }

        return $nextCode;
    }

    public function analytics(Proyek $project): array
    {
        $progressRows = $project->progressSintak()->with('sintakBadrul')->get();

        $total = max($progressRows->count(), 1);
        $stageWeight = 100 / $total;
        $completed = $progressRows->where('status', 'selesai')->count();
        $weightedProgress = $progressRows->reduce(function (float $carry, $progress) use ($stageWeight): float {
            return $carry + match ($progress->status) {
                'selesai' => $stageWeight,
                'proses' => $stageWeight / 2,
                default => 0.0,
            };
        }, 0.0);
        $currentProcess = $progressRows->firstWhere('status', 'proses');
        $suggested = $currentProcess
            ?? $progressRows->firstWhere('status', 'belum')
            ?? $progressRows->last();
        $workspaceCount = $project->workspaceSintak()->count();
        $reflectionCount = $project->refleksi()->count();
        $hasStarted = $progressRows->contains(fn (ProgressSintak $progress) => $progress->status !== 'belum')
            || $workspaceCount > 0
            || $reflectionCount > 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => round(min($weightedProgress, 100), 2),
            'current_stage' => $currentProcess?->sintakBadrul?->kode_sintak,
            'suggested_stage' => $suggested?->sintakBadrul?->kode_sintak,
            'has_started' => $hasStarted,
            'workspace_count' => $workspaceCount,
            'reflection_count' => $reflectionCount,
            'last_activity' => $progressRows
                ->filter(fn (ProgressSintak $progress) => $progress->status !== 'belum')
                ->sortByDesc('terakhir_update')
                ->first()?->terakhir_update,
        ];
    }

    private function loginLandingProject(Collection $projects): ?Proyek
    {
        $inProgressProject = $projects
            ->filter(fn (Proyek $project) => $this->projectHasInProgressStage($project))
            ->sortByDesc(fn (Proyek $project) => $this->projectActivityScore($project))
            ->first();

        if ($inProgressProject instanceof Proyek) {
            return $inProgressProject;
        }

        $lastMeaningfulProject = $projects
            ->filter(fn (Proyek $project) => $this->projectHasMeaningfulActivity($project))
            ->sortByDesc(fn (Proyek $project) => $this->projectActivityScore($project))
            ->first();

        if (! $lastMeaningfulProject instanceof Proyek) {
            return null;
        }

        if (! $this->projectIsCompleted($lastMeaningfulProject)) {
            return $lastMeaningfulProject;
        }

        $nextProject = $projects
            ->where('pertemuan_ke', '>', (int) $lastMeaningfulProject->pertemuan_ke)
            ->sortBy('pertemuan_ke')
            ->first();

        return $nextProject instanceof Proyek ? $nextProject : $lastMeaningfulProject;
    }

    private function projectsHaveMeaningfulActivity(Collection $projects): bool
    {
        return $projects->contains(fn (Proyek $project) => $this->projectHasMeaningfulActivity($project));
    }

    private function projectHasMeaningfulActivity(Proyek $project): bool
    {
        $project->loadMissing(['progressSintak', 'workspaceSintak', 'refleksi']);

        return $project->progressSintak->contains(fn ($progress) => $progress->status !== 'belum')
            || $project->workspaceSintak->isNotEmpty()
            || $project->refleksi->isNotEmpty();
    }

    private function projectHasInProgressStage(Proyek $project): bool
    {
        $project->loadMissing('progressSintak');

        return $project->progressSintak->contains(fn ($progress) => $progress->status === 'proses');
    }

    private function projectIsCompleted(Proyek $project): bool
    {
        $project->loadMissing('progressSintak');

        return $project->progressSintak->isNotEmpty()
            && $project->progressSintak->every(fn ($progress) => $progress->status === 'selesai');
    }

    private function projectActivityScore(Proyek $project): int
    {
        $project->loadMissing(['progressSintak', 'workspaceSintak', 'refleksi']);

        $progressTimestamp = (int) $project->progressSintak
            ->filter(fn ($progress) => $progress->status !== 'belum')
            ->max(fn ($progress) => $progress->terakhir_update?->getTimestamp() ?? 0);
        $workspaceTimestamp = (int) $project->workspaceSintak
            ->max(fn ($workspace) => $workspace->tanggal_update?->getTimestamp() ?? 0);
        $reflectionTimestamp = (int) $project->refleksi
            ->max(fn ($reflection) => $reflection->tanggal_refleksi?->getTimestamp() ?? 0);

        return (max($progressTimestamp, $workspaceTimestamp, $reflectionTimestamp) * 100) + (int) $project->pertemuan_ke;
    }

    public function legacyAutoStartedProjectsForUser(User $user, bool $forceSingle = false): Collection
    {
        $user->loadMissing([
            'proyek.progressSintak.sintakBadrul',
            'proyek.workspaceSintak',
            'proyek.refleksi',
        ]);

        $candidates = $user->proyek
            ->filter(fn (Proyek $project) => $this->isLegacyAutoStartedProject($project))
            ->values();

        if (! $forceSingle && $candidates->count() < 2) {
            return collect();
        }

        return $candidates;
    }

    public function normalizeLegacyAutoStartedProjectsForUser(
        User $user,
        bool $forceSingle = false,
        bool $persist = true,
    ): array {
        $candidates = $this->legacyAutoStartedProjectsForUser($user, $forceSingle);

        if ($persist) {
            $candidates->each(fn (Proyek $project) => $this->resetProjectToNotStarted($project));
        }

        return [
            'user_id' => $user->id_user,
            'username' => $user->username,
            'candidate_count' => $candidates->count(),
            'normalized_count' => $persist ? $candidates->count() : 0,
            'project_ids' => $candidates->pluck('id_proyek')->all(),
            'meetings' => $candidates->pluck('pertemuan_ke')->all(),
        ];
    }

    public function latestReflection(Proyek $project): ?Refleksi
    {
        return $project->refleksi()->latest('tanggal_refleksi')->first();
    }

    public function saveReflection(Proyek $project, string $content): Refleksi
    {
        return Refleksi::query()->updateOrCreate(
            ['id_proyek' => $project->id_proyek],
            [
                'isi_refleksi' => $content,
                'tanggal_refleksi' => now(),
            ],
        );
    }

    public function helpCards(): array
    {
        return config('badrul.help_cards', []);
    }

    private function encodeWorkspace(array $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function isLegacyAutoStartedProject(Proyek $project): bool
    {
        $project->loadMissing([
            'progressSintak.sintakBadrul',
            'workspaceSintak',
            'refleksi',
        ]);

        if ($project->workspaceSintak->isNotEmpty() || $project->refleksi->isNotEmpty()) {
            return false;
        }

        $progressByCode = $project->progressSintak
            ->filter(fn (ProgressSintak $progress) => $progress->sintakBadrul !== null)
            ->keyBy(fn (ProgressSintak $progress) => $progress->sintakBadrul->kode_sintak);

        $beginProgress = $progressByCode->get('B');

        if (! $beginProgress || $beginProgress->status !== 'proses') {
            return false;
        }

        if ($progressByCode->contains(fn (ProgressSintak $progress) => $progress->status === 'selesai')) {
            return false;
        }

        return $progressByCode
            ->reject(fn (ProgressSintak $progress, string $code) => $code === 'B')
            ->every(fn (ProgressSintak $progress) => $progress->status === 'belum');
    }

    private function resetProjectToNotStarted(Proyek $project): void
    {
        $project->loadMissing('progressSintak');

        $resetTimestamp = $project->tanggal_buat ?? now();

        foreach ($project->progressSintak as $progress) {
            $progress->update([
                'status' => 'belum',
                'terakhir_update' => $resetTimestamp,
            ]);
        }
    }
}