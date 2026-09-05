<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Compatible = 'compatible';
    case Unclear = 'unclear';
    case Critical = 'critical';

    /**
     * Human-readable meaning of each status, for consumers (e.g. an LLM
     * export) that shouldn't have to infer it from the raw enum value.
     *
     * @return array<string, string>
     */
    public static function legend(): array
    {
        return [
            self::Compatible->value => 'Neueste Version erfüllt die Ziel-Constraints.',
            self::Unclear->value => 'Erfüllt Ziel-Constraints nicht, aber Release ist jünger als 12 Monate und nicht abandoned.',
            self::Critical->value => 'Erfüllt Ziel-Constraints nicht UND (Release älter als 12 Monate ODER abandoned).',
        ];
    }
}
