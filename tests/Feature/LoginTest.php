<?php

use App\Services\BadrulWorkflowService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can open the login page', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('PjBL BADRUL')
        ->assertSee('Masukkan username');

    expect(substr_count($response->getContent(), 'class="form-block"'))->toBe(1);
});

test('user can login with valid username and password', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'admin',
        'password' => 'admin',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('authenticated user can see the dashboard overview page', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('Proyek Aktif dan Pengaturan Pertemuan')
        ->assertSee('Ringkasan Learning Analytics')
        ->assertDontSee('AI Assistant untuk Sintak B');
});

test('authenticated user can open menu pages separately', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.sintak'))
        ->assertOk()
        ->assertSee('Model PjBL Sintaks B A D R U L')
        ->assertSee('AI Assistant untuk Sintak B')
        ->assertSee('id="open-chatgpt-assistant" type="button" disabled', false)
        ->assertSee('Prompt disalin ke clipboard lalu ChatGPT Web dibuka.')
        ->assertDontSee('Prompt lengkap yang akan digunakan');

    $this->actingAs($user)
        ->get(route('dashboard.progress'))
        ->assertOk()
        ->assertSee('Refleksi & Progress')
        ->assertSee('Progress per Sintak BADRUL')
        ->assertSee('Jurnal Refleksi')
        ->assertSee('Saran AI untuk Pembelajaran');

    $this->actingAs($user)
        ->get(route('dashboard.help'))
        ->assertOk()
        ->assertSee('Panduan Penggunaan AILS BADRUL')
        ->assertSee('Ringkasan cara kerja prototipe');
});

test('user can save reflection journal from progress page', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)->get(route('dashboard.progress'));

    $project = $user->fresh()->proyek()->first();

    $response = $this->actingAs($user)->post(route('dashboard.reflection.update', $project), [
        'page' => 'progress',
        'sintak' => 'B',
        'isi_refleksi' => 'Saya mulai memahami hubungan antara analisis masalah, alur fitur, dan kualitas implementasi proyek.',
    ]);

    $response->assertRedirect(route('dashboard.progress', [
        'proyek' => $project->id_proyek,
        'sintak' => 'B',
    ]));

    $this->assertDatabaseHas('refleksi', [
        'id_proyek' => $project->id_proyek,
        'isi_refleksi' => 'Saya mulai memahami hubungan antara analisis masalah, alur fitur, dan kualitas implementasi proyek.',
    ]);
});

test('user can save the current stage and advance to the next sintak', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)->get(route('dashboard'));

    $project = $user->fresh()->proyek()->first();

    $response = $this->actingAs($user)->post(route('dashboard.stages.update', [
        'proyek' => $project->id_proyek,
        'sintak' => 'B',
    ]), [
        'nama_proyek' => $project->nama_proyek,
        'pertanyaan_mendasar' => 'Bagaimana merancang aplikasi kasir sederhana untuk UMKM?',
        'masalah_nyata' => 'Pencatatan transaksi UMKM masih dilakukan manual dan rawan salah.',
        'ide_solusi_awal' => 'Membangun aplikasi kasir sederhana berbasis desktop untuk UMKM.',
        'tujuan_proyek' => 'Membangun aplikasi kasir sederhana yang mempermudah pencatatan transaksi.',
        'catatan_tambahan' => 'Perlu fokus pada kemudahan penggunaan untuk kasir pemula.',
        'advance' => '1',
    ]);

    $response->assertRedirect(route('dashboard.sintak', [
        'proyek' => $project->id_proyek,
        'sintak' => 'A',
    ]));

    $project = $project->fresh();

    $progressBegin = $project->progressSintak()
        ->whereHas('sintakBadrul', fn ($query) => $query->where('kode_sintak', 'B'))
        ->first();

    $progressAnalyze = $project->progressSintak()
        ->whereHas('sintakBadrul', fn ($query) => $query->where('kode_sintak', 'A'))
        ->first();

    $workspaceBegin = $project->workspaceSintak()
        ->whereHas('sintakBadrul', fn ($query) => $query->where('kode_sintak', 'B'))
        ->first();

    expect($progressBegin?->status)->toBe('selesai');
    expect($progressAnalyze?->status)->toBe('proses');
    expect($workspaceBegin)->not->toBeNull();
});

