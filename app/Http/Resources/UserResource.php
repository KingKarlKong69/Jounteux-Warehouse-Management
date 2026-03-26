<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Never exposes password hashes.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'role' => $this->role,
            'role_label' => ucfirst($this->role),
            'is_blocked' => $this->is_blocked,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'blocked_at_formatted' => $this->blocked_at?->format('M d, Y h:i A'),
            'block_reason' => $this->getRawOriginal('block_reason'),
            'block_reason_label' => $this->block_reason?->label() ?? null,
            'failed_login_attempts' => $this->failed_login_attempts,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_formatted' => $this->created_at?->format('M d, Y h:i A'),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_at_formatted' => $this->updated_at?->format('M d, Y h:i A'),
        ];
    }
}
