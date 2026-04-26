<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JobLogStatus: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Ongoing = 'ongoing';
    case Succeed = 'succeed';
    case Failed = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Ongoing => 'Ongoing',
            self::Succeed => 'Succeed',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Ongoing => 'warning',
            self::Succeed => 'success',
            self::Failed => 'danger',
        };
    }
}
