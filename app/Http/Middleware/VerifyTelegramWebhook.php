<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTelegramWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = config('telegram.webhook_secret');

        if (! is_string($configuredSecret) || $configuredSecret === '') {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Telegram webhook is not configured.');
        }

        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (! hash_equals($configuredSecret, $providedSecret)) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
