<?php

namespace App\Filament\Resources\Users\Tables;

use App\Services\SurrealDbClient;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('surreal_student_id')
                    ->label('Linked Student')
                    ->getStateUsing(function ($record) {
                        if (! $record->surreal_student_id) {
                            return 'Not linked';
                        }

                        // We might want to cache this or eager load if possible,
                        // but for Filament simple view this is okay for small sets.
                        $client = app(SurrealDbClient::class);
                        $result = $client->query(<<<'SURQL'
                            SELECT 
                                <-is_student<-person[0].full_name AS full_name 
                            FROM $id;
                        SURQL, ['id' => $record->surreal_student_id]);

                        return $result[0]['result'][0]['full_name'] ?? 'Unknown';
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
