<?php

namespace App\Filters\ContentTranslations;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class SourceLanguageFilter
{
    public function __construct(private ?string $sourceLanguage = null) {}

    public function handle(Builder $query, Closure $next)
    {
        if ($this->sourceLanguage) {
            $query->where('source_language', $this->sourceLanguage);
        }

        return $next($query);
    }
}
