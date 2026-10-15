<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('return_reminded_at')->nullable();
            $table->index(
                ['status', 'return_reminded_at', 'reservation_date', 'end_time'],
                'reservations_due_return_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_due_return_lookup_index');
            $table->dropColumn('return_reminded_at');
        });
    }
};
