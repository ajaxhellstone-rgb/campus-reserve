<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserNameSearchIndex
{
    public function sync(User $user): void
    {
        $tokens = $this->tokens($user->name);
        DB::table('user_name_search_tokens')
            ->where('user_id', $user->id)
            ->delete();

        if ($tokens === []) {
            return;
        }

        $now = now();
        DB::table('user_name_search_tokens')->insertOrIgnore(
            array_map(fn (string $token): array => [
                'user_id' => $user->id,
                'token_hash' => $this->hashToken($token),
                'created_at' => $now,
                'updated_at' => $now,
            ], $tokens)
        );
    }

    public function matchingUserIds(string $search): Collection
    {
        $tokens = $this->tokens($search);
        if ($tokens === []) {
            return collect();
        }

        $tokenHashes = array_map(fn (string $token): string => $this->hashToken($token), $tokens);

        return DB::table('user_name_search_tokens')
            ->whereIn('token_hash', $tokenHashes)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT token_hash) = ?', [count($tokenHashes)])
            ->pluck('user_id');
    }

    public function matches(string $name, string $search): bool
    {
        return mb_strpos($this->normalize($name), $this->normalize($search)) !== false;
    }

    private function tokens(string $value): array
    {
        $normalized = $this->normalize($value);
        $length = mb_strlen($normalized);
        if ($length === 0) {
            return [];
        }

        $gramSize = min(3, $length);
        $tokens = [];
        for ($index = 0; $index <= $length - $gramSize; $index++) {
            $tokens[] = mb_substr($normalized, $index, $gramSize);
        }

        return array_values(array_unique($tokens));
    }

    private function normalize(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        return preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
    }

    private function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
