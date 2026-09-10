<?php

namespace App\Filament\Resources\Borrowers\Schemas;

use App\Enums\BorrowerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $set('identifier', $state ? $state.'-' : null);
                    }),
                TextInput::make('class')
                    ->visible(fn ($get) => $get('type') === BorrowerType::Student->value)
                    ->placeholder('e.g. 10-A'),
                TextInput::make('identifier')
                    ->label(fn (Get $get): string => match ($get('type')) {
                        BorrowerType::Student->value => 'Student ID (NIS)',
                        BorrowerType::Teacher->value => 'Teacher ID (NIP)',
                        default => 'Identifier',
                    })
                    ->placeholder(fn (Get $get): string => match ($get('type')) {
                        BorrowerType::Student->value => 'student-26271101',
                        BorrowerType::Teacher->value => 'teacher-xxxx',
                        BorrowerType::Staff->value => 'staff-xxxx',
                        default => 'guest-xxxx',
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
