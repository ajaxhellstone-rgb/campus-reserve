<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('facilities')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX facilities_name_building_unique_idx ON facilities (LOWER(name), LOWER(building));');
            return;
        }

        Schema::table('facilities', function (Blueprint $table) {
            $table->unique(['name', 'building']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('facilities')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS facilities_name_building_unique_idx;');
            return;
        }

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropUnique(['name', 'building']);
        });
    }
};
