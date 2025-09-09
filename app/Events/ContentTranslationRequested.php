<?php

namespace App\Events;

use App\Models\ContentTranslation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentTranslationRequested
{
    use Dispatchable, SerializesModels;

    /**
     * The content translation instance associated with the request.
     */
    public function __construct(
        public readonly ContentTranslation $translation
    ) {}
}
