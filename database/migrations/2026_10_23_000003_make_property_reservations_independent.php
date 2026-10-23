<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservations')
            ->whereIn('id', DB::table('reservation_items')->select('reservation_id'))
            ->delete();

        DB::table('facility_items')->update(['facility_id' => null]);

        Schema::table('facility_items', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->unsignedBigInteger('facility_id')->nullable()->change();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->unsignedBigInteger('facility_id')->nullable()->change();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This migration deletes legacy property reservations and cannot be reversed without losing newer independent property data.'
        );
    }
};
