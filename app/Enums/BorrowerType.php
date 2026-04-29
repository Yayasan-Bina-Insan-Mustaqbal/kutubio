<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BorrowerType: string implements HasColor, HasLabel
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Staff = 'staff';
    case Guest = 'guest';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Teacher => 'Teacher',
            self::Staff => 'Staff',
            self::Guest => 'Guest',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Student => 'info',
            self::Teacher => 'success',
            self::Staff => 'warning',
            self::Guest => 'gray',
        };
    }
}
