<?php

namespace App\Filament\Resources\Borrowers\Tables;

use App\Enums\BorrowerType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BorrowersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identifier')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'potential' => 'info',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('loans_count')
                    ->counts('loans')
                    ->label('Active Loans'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(BorrowerType::class),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'potential' => 'Potential',
                        'inactive' => 'Inactive',
                    ]),
            ]);
    }
}
