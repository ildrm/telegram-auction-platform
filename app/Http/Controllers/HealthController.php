<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\OperationalHealthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthController extends Controller
{
    public function __invoke(OperationalHealthService $health): JsonResponse
    {
        $result = $health->inspect();

        return response()->json(
            ['status' => $result['healthy'] ? 'ok' : 'degraded', 'checks' => $result['checks']],
            $result['healthy'] ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
