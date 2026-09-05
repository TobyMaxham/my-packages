<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Compatible = 'compatible';
    case Unclear = 'unclear';
    case Critical = 'critical';
}
