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

    public function ensureProjectWorkflow(Proyek $project): void
    {
        $this->syncMasterData();

        $stages = SintakBadrul::query()
            ->whereIn('kode_sintak', $this->stageCodes())
            ->get()
            ->keyBy('kode_sintak');

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
                    'status' => $index === 0 ? 'proses' : 'belum',
                    'terakhir_update' => now(),
                ],
            );
        }

        if (! $project->progressSintak()->where('status', 'proses')->exists()) {
            $firstPending = $project->progressSintak()
                ->where('status', 'belum')
                ->orderBy('id_progress')
                ->first();

            if ($firstPending) {
                $firstPending->update([
                    'status' => 'proses',
                    'terakhir_update' => now(),
                ]);
            }
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
            ':nama_proyek' => $project->nama_proyek,
            ':materi' => $project->materi,
            ':deskripsi' => $project->deskripsi,
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
        $current = $progressRows->firstWhere('status', 'proses')
            ?? $progressRows->firstWhere('status', 'belum')
            ?? $progressRows->last();
        $workspaceCount = $project->workspaceSintak()->count();
        $reflectionCount = $project->refleksi()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => round(min($weightedProgress, 100), 2),
            'current_stage' => $current?->sintakBadrul?->kode_sintak,
            'workspace_count' => $workspaceCount,
            'reflection_count' => $reflectionCount,
            'last_activity' => $progressRows->sortByDesc('terakhir_update')->first()?->terakhir_update,
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
}