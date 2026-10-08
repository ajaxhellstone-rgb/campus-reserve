<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_invalid_two_fa_secret_does_not_throw(): void
    {
        $user = new User();
        $user->setRawAttributes(['two_fa_secret' => 'not-a-valid-encrypted-value']);

        $this->assertNull($user->two_fa_secret);
    }
}
