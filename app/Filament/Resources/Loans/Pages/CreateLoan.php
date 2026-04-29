<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Enums\BookCopyStatus;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function afterCreate(): void
    {
        $this->record->bookCopy->update([
            'status' => BookCopyStatus::Borrowed,
        ]);
    }
}
