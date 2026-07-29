<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;

final readonly class TelegramClient
{
    public function __construct(private Factory $http) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function call(string $method, array $payload): array
    {
        $token = config('telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Telegram bot token is not configured.');
        }

        try {
            $response = $this->http
                ->asJson()
                ->timeout(config('telegram.delivery_timeout_seconds'))
                ->post(rtrim(config('telegram.api_base_url'), '/')."/bot{$token}/{$method}", $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new \RuntimeException('Telegram API request failed.', previous: $exception);
        }

        $body = $response->json();

        if (! is_array($body) || ($body['ok'] ?? false) !== true) {
            throw new \RuntimeException('Telegram API returned an unsuccessful response.');
        }

        return $body;
    }
}
