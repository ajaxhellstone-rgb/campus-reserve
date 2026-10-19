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
                ['status', 'reservation_date'],
                'reservations_status_date_index'
            );
        });

        Schema::table('reservation_items', function (Blueprint $table) {
            $table->index('facility_item_id', 'reservation_items_facility_item_index');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->dropIndex('reservation_items_facility_item_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_date_index');
        });
    }
};
