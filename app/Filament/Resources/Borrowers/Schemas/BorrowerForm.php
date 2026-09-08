<?php

namespace App\Filament\Resources\Borrowers\Schemas;

use App\Enums\BorrowerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BorrowerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(BorrowerType::class)
                    ->required()
                    ->live(),
                TextInput::make('class')
                    ->visible(fn ($get) => $get('type') === BorrowerType::Student->value)
                    ->placeholder('e.g. 10-A'),
                TextInput::make('identifier')
                    ->label(fn (string $operation, ?array $state, $get) => match ($get('type')) {
                        BorrowerType::Student->value => 'Student ID (NIS)',
                        BorrowerType::Teacher->value => 'Teacher ID (NIP)',
                        default => 'Identifier',
                    })
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('surreal_id')
                    ->label('SurrealDB ID')
                    ->helperText('This ID is used for syncing with the school database.')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => !empty($record?->surreal_id)),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
