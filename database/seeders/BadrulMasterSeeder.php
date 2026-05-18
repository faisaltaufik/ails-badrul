<?php

namespace Database\Seeders;

use App\Services\BadrulWorkflowService;
use Illuminate\Database\Seeder;

class BadrulMasterSeeder extends Seeder
{
    /**
     * Seed the application's BADRUL master data.
     */
    public function run(): void
    {
        app(BadrulWorkflowService::class)->syncMasterData();
    }
}