<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ResendMailTransportTest extends TestCase
{
    public function test_resend_mailer_sends_messages_through_the_https_api(): void
    {
        config([
            'mail.mailers.resend.key' => 'test-resend-key',
            'mail.from.address' => 'no-reply@example.com',
            'mail.from.name' => 'Campus Reserve',
        ]);
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'resend-message-id'], 200),
        ]);

        Mail::mailer('resend')->raw('Reset your password.', function ($message): void {
            $message->to('user@example.com')->subject('Password reset');
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization')
                && $request['from'] === '"Campus Reserve" <no-reply@example.com>'
                && $request['to'] === ['user@example.com']
                && $request['subject'] === 'Password reset'
                && $request['text'] === 'Reset your password.';
        });
    }

    public function test_resend_mailer_reports_api_rejections(): void
    {
        config(['mail.mailers.resend.key' => 'test-resend-key']);
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['message' => 'Invalid API key'], 401),
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('HTTP 401: Invalid API key');

        Mail::mailer('resend')->raw('Test email.', function ($message): void {
            $message->to('user@example.com')->subject('Test');
        });
    }

    public function test_resend_mailer_requires_an_api_key(): void
    {
        config([
            'mail.mailers.resend.key' => null,
            'services.resend.key' => null,
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('The Resend API key is not configured.');

        Mail::mailer('resend')->raw('Test email.', function ($message): void {
            $message->to('user@example.com')->subject('Test');
        });
    }
}