test('project material is derived automatically from selected meeting', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)->get(route('dashboard.sintak'));

    $project = $user->fresh()->proyek()->first();

    $response = $this->actingAs($user)->post(route('dashboard.projects.update', $project), [
        'page' => 'sintak',
        'sintak' => 'B',
        'nama_proyek' => $project->nama_proyek,
        'pertemuan_ke' => 9,
        'deskripsi' => $project->deskripsi,
    ]);

    $response->assertRedirect(route('dashboard.sintak', [
        'proyek' => $project->id_proyek,
        'sintak' => 'B',
    ]));

    $project = $project->fresh();

    expect($project->pertemuan_ke)->toBe(9);
    expect($project->materi)->toBe('Koneksi Database');
});

test('sintak page shows updated assistant choices for each stage', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)->get(route('dashboard.sintak'));

    $project = $user->fresh()->proyek()->first();

    $cases = [
        'B' => [
            'present' => ['AI Reasoning Assistant', 'AI Idea Assistant'],
            'absent' => ['AI Scope Assistant'],
        ],
        'A' => [
            'present' => ['AI Planning Assistant', 'AI Learning Resource Assistant', 'AI Requirement Analysis Assistant'],
            'absent' => ['AI Flow Assistant'],
        ],
        'D' => [
            'present' => ['AI Coding Assistant', 'AI Debugging Assistant', 'AI Database Assistant'],
            'absent' => [],
        ],
        'R' => [
            'present' => ['AI Feedback Assistant', 'AI Revision Assistant'],
            'absent' => ['AI Testing Assistant'],
        ],
        'U' => [
            'present' => ['AI Presentation Assistant', 'AI Communication Assistant'],
            'absent' => ['AI Benefit Assistant'],
        ],
        'L' => [
            'present' => ['AI Reflective Assistant', 'AI Learning Analytics Assistant'],
            'absent' => ['AI Improvement Assistant'],
        ],
    ];

    foreach ($cases as $stageCode => $case) {
        $response = $this->actingAs($user)->get(route('dashboard.sintak', [
            'proyek' => $project->id_proyek,
            'sintak' => $stageCode,
        ]));

        $response->assertOk();

        foreach ($case['present'] as $text) {
            $response->assertSee($text);
        }

        foreach ($case['absent'] as $text) {
            $response->assertDontSee($text);
        }
    }
});

test('assistant order follows config for sintak r and l', function () {
    $workflow = app(BadrulWorkflowService::class);

    $workflow->syncMasterData();

    expect($workflow->assistantsForStage('R')->pluck('nama_ai')->values()->all())
        ->toBe([
            'AI Feedback Assistant',
            'AI Revision Assistant',
        ]);

    expect($workflow->assistantsForStage('L')->pluck('nama_ai')->values()->all())
        ->toBe([
            'AI Reflective Assistant',
            'AI Learning Analytics Assistant',
        ]);
});

