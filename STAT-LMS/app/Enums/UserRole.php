<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum UserRole: string implements HasLabel, HasColor
{
    case SUPER_ADMIN = 'super_admin';
    case COMMITTEE = 'committee';
    case IT = 'it';
    case RR = 'staff/custodian';
    case FACULTY = 'faculty';
    case STUDENT = 'student';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
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
            self::SUPER_ADMIN => 'stat-yellow',
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
            self::SUPER_ADMIN => 4,
            self::COMMITTEE => 3,
            self::IT => 3,
            self::RR => 2,
            self::FACULTY => 2,
            self::STUDENT => 1,
        };
    }

    public function getPrivilegeLevel(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 6,
            self::COMMITTEE => 5,
            self::IT => 4,
            self::RR => 3,
            self::FACULTY => 2,
            self::STUDENT => 1,
        };
    }
}
