<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('start_time', 5)->default('00:00');
            $table->string('end_time', 5)->default('23:59');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_settings');
    }
};
