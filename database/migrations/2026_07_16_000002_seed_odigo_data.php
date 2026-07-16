<?php

use Database\Seeders\OdigoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed the mock Odigo data as part of migration so the packaged
     * NativePHP app (which runs `native:migrate` on boot but not seeders)
     * ships with people and message history ready to go.
     */
    public function up(): void
    {
        (new OdigoSeeder())->run();
    }

    public function down(): void
    {
        \App\Models\OdigoMessage::query()->delete();
        \App\Models\Person::query()->delete();
    }
};
