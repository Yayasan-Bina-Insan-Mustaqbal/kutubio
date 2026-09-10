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
                    ->afterStateUpdated(function (Set $set, BorrowerType|string|null $state): void {
                        $type = $state instanceof BorrowerType ? $state->value : $state;
                        $set('identifier', $type ? $type.'-' : null);
                    }),
                Select::make('class')
                    ->label('Class')
                    ->options(self::classOptions())
                    ->searchable()
                    ->visible(fn (Get $get): bool => self::isStudent($get('type')))
                    ->required(fn (Get $get): bool => self::isStudent($get('type'))),
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

    private static function isStudent(BorrowerType|string|null $type): bool
    {
        return ($type instanceof BorrowerType ? $type->value : $type) === BorrowerType::Student->value;
    }
    private static function classOptions(): array
    {
        return collect(range(1, 12))
            ->flatMap(fn (int $grade): array => collect(range('A', 'I'))
                ->mapWithKeys(fn (string $section): array => ["{$grade}{$section}" => "{$grade}{$section}"])
                ->all())
            ->all();
    }
}
