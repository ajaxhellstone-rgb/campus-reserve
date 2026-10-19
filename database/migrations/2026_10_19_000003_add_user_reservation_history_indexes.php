<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(
                ['user_id', 'reservation_date', 'start_time'],
                'reservations_user_history_index'
            );
            $table->index(
                ['user_id', 'status', 'reservation_date'],
                'reservations_user_stats_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_user_history_index');
            $table->dropIndex('reservations_user_stats_index');
        });
    }
};
