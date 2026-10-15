<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class GmailApiMailTransportTest extends TestCase
{
    public function test_gmail_api_mailer_refreshes_oauth_and_sends_rfc822_message(): void
    {
        config([
            'mail.mailers.gmail_api.client_id' => 'test-client-id',
            'mail.mailers.gmail_api.client_secret' => 'test-client-secret',
            'mail.mailers.gmail_api.refresh_token' => 'test-refresh-token',
            'mail.from.address' => 'sender@gmail.com',
            'mail.from.name' => 'Campus Reserve',
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'gmail-message-id',
            ]),
        ]);

        Mail::mailer('gmail_api')->raw('Reset your password.', function ($message): void {
            $message->to('user@example.com')->subject('Password reset');
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request['client_id'] === 'test-client-id'
                && $request['refresh_token'] === 'test-refresh-token'
                && $request['grant_type'] === 'refresh_token';
        });
        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $decodedMessage = base64_decode(strtr($request['raw'], '-_', '+/'));

            return $request->hasHeader('Authorization', 'Bearer test-access-token')
                && is_string($decodedMessage)
                && str_contains($decodedMessage, 'sender@gmail.com')
                && str_contains($decodedMessage, 'user@example.com')
                && str_contains($decodedMessage, 'Password reset')
                && str_contains($decodedMessage, 'Reset your password.');
        });
    }

    public function test_gmail_api_mailer_reports_oauth_refresh_errors(): void
    {
        config([
            'mail.mailers.gmail_api.client_id' => 'test-client-id',
            'mail.mailers.gmail_api.client_secret' => 'test-client-secret',
            'mail.mailers.gmail_api.refresh_token' => 'invalid-refresh-token',
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Token has been expired or revoked.');

        Mail::mailer('gmail_api')->raw('Test email.', function ($message): void {
            $message->to('user@example.com')->subject('Test');
        });
    }

    public function test_gmail_api_mailer_reports_api_rejections(): void
    {
        config([
            'mail.mailers.gmail_api.client_id' => 'test-client-id',
            'mail.mailers.gmail_api.client_secret' => 'test-client-secret',
            'mail.mailers.gmail_api.refresh_token' => 'test-refresh-token',
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'error' => ['message' => 'Gmail API is disabled'],
            ], 403),
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('HTTP 403: Gmail API is disabled');

        Mail::mailer('gmail_api')->raw('Test email.', function ($message): void {
            $message->to('user@example.com')->subject('Test');
        });
    }

    public function test_gmail_api_mailer_requires_all_oauth_credentials(): void
    {
        config([
            'mail.mailers.gmail_api.client_id' => null,
            'mail.mailers.gmail_api.client_secret' => null,
            'mail.mailers.gmail_api.refresh_token' => null,
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Gmail API OAuth credentials are not fully configured.');

        Mail::mailer('gmail_api')->raw('Test email.', function ($message): void {
            $message->to('user@example.com')->subject('Test');
        });
    }
}
