<?php

use App\Models\User;
use App\Services\UserNameSearchIndex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_name_search_tokens', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64);
            $table->timestamps();
            $table->primary(['user_id', 'token_hash']);
            $table->index(['token_hash', 'user_id']);
        });

        $searchIndex = app(UserNameSearchIndex::class);
        User::query()->chunkById(200, function ($users) use ($searchIndex): void {
            foreach ($users as $user) {
                $searchIndex->sync($user);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_name_search_tokens');
    }
};
