<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Telegram\Actions\IngestTelegramUpdateAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, IngestTelegramUpdateAction $action): JsonResponse
    {
        $payload = $request->json()->all();

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $action->execute($payload);

        return response()->json(['ok' => true], Response::HTTP_ACCEPTED);
    }
}
