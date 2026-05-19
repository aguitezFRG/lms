<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RepositoryChangeType: string implements HasColor, HasLabel
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case LOGIN = 'login';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::RESTORE => 'Restore',
            self::LOGIN => 'Login',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CREATE => 'success',
            self::UPDATE => 'primary',
            self::DELETE => 'danger',
            self::RESTORE => 'warning',
            self::LOGIN => 'info',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
