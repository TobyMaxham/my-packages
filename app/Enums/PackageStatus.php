<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Compatible = 'compatible';
    case Unclear = 'unclear';
    case Critical = 'critical';
    case Abandoned = 'abandoned';

    /**
     * Human-readable meaning of each status, for consumers (e.g. an LLM
     * export) that shouldn't have to infer it from the raw enum value.
     *
     * @return array<string, string>
     */
    public static function legend(): array
    {
        return [
            self::Compatible->value => 'Nicht abandoned UND neueste Version erlaubt alle required- und recommended-Zielversionen.',
            self::Unclear->value => 'Nicht abandoned UND entweder: required-Zielversion fehlt, aber Release ist jünger als 12 Monate; oder: alle required erlaubt, aber kein getaggtes Release (nur Default-Branch geprüft), kein Laravel-Constraint mit Obergrenze (fehlt oder offen wie ">=5.5") bei Release älter als 3 Jahren (nur Composer) oder eine recommended-Zielversion fehlt.',
            self::Critical->value => 'Nicht abandoned UND required-Zielversion fehlt UND (Release älter als 12 Monate ODER kein Release).',
            self::Abandoned->value => 'Auf Packagist/npm als abandoned bzw. deprecated markiert. Wird nicht mehr gepflegt, daher kein Handlungsbedarf und nie critical.',
        ];
    }
}
