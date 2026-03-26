<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * ─────────────────────────────────────────────────────────────
 * ApiResponse — Unified JSON Response Envelope
 * ─────────────────────────────────────────────────────────────
 *
 * Enforces a standardized response structure across ALL API
 * endpoints. Every JSON response follows this contract:
 *
 *   {
 *     "success": boolean,
 *     "data": object|array|null,
 *     "error_message": string|null,
 *     "error_code": int|null,
 *     "meta": object|null
 *   }
 *
 * Usage in controllers:
 *   return $this->success($data, $meta);
 *   return $this->error('Not found', 404);
 */
trait ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param mixed      $data   Response payload
     * @param array|null $meta   Optional metadata (pagination, timestamps)
     * @param int        $status HTTP status code (default 200)
     */
    protected function success(mixed $data = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        $response = [
            'success'       => true,
            'data'          => $data,
            'error_message' => null,
            'error_code'    => null,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error JSON response.
     *
     * @param string   $message  Human-readable error description
     * @param int      $code     HTTP status code
     * @param mixed    $data     Optional error detail payload
     */
    protected function error(string $message, int $code = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success'       => false,
            'data'          => $data,
            'error_message' => $message,
            'error_code'    => $code,
        ], $code);
    }
}
