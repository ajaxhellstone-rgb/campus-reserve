<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class ResendApiTransport extends AbstractTransport
{
    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('The Resend API key is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            if (! in_array(strtolower($header->getName()), ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type'], true)) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }

        $payload = [
            'from' => $envelope->getSender()->toString(),
            'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ];

        foreach ([
            'cc' => $email->getCc(),
            'bcc' => $email->getBcc(),
            'reply_to' => $email->getReplyTo(),
        ] as $key => $addresses) {
            if ($addresses !== []) {
                $payload[$key] = $this->stringifyAddresses($addresses);
            }
        }

        if ($headers !== []) {
            $payload['headers'] = $headers;
        }

        if ($email->getAttachments() !== []) {
            $payload['attachments'] = array_map(function ($attachment): array {
                $attachmentHeaders = $attachment->getPreparedHeaders();
                $contentType = $attachmentHeaders->get('Content-Type')->getBody();
                $content = $contentType === 'text/calendar'
                    ? $attachment->getBody()
                    : str_replace("\r\n", '', $attachment->bodyToString());

                return [
                    'content_type' => $contentType,
                    'content' => $content,
                    'filename' => $attachmentHeaders->getHeaderParameter('Content-Disposition', 'filename'),
                ];
            }, $email->getAttachments());
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(20)
                ->post('https://api.resend.com/emails', $payload);
        } catch (ConnectionException $exception) {
            throw new TransportException('Could not connect to the Resend API: '.$exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            throw new TransportException(sprintf(
                'The Resend API rejected the email with HTTP %d: %s',
                $response->status(),
                $response->json('message') ?? 'No error message provided.'
            ));
        }

        $messageId = $response->json('id');
        if (! is_string($messageId) || $messageId === '') {
            throw new TransportException('The Resend API response did not include an email ID.');
        }

        $email->getHeaders()->addTextHeader('X-Resend-Email-ID', $messageId);
    }

    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email): bool {
            return ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true);
        });
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
