<?php

namespace App\Filters\ContentTranslations;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class TargetLanguageFilter
{
    public function __construct(private ?string $targetLanguage = null) {}

    public function handle(Builder $query, Closure $next)
    {
        if ($this->targetLanguage) {
            $query->where('target_language', $this->targetLanguage);
        }

        return $next($query);
    }
}

