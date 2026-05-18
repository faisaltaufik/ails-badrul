<?php

namespace App\Http\Controllers;

use App\Models\AiAssistant;
use App\Models\Proyek;
use App\Models\User;
use App\Services\BadrulWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly BadrulWorkflowService $workflow)
    {
    }

    public function index(Request $request): View
    {
        return $this->renderPage($request, 'dashboard');
    }

    public function sintak(Request $request): View
    {
        return $this->renderPage($request, 'sintak');
    }

    public function progress(Request $request): View
    {
        return $this->renderPage($request, 'progress');
    }

    public function help(Request $request): View
    {
        return $this->renderPage($request, 'help');
    }

    public function createProject(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $project = $this->workflow->createProject($user, [
            'nama_proyek' => 'Proyek BADRUL Baru',
        ]);

        return $this->redirectToPage(
            $this->normalizePage($request->string('page')->toString()),
            $project,
            'B',
            'Proyek baru berhasil dibuat. Lengkapi profil proyek dan mulai dari sintak B.',
        );
    }

    public function updateProject(Request $request, Proyek $proyek): RedirectResponse
    {
        $this->authorizeProject($request, $proyek);

        $validated = $request->validate([
            'nama_proyek' => ['required', 'string', 'max:200'],
            'pertemuan_ke' => ['required', 'integer', 'min:1', 'max:14'],
            'deskripsi' => ['required', 'string'],
            'sintak' => ['nullable', 'string', 'size:1'],
            'page' => ['nullable', 'string'],
        ]);

        $this->workflow->updateProject($proyek, $validated);

        return $this->redirectToPage(
            $this->normalizePage($validated['page'] ?? 'dashboard'),
            $proyek,
            $validated['sintak'] ?? $this->workflow->currentStageCode($proyek->fresh()),
            'Profil proyek berhasil diperbarui.',
        );
    }

    public function updateStage(Request $request, Proyek $proyek, string $sintak): RedirectResponse
    {
        $this->authorizeProject($request, $proyek);

        $definition = $this->workflow->stageDefinition($sintak);
        $rules = [];

        foreach ($definition['fields'] as $field) {
            $rules[$field['name']] = [
                $field['required'] ? 'required' : 'nullable',
                ($field['type'] ?? 'textarea') === 'file' ? 'file' : 'string',
            ];

            if (($field['type'] ?? 'textarea') === 'file') {
                $rules[$field['name']][] = 'max:5120';
            }
        }

        $rules['advance'] = ['nullable', 'boolean'];
        $rules['page'] = ['nullable', 'string'];

        $validated = $request->validate($rules);
        $existingWorkspaceValues = $this->workflow->workspaceValues(
            $this->workflow->stageWorkspace($proyek, $sintak),
        );
        $payload = [];
        $projectUpdates = [];

        foreach ($definition['fields'] as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'] ?? 'textarea';

            if ($fieldType === 'file') {
                if ($request->hasFile($fieldName)) {
                    $uploadedFile = $request->file($fieldName);

                    if ($uploadedFile) {
                        $payload[$fieldName] = [
                            'name' => $uploadedFile->getClientOriginalName(),
                            'path' => $uploadedFile->store("badrul-workspace/{$proyek->id_proyek}/{$sintak}", 'public'),
                        ];
                    }

                    continue;
                }

                if (array_key_exists($fieldName, $existingWorkspaceValues)) {
                    $payload[$fieldName] = $existingWorkspaceValues[$fieldName];
                }

                continue;
            }

            $value = (string) ($validated[$fieldName] ?? '');

            if (isset($field['project_attribute'])) {
                $projectUpdates[$field['project_attribute']] = $value;
            }

            if ($field['persist_to_workspace'] ?? true) {
                $payload[$fieldName] = $value;
            }
        }

        if ($projectUpdates !== []) {
            $proyek->update($projectUpdates);
            $proyek = $proyek->fresh();
        }

        $nextStage = $this->workflow->saveStage(
            $proyek,
            $sintak,
            $payload,
            $request->boolean('advance', true),
        );

        return $this->redirectToPage(
            $this->normalizePage($validated['page'] ?? 'sintak', 'sintak'),
            $proyek,
            $nextStage,
            "Sintak {$sintak} berhasil disimpan.",
        );
    }

    public function updateReflection(Request $request, Proyek $proyek): RedirectResponse
    {
        $this->authorizeProject($request, $proyek);

        $validated = $request->validate([
            'isi_refleksi' => ['required', 'string'],
            'sintak' => ['nullable', 'string', 'size:1'],
            'page' => ['nullable', 'string'],
        ]);

        $this->workflow->saveReflection($proyek, $validated['isi_refleksi']);

        $freshProject = $proyek->fresh();

        return $this->redirectToPage(
            $this->normalizePage($validated['page'] ?? 'progress', 'progress'),
            $freshProject,
            $validated['sintak'] ?? $this->workflow->currentStageCode($freshProject),
            'Jurnal refleksi berhasil disimpan.',
        );
    }

    private function renderPage(Request $request, string $page): View
    {
        /** @var User $user */
        $user = $request->user();

        $this->workflow->syncMasterData();

        $projects = $user->proyek()->orderByDesc('tanggal_buat')->get();

        if ($projects->isEmpty()) {
            $this->workflow->createProject($user);
            $projects = $user->proyek()->orderByDesc('tanggal_buat')->get();
        }

        $currentProject = $this->resolveProject($request, $user, $projects);

        $this->workflow->ensureProjectWorkflow($currentProject);

        $currentProject->load([
            'progressSintak.sintakBadrul',
            'workspaceSintak.sintakBadrul',
            'refleksi',
        ]);

        $activeStageCode = $this->workflow->currentStageCode(
            $currentProject,
            $request->string('sintak')->toString() ?: null,
        );

        $activeStage = $this->workflow->stageDefinition($activeStageCode);
        $workspace = $this->workflow->stageWorkspace($currentProject, $activeStageCode);
        $workspaceValues = $this->workflow->workspaceValues($workspace);
        $stageCards = $this->workflow->stageCards($currentProject);
        $reflectionStageCode = $this->reflectionStageCode($stageCards, $activeStageCode);
        $assistants = $this->workflow->assistantsForStage($activeStageCode);
        $selectedAssistant = $this->resolveAssistant($request, $assistants);
        $analytics = $this->workflow->analytics($currentProject);
        $latestReflection = $this->workflow->latestReflection($currentProject);
        $pageMeta = $this->pageMeta($page);
        $progressPercent = round(max(0, min(100, (float) ($analytics['percentage'] ?? 0))), 2);
        $progressPercentLabel = abs($progressPercent - floor($progressPercent)) < 0.001
            ? number_format($progressPercent, 0, ',', '.')
            : number_format($progressPercent, 2, ',', '.');

        return view('dashboard', [
            'page' => $page,
            'user' => $user,
            'userRoleLabel' => $this->userRoleLabel($user),
            'projects' => $projects,
            'currentProject' => $currentProject,
            'meetingOptions' => $this->workflow->meetingOptions(),
            'materialOptions' => $this->workflow->materialOptions(),
            'stageCards' => $stageCards,
            'activeStageCode' => $activeStageCode,
            'activeStage' => $activeStage,
            'workspaceValues' => $workspaceValues,
            'assistants' => $assistants,
            'selectedAssistantId' => $selectedAssistant?->id_ai,
            'analytics' => $analytics,
            'latestReflection' => $latestReflection,
            'helpCards' => $this->workflow->helpCards(),
            'headerTitle' => $pageMeta['title'],
            'headerDescription' => $pageMeta['description'],
            'menuQuery' => $this->menuQuery($currentProject, $activeStageCode),
            'progressPercent' => $progressPercent,
            'progressPercentLabel' => $progressPercentLabel,
            'reflectionSuggestions' => $this->reflectionSuggestions($reflectionStageCode),
            'reflectionAssistantPrompt' => $this->reflectionAssistantPrompt($currentProject),
            'dashboardSummary' => $this->dashboardSummary(
                $currentProject,
                $activeStage,
                $activeStageCode,
                $analytics,
                $stageCards,
                $latestReflection,
            ),
            'statusLabels' => [
                'selesai' => 'Selesai',
                'proses' => 'Proses',
                'belum' => 'Belum',
            ],
            'statusColors' => [
                'selesai' => 'var(--success)',
                'proses' => 'var(--warning)',
                'belum' => 'var(--pending)',
            ],
        ]);
    }

    private function pageMeta(string $page): array
    {
        return match ($page) {
            'dashboard' => [
                'title' => 'Dashboard',
                'description' => 'Pantau proyek aktif, progress BADRUL, dan fokus pembelajaran utama Anda dari halaman dashboard.',
            ],
            'sintak' => [
                'title' => 'Sintaks BADRUL',
                'description' => 'Kelola area kerja pada setiap tahapan BADRUL dan gunakan AI assistant yang sesuai dengan sintak yang sedang aktif.',
            ],
            'progress' => [
                'title' => 'Refleksi & Progress',
                'description' => 'Pantau perkembangan pada setiap tahapan sintak BADRUL dan tuliskan refleksi untuk peningkatan pembelajaran.',
            ],
            'help' => [
                'title' => 'Bantuan',
                'description' => 'Pelajari panduan penggunaan AILS BADRUL, alur kerja sintak, dan cara memanfaatkan AI pendamping belajar.',
            ],
            default => [
                'title' => 'Dashboard',
                'description' => 'AILS siap mendukung pembelajaran proyek Anda pada setiap tahapan BADRUL.',
            ],
        };
    }

    private function userRoleLabel(User $user): string
    {
        return match ($user->role) {
            'admin' => 'Administrator Aktif',
            'dosen' => 'Dosen Aktif',
            default => 'Mahasiswa Aktif',
        };
    }

    private function menuQuery(Proyek $currentProject, string $activeStageCode): array
    {
        $menuQuery = ['proyek' => $currentProject->id_proyek];

        if ($activeStageCode !== '') {
            $menuQuery['sintak'] = $activeStageCode;
        }

        return $menuQuery;
    }

    private function reflectionStageCode(array $stageCards, string $fallbackCode): string
    {
        $processStageCode = collect($stageCards)
            ->first(fn (array $card) => ($card['status'] ?? 'belum') === 'proses')['code'] ?? null;

        return is_string($processStageCode) && $processStageCode !== ''
            ? $processStageCode
            : $fallbackCode;
    }

    private function reflectionSuggestions(string $stageCode): array
    {
        return match ($stageCode) {
            'B' => [
                'Identifikasi permasalahan nyata yang relevan dengan kebutuhan pengguna.',
                'Rumuskan pertanyaan mendasar proyek secara jelas dan terarah.',
                'Gunakan AI Reasoning Assistant untuk membantu menganalisis masalah proyek.',
            ],
            'A' => [
                'Analisis kebutuhan pengguna dan fitur aplikasi secara sistematis.',
                'Susun rencana pengembangan proyek dan timeline pengerjaan dengan baik.',
                'Gunakan AI Planning Assistant untuk membantu perencanaan proyek aplikasi.',
            ],
            'D' => [
                'Pastikan desain GUI sesuai dengan kebutuhan pengguna aplikasi.',
                'Periksa kembali koneksi database dan struktur program sebelum pengujian aplikasi.',
                'Gunakan AI Coding Assistant untuk membantu implementasi dan debugging program.',
            ],
            'R' => [
                'Evaluasi kembali tampilan, fitur, dan fungsi aplikasi yang telah dikembangkan.',
                'Perbaiki kesalahan program dan revisi aplikasi berdasarkan hasil evaluasi.',
                'Gunakan AI Feedback Assistant untuk membantu memperoleh saran perbaikan proyek.',
            ],
            'U' => [
                'Siapkan demonstrasi aplikasi secara terstruktur dan mudah dipahami.',
                'Jelaskan fitur utama aplikasi dengan bahasa yang jelas dan komunikatif.',
                'Gunakan AI Presentation Assistant untuk membantu penyusunan materi presentasi.',
            ],
            default => [
                'Lakukan refleksi terhadap pengalaman belajar dan proses pengembangan proyek.',
                'Identifikasi kesulitan yang dihadapi dan strategi perbaikan pembelajaran berikutnya.',
                'Gunakan AI Reflective Assistant untuk membantu refleksi dan evaluasi diri.',
            ],
        };
    }

    private function reflectionAssistantPrompt(Proyek $currentProject): string
    {
        return 'Bantu saya merefleksikan perkembangan pembelajaran proyek "'.$currentProject->nama_proyek.'" berdasarkan progres sintak dan kendala yang saya alami. Mengapa saya masih kesulitan pada beberapa bagian, apa penyebabnya, dan langkah perbaikan apa yang sebaiknya saya lakukan selanjutnya?';
    }

    private function dashboardSummary(
        Proyek $currentProject,
        array $activeStage,
        string $activeStageCode,
        array $analytics,
        array $stageCards,
        mixed $latestReflection,
    ): array {
        return [
            'currentStageLabel' => $activeStageCode !== ''
                ? "{$activeStageCode} - {$activeStage['title']}"
                : ($analytics['current_stage'] ?? 'Belum ada tahap aktif'),
            'recentStageCards' => collect($stageCards)
                ->sortByDesc(fn (array $card) => $card['updated_at']?->timestamp ?? 0)
                ->values()
                ->take(4),
            'latestReflectionPreview' => Str::limit(
                $latestReflection?->isi_refleksi ?: 'Belum ada refleksi yang disimpan. Gunakan menu Refleksi & Progress untuk mulai mencatat pembelajaran.',
                160,
            ),
        ];
    }

    private function authorizeProject(Request $request, Proyek $proyek): void
    {
        abort_unless($proyek->id_user === $request->user()?->id_user, 403);
    }

    private function redirectToPage(string $page, Proyek $proyek, ?string $sintak, string $status): RedirectResponse
    {
        $parameters = [
            'proyek' => $proyek->id_proyek,
        ];

        if ($sintak) {
            $parameters['sintak'] = $sintak;
        }

        return redirect()
            ->route($this->pageRouteName($page), $parameters)
            ->with('status', $status);
    }

    private function normalizePage(?string $page, string $fallback = 'dashboard'): string
    {
        return match ($page) {
            'dashboard', 'sintak', 'progress', 'help' => $page,
            default => $fallback,
        };
    }

    private function pageRouteName(string $page): string
    {
        return match ($page) {
            'sintak' => 'dashboard.sintak',
            'progress' => 'dashboard.progress',
            'help' => 'dashboard.help',
            default => 'dashboard',
        };
    }

    private function resolveProject(Request $request, User $user, Collection $projects): Proyek
    {
        $selectedProjectId = $request->integer('proyek');

        if ($selectedProjectId > 0) {
            $project = $projects->firstWhere('id_proyek', $selectedProjectId);

            if ($project) {
                return $project;
            }
        }

        return $projects->first() ?? $this->workflow->createProject($user);
    }

    private function resolveAssistant(Request $request, Collection $assistants): ?AiAssistant
    {
        return $this->resolveAssistantById($assistants, $request->integer('assistant'));
    }

    private function resolveAssistantById(Collection $assistants, int $assistantId): ?AiAssistant
    {
        if ($assistantId > 0) {
            $assistant = $assistants->firstWhere('id_ai', $assistantId);

            if ($assistant) {
                return $assistant;
            }
        }

        return null;
    }
}