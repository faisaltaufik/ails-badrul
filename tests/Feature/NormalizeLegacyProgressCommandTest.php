<?php

use App\Models\Proyek;
use App\Models\User;
use App\Services\BadrulWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepareUserProjects(User $user): User
{
    app(BadrulWorkflowService::class)->ensureUserProjects($user);

    return $user->fresh();
}

function markProjectAsLegacyAutoStarted(Proyek $project): Proyek
{
    $progressRows = $project->fresh()->progressSintak()
        ->with('sintakBadrul')
        ->get();

    foreach ($progressRows as $progress) {
        $progress->update([
            'status' => $progress->sintakBadrul->kode_sintak === 'B' ? 'proses' : 'belum',
            'terakhir_update' => now(),
        ]);
    }

    return $project->fresh();
}

function beginStatusForProject(Proyek $project): ?string
{
    return $project->fresh()->progressSintak()
        ->whereHas('sintakBadrul', fn ($query) => $query->where('kode_sintak', 'B'))
        ->first()?->status;
}

test('normalize legacy progress command skips a single ambiguous project by default', function () {
    $user = prepareUserProjects(User::factory()->create([
        'nama' => 'Mahasiswa Legacy',
        'username' => 'legacy-single',
        'password' => 'password123',
        'role' => 'mahasiswa',
        'prodi' => 'Pemrograman Visual',
    ]));

    $project = markProjectAsLegacyAutoStarted(
        $user->proyek()->where('pertemuan_ke', 1)->firstOrFail(),
    );

    $this->artisan('badrul:normalize-legacy-progress --execute')
        ->assertExitCode(0);

    expect(beginStatusForProject($project))->toBe('proses');
});

test('normalize legacy progress command dry-run reports candidates without changing them', function () {
    $user = prepareUserProjects(User::factory()->create([
        'nama' => 'Mahasiswa Legacy Dry Run',
        'username' => 'legacy-dry-run',
        'password' => 'password123',
        'role' => 'mahasiswa',
        'prodi' => 'Pemrograman Visual',
    ]));

    $projectOne = markProjectAsLegacyAutoStarted(
        $user->proyek()->where('pertemuan_ke', 1)->firstOrFail(),
    );
    $projectTwo = markProjectAsLegacyAutoStarted(
        $user->proyek()->where('pertemuan_ke', 2)->firstOrFail(),
    );

    $this->artisan('badrul:normalize-legacy-progress')
        ->expectsOutputToContain('Dry-run saja. Tambahkan --execute untuk menerapkan normalisasi.')
        ->assertExitCode(0);

    expect(beginStatusForProject($projectOne))->toBe('proses');
    expect(beginStatusForProject($projectTwo))->toBe('proses');
});

test('normalize legacy progress command can force-reset a single legacy project', function () {
    $user = prepareUserProjects(User::factory()->create([
        'nama' => 'Mahasiswa Legacy Force',
        'username' => 'legacy-force',
        'password' => 'password123',
        'role' => 'mahasiswa',
        'prodi' => 'Pemrograman Visual',
    ]));

    $project = markProjectAsLegacyAutoStarted(
        $user->proyek()->where('pertemuan_ke', 1)->firstOrFail(),
    );

    $this->artisan('badrul:normalize-legacy-progress --force-single --execute')
        ->assertExitCode(0);

    expect(beginStatusForProject($project))->toBe('belum');
});