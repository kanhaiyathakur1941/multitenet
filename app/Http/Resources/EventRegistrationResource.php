<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventRegistration
 */
class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'event' => new EventResource($this->whenLoaded('event')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
