<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for user analytics distribution data.
 */
class UserAnalyticsReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'role'  => $this->resource['role'] ?? $this->role ?? null,
            'count' => (int) ($this->resource['count'] ?? $this->count ?? 0),
        ];
    }

    /**
     * Build a structured response for the full user analytics payload.
     */
    public static function buildResponse(
        $distribution,
        $growth,
        int $totalUsers,
        int $blockedUsers
    ): array {
        return [
            'distribution'  => $distribution->map(fn ($item) => [
                'role'  => $item->role,
                'count' => (int) $item->count,
            ])->values()->toArray(),
            'growth'        => $growth->map(fn ($item) => [
                'month' => $item->month,
                'count' => (int) $item->count,
            ])->values()->toArray(),
            'total_users'   => $totalUsers,
            'blocked_users' => $blockedUsers,
        ];
    }
}
