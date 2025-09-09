<?php

namespace App\Filters\ContentTranslations;

use Closure;
use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter
{
    public function __construct(private ?TranslationStatus $status = null) {}

    public function handle(Builder $query, Closure $next)
    {
        if ($this->status) {
            $query->where('status', $this->status);
        } else {
            $query->where('status', TranslationStatus::COMPLETED);
        }

        return $next($query);
    }
}

