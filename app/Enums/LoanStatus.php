<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LoanStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Lost = 'lost';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Returned => 'Returned',
            self::Overdue => 'Overdue',
            self::Lost => 'Lost',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'info',
            self::Returned => 'success',
            self::Overdue => 'danger',
            self::Lost => 'gray',
        };
    }
}
