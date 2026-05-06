<?php

namespace App\Filament\Resources\PrintProfiles\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PrintProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Measurement Guidance')
                    ->collapsible()
                    ->schema([
                        Placeholder::make('guidance_image')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="flex justify-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <img src="' . asset('images/guide/Print Profile.png') . '" 
                                         alt="Print Profile Guidance" 
                                         class="max-w-full h-auto shadow-sm rounded">
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Use this diagram to measure your sticker sheet dimensions in millimeters (mm).</p>
                            ')),
                    ]),

                Section::make('Sheet layout')
                    ->schema([
                        TextInput::make('name')
...

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
