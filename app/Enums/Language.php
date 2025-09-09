<?php

namespace App\Enums;

enum Language: string
{
    case ES = 'es';
    case FR = 'fr';
    case DE = 'de';
    case IT = 'it';
    case PT = 'pt';
    case JA = 'ja';
    case KO = 'ko';
    case ZH = 'zh';
    case EN = 'en';

    public function label(): string
    {
        return match($this) {
            self::ES => 'Spanish',
            self::FR => 'French',
            self::DE => 'German',
            self::IT => 'Italian',
            self::PT => 'Portuguese',
            self::JA => 'Japanese',
            self::KO => 'Korean',
            self::ZH => 'Chinese',
            self::EN => 'English',
        };
    }

    public static function tryFromCode(string $code): ?self
    {
        // Normalize to lowercase to be tolerant of input
        $code = strtolower($code);
        return self::tryFrom($code);
    }

    public static function nameFor(string $code): string
    {
        $lang = self::tryFromCode($code);
        return $lang?->label() ?? $code;
    }

    public static function getDefaultLanguage(): self
    {
        return self::EN;
    }
}
