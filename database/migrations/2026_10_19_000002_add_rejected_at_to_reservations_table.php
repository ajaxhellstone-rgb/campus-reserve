<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('rejected_at')->nullable()->after('status');
            $table->index(['status', 'rejected_at']);
        });

        DB::table('reservations')
            ->where('status', 'rejected')
            ->update(['rejected_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['status', 'rejected_at']);
            $table->dropColumn('rejected_at');
        });
    }
};