test('assistant radios start unchecked and expose default prompt text metadata', function () {
    $user = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)->get(route('dashboard.sintak'));

    $project = $user->fresh()->proyek()->first();

    $expectedByStage = [
        'B' => [
            'AI Reasoning Assistant' => 'Bantu saya menganalisis permasalahan utama dari proyek yang akan dikembangkan serta membantu merumuskan pertanyaan mendasar proyek secara logis dan relevan dengan mata kuliah Pemrograman Visual.',
            'AI Idea Assistant' => 'Berikan ide proyek aplikasi berbasis Pemrograman Visual beserta fitur utama yang sesuai dengan kebutuhan pengguna dan dapat diselesaikan pada proyek perkuliahan.',
        ],
        'A' => [
            'AI Planning Assistant' => 'Bantu saya menyusun rencana pengembangan proyek aplikasi secara sistematis mulai dari analisis kebutuhan, perancangan, implementasi, hingga pengujian aplikasi.',
            'AI Learning Resource Assistant' => 'Berikan rekomendasi sumber belajar, tutorial, referensi kode, dan materi yang relevan untuk membantu pengembangan proyek aplikasi berbasis Pemrograman Visual.',
            'AI Requirement Analysis Assistant' => 'Bantu saya menganalisis kebutuhan sistem dan kebutuhan pengguna untuk proyek aplikasi yang akan dikembangkan, termasuk fitur utama, input, proses, dan output aplikasi.',
        ],
        'D' => [
            'AI Coding Assistant' => 'Bantu saya membuat kode program dan struktur aplikasi berbasis Pemrograman Visual sesuai fitur yang akan dikembangkan pada proyek ini.',
            'AI Debugging Assistant' => 'Bantu saya menemukan dan memperbaiki kesalahan kode program atau error yang muncul pada aplikasi berbasis Pemrograman Visual yang sedang dikembangkan.',
            'AI Database Assistant' => 'Bantu saya merancang database, membuat tabel dan query SQL, serta menghubungkan database dengan aplikasi Pemrograman Visual yang sedang dikembangkan.',
        ],
        'R' => [
            'AI Feedback Assistant' => 'Bantu saya mengevaluasi hasil proyek aplikasi yang telah dikembangkan serta berikan umpan balik dan saran perbaikan terhadap fitur, tampilan, dan fungsi aplikasi.',
            'AI Revision Assistant' => 'Bantu saya melakukan revisi dan penyempurnaan aplikasi berdasarkan hasil evaluasi dan umpan balik yang telah diperoleh pada tahap review proyek.',
        ],
        'U' => [
            'AI Presentation Assistant' => 'Bantu saya menyusun materi presentasi proyek aplikasi secara sistematis, mulai dari latar belakang, fitur aplikasi, proses pengembangan, hingga hasil implementasi proyek.',
            'AI Communication Assistant' => 'Bantu saya menyiapkan cara menjelaskan dan mendemonstrasikan aplikasi proyek agar penyampaian presentasi lebih jelas, menarik, dan mudah dipahami audiens.',
        ],
        'L' => [
            'AI Reflective Assistant' => 'Bantu saya melakukan refleksi terhadap proses pembelajaran dan pengembangan proyek yang telah dilakukan, termasuk capaian, kesulitan, dan strategi perbaikan pembelajaran berikutnya.',
            'AI Learning Analytics Assistant' => 'Bantu saya menganalisis perkembangan pembelajaran berdasarkan aktivitas proyek, progres sintak, dan kemampuan yang telah saya capai selama pengembangan aplikasi.',
        ],
    ];

    foreach ($expectedByStage as $stageCode => $expectedPrompts) {
        $response = $this->actingAs($user)->get(route('dashboard.sintak', [
            'proyek' => $project->id_proyek,
            'sintak' => $stageCode,
        ]));

        $response->assertOk();

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $inputs = $xpath->query('//input[@name="assistant_picker"]');
        $checkedCount = 0;
        $renderedPrompts = [];

        foreach ($inputs as $input) {
            if ($input->hasAttribute('checked')) {
                $checkedCount++;
            }

            $renderedPrompts[$input->getAttribute('data-ai-name')] = $input->getAttribute('data-default-extra-context');
        }

        expect($checkedCount)->toBe(0);
        expect($renderedPrompts)->toBe($expectedPrompts);
    }
});

test('user cannot login with invalid password', function () {
    User::factory()->create([
        'username' => 'admin',
        'password' => 'admin',
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'username' => 'admin',
        'password' => 'salah',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});