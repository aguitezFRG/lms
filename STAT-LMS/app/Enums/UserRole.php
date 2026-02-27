<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum UserRole: string implements HasLabel, HasColor
{
    case COMMITTEE = 'committee';
    case IT = 'it';
    case RR = 'staff/custodian';
    case FACULTY = 'faculty';
    case STUDENT = 'student';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IT => 'IT Administrator',
            self::COMMITTEE => 'Reading Room Committee',
            self::RR => 'Staff/Custodian',
            self::FACULTY => 'Faculty Member',
            self::STUDENT => 'Student User',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::IT => 'danger',
            self::COMMITTEE => 'warning',
            self::RR => 'success',
            self::FACULTY => 'primary',
            self::STUDENT => 'stat-blue',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getAccessLevel(): int
    {
        return match ($this) {
            self::COMMITTEE => 3,
            self::IT => 3,
            self::RR => 2,
            self::FACULTY => 2,
            self::STUDENT => 1,
        };
    }
}