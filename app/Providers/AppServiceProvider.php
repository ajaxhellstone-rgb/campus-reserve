<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use App\Mail\Transport\ResendApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('gmail-api', function (array $config): GmailApiTransport {
            return new GmailApiTransport(
                is_string($config['client_id'] ?? null) ? $config['client_id'] : '',
                is_string($config['client_secret'] ?? null) ? $config['client_secret'] : '',
                is_string($config['refresh_token'] ?? null) ? $config['refresh_token'] : '',
            );
        });

        Mail::extend('resend', function (array $config): ResendApiTransport {
            $key = $config['key'] ?? config('services.resend.key');

            return new ResendApiTransport(is_string($key) ? $key : '');
        });
    }
}
