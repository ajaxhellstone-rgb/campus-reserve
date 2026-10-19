<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationUnreadSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_summary_returns_count_and_latest_timestamp(): void
    {
        $user = User::factory()->create();
        $earlierAt = now()->subMinutes(2)->toDateTimeString();
        $latestAt = now()->subMinute()->toDateTimeString();
        $readAt = now()->subSeconds(30)->toDateTimeString();

        foreach ([$earlierAt, $latestAt] as $createdAt) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'test',
                'data' => [],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [],
            'read_at' => $readAt,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.unreadSummary'))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('latest_at', $latestAt);
    }
}
