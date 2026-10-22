<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginCsrfRecoveryTest extends TestCase
{
    public function test_login_page_explains_expired_csrf_sessions_and_offers_a_refresh(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Your login session expired or changed. Refresh this page and try again.')
            ->assertSee('Refresh login page')
            ->assertSee('response.status === 419', false);
    }
}
