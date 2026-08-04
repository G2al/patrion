<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class ApiController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data]);
    }

    protected function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => $errors], $status);
    }
}
