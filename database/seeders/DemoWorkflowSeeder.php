<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BadrulWorkflowService;
use Illuminate\Database\Seeder;

class DemoWorkflowSeeder extends Seeder
{
    /**
     * Seed a starter project workflow for the demo admin user.
     */
    public function run(): void
    {
        $user = User::query()->where('username', 'admin')->first();

        if (! $user) {
            return;
        }

        $service = app(BadrulWorkflowService::class);

        $project = $user->proyek()
            ->where('nama_proyek', config('badrul.default_project.nama_proyek'))
            ->latest('tanggal_buat')
            ->first();

        if (! $project) {
            $service->createProject($user, [], true);

            return;
        }

        $service->seedDemoProjectData($project);
    }
}