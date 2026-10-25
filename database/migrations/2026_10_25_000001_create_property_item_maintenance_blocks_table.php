<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_item_maintenance_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facility_item_id')->constrained()->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('reason', 255);
            $table->timestamps();

            $table->index(['facility_item_id', 'maintenance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_item_maintenance_blocks');
    }
};
