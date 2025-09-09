<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentTranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'target_language' => $this->target_language,
            'original_content' => $this->whenLoaded('content', fn () => $this->content->original_content),
            'translated_content' => $this->when(
                $this->status->isCompleted(),
                $this->translated_content
            ),
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label()
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
