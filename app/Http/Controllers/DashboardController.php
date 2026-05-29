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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly BadrulWorkflowService $workflow)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        return $this->renderPage($request, 'dashboard');
    }

    public function sintak(Request $request): View|RedirectResponse
    {
        return $this->renderPage($request, 'sintak');
    }

    public function progress(Request $request): View|RedirectResponse
    {
        return $this->renderPage($request, 'progress');
    }

    public function help(Request $request): View|RedirectResponse
    {
        return $this->renderPage($request, 'help');
    }

    public function users(Request $request): View
    {
        return $this->renderUsersPage($this->authorizeAdmin($request));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate(
            $this->userRules(),
            $this->userValidationMessages(),
        );

        User::create($validated);

        return redirect()
            ->route('dashboard.users')
            ->with('status', 'User baru berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $managedUser): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate(
            $this->userRules($managedUser),
            $this->userValidationMessages(),
        );

        if (($validated['password'] ?? null) === null || ($validated['password'] ?? '') === '') {
            unset($validated['password']);
        }

        $managedUser->update($validated);

        return redirect()
            ->route('dashboard.users')
            ->with('status', 'Data user berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $managedUser): RedirectResponse
    {
        $admin = $this->authorizeAdmin($request);

        if ($managedUser->id_user === $admin->id_user) {
            return redirect()
                ->route('dashboard.users')
                ->withErrors(['user' => 'Akun admin yang sedang aktif tidak dapat dihapus.']);
        }

        $managedUser->delete();

        return redirect()
            ->route('dashboard.users')
            ->with('status', 'User berhasil dihapus.');
    }

    public function createProject(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate(
            $this->projectRules($user),
            $this->projectValidationMessages(),
            $this->projectValidationAttributes(),
        );

        $attributes = [
            'nama_proyek' => $validated['nama_proyek'],
            'deskripsi' => $validated['deskripsi'],
        ];

        if (array_key_exists('pertemuan_ke', $validated)) {
            $attributes['pertemuan_ke'] = (int) $validated['pertemuan_ke'];
        }

        $project = $this->workflow->createProject($user, $attributes);

        return $this->redirectToPage(
            $this->normalizePage($validated['page'] ?? 'sintak', 'sintak'),
            $project,
            'B',
            'Proyek baru berhasil dibuat. Lanjutkan pengisian dan mulai dari sintak B.',
        );
    }

    public function updateProject(Request $request, Proyek $proyek): RedirectResponse
    {
        $this->authorizeProject($request, $proyek);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate(
            array_merge($this->projectRules($user, $proyek), [
                'sintak' => ['nullable', 'string', 'size:1'],
            ]),
            $this->projectValidationMessages(),
            $this->projectValidationAttributes(),
        );

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

    private function renderPage(Request $request, string $page): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $pageMeta = $this->pageMeta($page);

        $projects = $this->workflow->ensureUserProjects($user);
        $availableMeetingOptions = $this->availableMeetingOptions($projects);
        $selectedMeeting = $this->selectedMeetingFromRequest($request);
        $selectedMaterial = $this->selectedMaterialFromRequest($request, $selectedMeeting);

        $selectedProject = null;

        if ($page === 'sintak' && ! $request->has('proyek') && $selectedMeeting !== null) {
            $selectedProject = $projects->firstWhere('pertemuan_ke', $selectedMeeting);
        }

        $currentProject = $selectedProject instanceof Proyek
            ? $selectedProject
            : $this->resolveProject($request, $projects);

        $activeStageCode = $this->workflow->currentStageCode(
            $currentProject,
            $request->string('sintak')->toString(),
        );
        $activeStage = $this->workflow->stageDefinition($activeStageCode);
        $workspaceValues = $this->workflow->workspaceValues(
            $this->workflow->stageWorkspace($currentProject, $activeStageCode),
        );
        $stageCards = $this->workflow->stageCards($currentProject);
        $assistants = $this->workflow->assistantsForStage($activeStageCode);

        $currentProject->load([
            'progressSintak.sintakBadrul',
            'workspaceSintak.sintakBadrul',
            'refleksi',
        ]);

        $selectedAssistant = $this->resolveAssistant($request, $assistants);
        $analytics = $this->workflow->analytics($currentProject);
        $latestReflection = $this->workflow->latestReflection($currentProject);
        $progressPercent = round(max(0, min(100, (float) ($analytics['percentage'] ?? 0))), 2);
        $progressPercentLabel = abs($progressPercent - floor($progressPercent)) < 0.001
            ? number_format($progressPercent, 0, ',', '.')
            : number_format($progressPercent, 2, ',', '.');
        $reflectionStageCode = $this->reflectionStageCode($stageCards, $activeStageCode);

        return view('dashboard', [
            'page' => $page,
            'user' => $user,
            'userRoleLabel' => $this->userRoleLabel($user),
            'projects' => $projects,
            'currentProject' => $currentProject,
            'meetingOptions' => $this->workflow->meetingOptions(),
            'availableMeetingOptions' => $availableMeetingOptions,
            'materialOptions' => $this->workflow->materialOptions(),
            'selectedMeeting' => $selectedMeeting,
            'selectedMaterial' => $selectedMaterial,
            'selectedProjectMissing' => false,
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
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    private function renderPageWithoutProject(
        User $user,
        Collection $projects,
        string $page,
        array $pageMeta,
        array $availableMeetingOptions,
        ?int $selectedMeeting = null,
        ?string $selectedMaterial = null,
        bool $selectedProjectMissing = false,
    ): View
    {
        $activeStageCode = 'B';
        $activeStage = $this->workflow->stageDefinition($activeStageCode);

        return view('dashboard', [
            'page' => $page,
            'user' => $user,
            'userRoleLabel' => $this->userRoleLabel($user),
            'projects' => $projects,
            'currentProject' => null,
            'meetingOptions' => $this->workflow->meetingOptions(),
            'availableMeetingOptions' => $availableMeetingOptions,
            'materialOptions' => $this->workflow->materialOptions(),
            'selectedMeeting' => $selectedMeeting,
            'selectedMaterial' => $selectedMaterial,
            'selectedProjectMissing' => $selectedProjectMissing,
            'stageCards' => [],
            'activeStageCode' => $activeStageCode,
            'activeStage' => $activeStage,
            'workspaceValues' => [],
            'assistants' => collect(),
            'selectedAssistantId' => null,
            'analytics' => [
                'total' => count($this->workflow->stageCodes()),
                'completed' => 0,
                'percentage' => 0,
                'current_stage' => $activeStageCode,
                'workspace_count' => 0,
                'reflection_count' => 0,
                'last_activity' => null,
            ],
            'latestReflection' => null,
            'helpCards' => $this->workflow->helpCards(),
            'headerTitle' => $pageMeta['title'],
            'headerDescription' => $pageMeta['description'],
            'menuQuery' => [],
            'progressPercent' => 0,
            'progressPercentLabel' => '0',
            'reflectionSuggestions' => [],
            'reflectionAssistantPrompt' => '',
            'dashboardSummary' => [
                'currentStageLabel' => "{$activeStageCode} - {$activeStage['title']}",
                'recentStageCards' => collect(),
                'latestReflectionPreview' => 'Belum ada refleksi yang disimpan. Gunakan menu Refleksi & Progress untuk mulai mencatat pembelajaran.',
            ],
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    private function renderUsersPage(User $user): View
    {
        $page = 'users';
        $pageMeta = $this->pageMeta($page);

        return view('dashboard', [
            'page' => $page,
            'user' => $user,
            'userRoleLabel' => $this->userRoleLabel($user),
            'projects' => collect(),
            'currentProject' => null,
            'meetingOptions' => $this->workflow->meetingOptions(),
            'availableMeetingOptions' => [],
            'materialOptions' => $this->workflow->materialOptions(),
            'selectedMeeting' => null,
            'selectedMaterial' => null,
            'selectedProjectMissing' => false,
            'stageCards' => [],
            'activeStageCode' => '',
            'activeStage' => [],
            'workspaceValues' => [],
            'assistants' => collect(),
            'selectedAssistantId' => null,
            'analytics' => [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'current_stage' => null,
                'workspace_count' => 0,
                'reflection_count' => 0,
                'last_activity' => null,
            ],
            'latestReflection' => null,
            'helpCards' => $this->workflow->helpCards(),
            'headerTitle' => $pageMeta['title'],
            'headerDescription' => $pageMeta['description'],
            'menuQuery' => [],
            'progressPercent' => 0,
            'progressPercentLabel' => '0',
            'reflectionSuggestions' => [],
            'reflectionAssistantPrompt' => '',
            'dashboardSummary' => [
                'currentStageLabel' => 'Kelola akun pengguna dari dashboard admin.',
                'recentStageCards' => collect(),
                'latestReflectionPreview' => '',
            ],
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
            'managedUsers' => User::query()->orderBy('role')->orderBy('nama')->get(),
            'roleOptions' => $this->userRoleOptions(),
        ]);
    }

    private function selectedMeetingFromRequest(Request $request): ?int
    {
        $selectedMeeting = $request->integer('pertemuan_ke');

        if ($selectedMeeting >= 1 && $selectedMeeting <= 14) {
            return $selectedMeeting;
        }

        $selectedMaterial = trim($request->string('materi')->toString());

        if ($selectedMaterial === '') {
            return null;
        }

        $materialIndex = array_search($selectedMaterial, $this->workflow->materialOptions(), true);

        if ($materialIndex === false) {
            return null;
        }

        return $materialIndex + 1;
    }

    private function selectedMaterialFromRequest(Request $request, ?int $selectedMeeting): ?string
    {
        if ($selectedMeeting !== null) {
            return $this->workflow->materialForMeeting($selectedMeeting);
        }

        $selectedMaterial = trim($request->string('materi')->toString());

        return $selectedMaterial !== '' ? $selectedMaterial : null;
    }

    private function projectRules(User $user, ?Proyek $ignoreProject = null): array
    {
        $meetingRule = Rule::unique('proyek', 'pertemuan_ke')
            ->where(fn ($query) => $query->where('id_user', $user->id_user));

        if ($ignoreProject) {
            $meetingRule = $meetingRule->ignore($ignoreProject->id_proyek, 'id_proyek');
        }

        return [
            'page' => ['nullable', 'string'],
            'nama_proyek' => ['required', 'string', 'max:200'],
            'deskripsi' => ['required', 'string'],
            'pertemuan_ke' => ['required', 'integer', 'min:1', 'max:14', $meetingRule],
        ];
    }

    private function projectValidationMessages(): array
    {
        return [
            'pertemuan_ke.unique' => 'Anda sudah memiliki proyek BADRUL untuk pertemuan ini. Pilih pertemuan lain.',
        ];
    }

    private function projectValidationAttributes(): array
    {
        return [
            'pertemuan_ke' => 'pertemuan',
        ];
    }

    private function userRules(?User $managedUser = null): array
    {
        $usernameRule = Rule::unique('users', 'username');

        if ($managedUser) {
            $usernameRule = $usernameRule->ignore($managedUser->id_user, 'id_user');
        }

        return [
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', $usernameRule],
            'password' => $managedUser
                ? ['nullable', 'string', 'min:8', 'max:100']
                : ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', 'string', Rule::in($this->userRoleOptions())],
            'prodi' => ['required', 'string', 'max:100'],
        ];
    }

    private function userValidationMessages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan pengguna lain.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    private function userRoleOptions(): array
    {
        return ['admin', 'dosen', 'mahasiswa'];
    }

    private function availableMeetingOptions(Collection $projects): array
    {
        $usedMeetings = $projects
            ->pluck('pertemuan_ke')
            ->map(static fn (mixed $meeting) => (int) $meeting)
            ->unique()
            ->all();

        return array_values(array_filter(
            $this->workflow->meetingOptions(),
            static fn (mixed $meetingOption) => ! in_array((int) $meetingOption, $usedMeetings, true),
        ));
    }

    private function pageMeta(string $page): array
    {
        return match ($page) {
            'dashboard' => [
                'title' => 'Dashboard',
                'description' => 'Pantau proyek aktif, progress BADRUL, dan fokus pembelajaran utama Anda dari halaman dashboard.',
            ],
            'sintak' => [
                'title' => 'Artificial Intelligence Learning System - BADRUL',
                'description' => 'Kelola ruang kerja proyek pada setiap tahapan BADRUL dan gunakan AI Assistant yang dibutuhkan.',
            ],
            'progress' => [
                'title' => 'Refleksi & Progress',
                'description' => 'Pantau perkembangan pada setiap tahapan sintak BADRUL dan tuliskan refleksi untuk peningkatan pembelajaran.',
            ],
            'help' => [
                'title' => 'Bantuan',
                'description' => 'Pelajari panduan penggunaan AILS BADRUL, alur kerja sintak, dan cara memanfaatkan AI pendamping belajar.',
            ],
            'users' => [
                'title' => 'Kelola User',
                'description' => 'Tambahkan, perbarui, dan hapus akun pengguna langsung dari dashboard admin.',
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
        return 'Bantu saya merefleksikan perkembangan pembelajaran proyek "'.$currentProject->displayName().'" berdasarkan progres sintak dan kendala yang saya alami. Mengapa saya masih kesulitan pada beberapa bagian, apa penyebabnya, dan langkah perbaikan apa yang sebaiknya saya lakukan selanjutnya?';
    }

    private function statusLabels(): array
    {
        return [
            'selesai' => 'Selesai',
            'proses' => 'Proses',
            'belum' => 'Belum',
        ];
    }

    private function statusColors(): array
    {
        return [
            'selesai' => 'var(--success)',
            'proses' => 'var(--warning)',
            'belum' => 'var(--pending)',
        ];
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

    private function authorizeAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->role === 'admin', 403);

        return $user;
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
            'dashboard', 'sintak', 'progress', 'help', 'users' => $page,
            default => $fallback,
        };
    }

    private function pageRouteName(string $page): string
    {
        return match ($page) {
            'sintak' => 'dashboard.sintak',
            'progress' => 'dashboard.progress',
            'help' => 'dashboard.help',
            'users' => 'dashboard.users',
            default => 'dashboard',
        };
    }

    private function resolveProject(Request $request, Collection $projects): Proyek
    {
        $selectedProjectId = $request->integer('proyek');

        if ($selectedProjectId > 0) {
            $project = $projects->firstWhere('id_proyek', $selectedProjectId);

            if ($project) {
                return $project;
            }
        }

        $project = $projects->first();

        abort_unless($project instanceof Proyek, 404);

        return $project;
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