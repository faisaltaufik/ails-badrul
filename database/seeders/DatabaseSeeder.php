<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BadrulMasterSeeder::class);

        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Admin AILS',
                'password' => 'admin',
                'role' => 'admin',
                'prodi' => 'Sistem Informasi',
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'andi'],
            [
                'nama' => 'Andi',
                'password' => '12345678',
                'role' => 'mahasiswa',
                'prodi' => 'Sistem Informasi',
            ]
        );

        $this->call(DemoWorkflowSeeder::class);
    }
}
