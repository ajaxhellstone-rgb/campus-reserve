<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $rawMessage = rtrim(strtr(base64_encode($message->getOriginalMessage()->toString()), '+/', '-_'), '=');

        try {
            $response = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->timeout(20)
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                    'raw' => $rawMessage,
                ]);
        } catch (ConnectionException $exception) {
            throw new TransportException('Could not connect to the Gmail API: '.$exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? $response->json('error_description') ?? 'No error message provided.';

            throw new TransportException(sprintf(
                'The Gmail API rejected the email with HTTP %d: %s',
                $response->status(),
                $detail
            ));
        }

        $messageId = $response->json('id');
        if (! is_string($messageId) || $messageId === '') {
            throw new TransportException('The Gmail API response did not include an email ID.');
        }
    }

    private function getAccessToken(): string
    {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->refreshToken === '') {
            throw new TransportException('Gmail API OAuth credentials are not fully configured.');
        }

        if ($this->accessToken !== null && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ]);
        } catch (ConnectionException $exception) {
            throw new TransportException('Could not obtain a Gmail API access token: '.$exception->getMessage(), 0, $exception);
        }

        $accessToken = $response->json('access_token');
        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            $detail = $response->json('error_description') ?? $response->json('error') ?? 'No error message provided.';

            throw new TransportException(sprintf(
                'Google OAuth could not refresh the Gmail API access token (HTTP %d): %s',
                $response->status(),
                is_string($detail) ? $detail : 'No error message provided.'
            ));
        }

        $this->accessToken = $accessToken;
        $this->accessTokenExpiresAt = time() + (int) $response->json('expires_in', 3600);

        return $this->accessToken;
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
