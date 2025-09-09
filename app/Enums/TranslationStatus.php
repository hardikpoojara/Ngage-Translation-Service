<?php

namespace App\Enums;

enum TranslationStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Translation',
            self::COMPLETED => 'Translation Completed',
            self::FAILED => 'Translation Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED]);
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool{
        return $this === self::FAILED;
    }
}
