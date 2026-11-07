<?php

use App\Models\User;
use App\Services\BadrulWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'badrul:normalize-legacy-progress
        {--user-id= : Batasi ke satu user tertentu}
        {--force-single : Izinkan normalisasi walau hanya ada satu kandidat ambigu pada user tersebut}
        {--execute : Terapkan perubahan ke database. Default command berjalan sebagai dry-run}',
    function () {
        $workflow = app(BadrulWorkflowService::class);
        $selectedUserId = $this->option('user-id');
        $forceSingle = (bool) $this->option('force-single');
        $execute = (bool) $this->option('execute');

        $users = User::query()
            ->when(
                filled($selectedUserId),
                fn ($query) => $query->where('id_user', (int) $selectedUserId),
            )
            ->with([
                'proyek.progressSintak.sintakBadrul',
                'proyek.workspaceSintak',
                'proyek.refleksi',
            ])
            ->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada user yang cocok untuk diperiksa.');

            return 1;
        }

        $rows = [];
        $candidateUsers = 0;
        $candidateProjects = 0;

        foreach ($users as $user) {
            $summary = $workflow->normalizeLegacyAutoStartedProjectsForUser($user, $forceSingle, $execute);

            if (($summary['candidate_count'] ?? 0) === 0) {
                continue;
            }

            $candidateUsers++;
            $candidateProjects += (int) $summary['candidate_count'];

            $rows[] = [
                'User ID' => $summary['user_id'],
                'Username' => $summary['username'],
                'Pertemuan' => implode(', ', $summary['meetings']),
                'Project ID' => implode(', ', $summary['project_ids']),
                'Mode' => $execute ? 'normalized' : 'dry-run',
            ];
        }

        if ($rows === []) {
            $this->info(
                $execute
                    ? 'Tidak ada proyek legacy yang perlu dinormalisasi.'
                    : 'Dry-run selesai. Tidak ada proyek legacy yang terdeteksi dengan heuristik saat ini.',
            );

            if (! $forceSingle) {
                $this->line('Catatan: kandidat tunggal yang ambigu sengaja dilewati. Gunakan --force-single jika ingin memeriksa kasus itu.');
            }

            return 0;
        }

        $this->table(['User ID', 'Username', 'Pertemuan', 'Project ID', 'Mode'], $rows);
        $this->info("User kandidat: {$candidateUsers}; proyek kandidat: {$candidateProjects}.");

        if (! $execute) {
            $this->comment('Dry-run saja. Tambahkan --execute untuk menerapkan normalisasi.');
        }

        if (! $forceSingle) {
            $this->line('Catatan: kandidat tunggal yang ambigu dilewati untuk menghindari reset proyek yang memang sengaja baru dimulai.');
        }

        return 0;
    },
)->purpose('Normalisasi proyek BADRUL legacy yang dulu otomatis memulai sintak B tanpa aksi Masuk.');
