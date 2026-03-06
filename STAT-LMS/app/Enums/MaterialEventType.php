<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum MaterialEventType: string implements HasLabel, HasColor
{
    case VIEW = 'view';
    case REQUEST = 'request';
    case ACCESSED = 'accessed';
    case BORROW = 'borrow';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VIEW => 'View',
            self::REQUEST => 'Request',
            self::ACCESSED => 'Accessed',
            self::BORROW => 'Borrow',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::VIEW => 'gray',
            self::REQUEST => 'success',
            self::ACCESSED => 'primary',
            self::BORROW => 'stat-yellow',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }
}