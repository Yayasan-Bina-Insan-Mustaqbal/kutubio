<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Services\SurrealDbClient;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('SurrealDB Student Identity')
                    ->schema([
                        TextEntry::make('surreal_student_id')
                            ->label('Student ID')
                            ->placeholder('Not linked'),
                        
                        TextEntry::make('student_name')
                            ->label('Full Name')
                            ->getStateUsing(function ($record) {
                                if (! $record->surreal_student_id) return null;
                                return self::getStudentData($record->surreal_student_id)['full_name'] ?? 'Unknown';
                            }),

                        TextEntry::make('student_code')
                            ->label('Student Code')
                            ->getStateUsing(function ($record) {
                                if (! $record->surreal_student_id) return null;
                                return self::getStudentData($record->surreal_student_id)['student_code'] ?? 'Unknown';
                            }),

                        TextEntry::make('class_name')
                            ->label('Current Class')
                            ->getStateUsing(function ($record) {
                                if (! $record->surreal_student_id) return null;
                                return self::getStudentData($record->surreal_student_id)['class_name'] ?? 'Not enrolled';
                            }),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => filled($record->surreal_student_id)),
            ]);
    }

    protected static function getStudentData(string $id): array
    {
        return \Illuminate\Support\Facades\Cache::remember("surreal_student_{$id}", now()->addHours(1), function () use ($id) {
            $client = app(SurrealDbClient::class);
            $result = $client->query(<<<'SURQL'
                SELECT 
                    student_code,
                    <-is_student<-person[0].full_name AS full_name,
                    ->has_enrollment->enrollment[0]->in_class->class_group[0].class_name AS class_name
                FROM $id;
            SURQL, ['id' => $id]);

            return $result[0]['result'][0] ?? [];
        });
    }
}
