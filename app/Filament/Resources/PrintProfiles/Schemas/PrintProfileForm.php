<?php

namespace App\Filament\Resources\PrintProfiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrintProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sheet layout')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_default'),
                        TextInput::make('page_width_mm')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('page_height_mm')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('grid_columns')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('grid_rows')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('offset_x_mm')
                            ->numeric()
                            ->required(),
                        TextInput::make('offset_y_mm')
                            ->numeric()
                            ->required(),
                        TextInput::make('slot_width_mm')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('slot_height_mm')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('gap_x_mm')
                            ->label('Horizontal Gap (mm)')
                            ->numeric()
                            ->required()
                            ->default(0),
                        TextInput::make('gap_y_mm')
                            ->label('Vertical Gap (mm)')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}
