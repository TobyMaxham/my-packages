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
            self::Compatible->value => 'Neueste Version erlaubt alle required- und recommended-Zielversionen und ist nicht abandoned.',
            self::Unclear->value => 'Nicht abandoned und entweder: required-Zielversion fehlt, aber Release ist jünger als 12 Monate; oder: alle required erlaubt, aber kein getaggtes Release (nur Default-Branch geprüft), kein Laravel-Constraint bei Release älter als 3 Jahren (nur Composer) oder eine recommended-Zielversion fehlt.',
            self::Critical->value => 'Abandoned ODER (required-Zielversion fehlt UND (Release älter als 12 Monate ODER kein Release)).',
        ];
    }
}
