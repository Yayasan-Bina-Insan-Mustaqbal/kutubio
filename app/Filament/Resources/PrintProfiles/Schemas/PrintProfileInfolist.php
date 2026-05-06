<?php

namespace App\Filament\Resources\PrintProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PrintProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Measurement Guidance')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('guidance_image')
                            ->hiddenLabel()
                            ->default(new HtmlString('
                                <div class="flex justify-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <img src="' . asset('images/guide/Print Profile.png') . '" 
                                         alt="Print Profile Guidance" 
                                         class="max-w-full h-auto shadow-sm rounded">
                                </div>
                            '))
                            ->html(),
                    ]),

                Section::make('Print profile')
                    ->schema([
                        TextEntry::make('name'),
                        IconEntry::make('is_default')
                            ->boolean(),
                        TextEntry::make('page_width_mm')
                            ->suffix(' mm'),
                        TextEntry::make('page_height_mm')
                            ->suffix(' mm'),
                        TextEntry::make('grid_columns')
                            ->numeric(),
                        TextEntry::make('grid_rows')
                            ->numeric(),
                        TextEntry::make('offset_x_mm')
                            ->suffix(' mm'),
                        TextEntry::make('offset_y_mm')
                            ->suffix(' mm'),
                        TextEntry::make('slot_width_mm')
                            ->suffix(' mm'),
                        TextEntry::make('slot_height_mm')
                            ->suffix(' mm'),
                        TextEntry::make('gap_x_mm')
                            ->label('Horizontal Gap')
                            ->suffix(' mm'),
                        TextEntry::make('gap_y_mm')
                            ->label('Vertical Gap')
                            ->suffix(' mm'),
                    ])
                    ->columns(2),
            ]);
    }
}
