<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Services\SurrealDbClient;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Select::make('surreal_student_id')
                    ->label('Linked Student (SurrealDB)')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $client = app(SurrealDbClient::class);
                        // Using a fuzzy search on person full_name linked to student
                        $result = $client->query(<<<'SURQL'
                            SELECT 
                                id, 
                                student_code,
                                <-is_student<-person[0].full_name AS full_name 
                            FROM student 
                            WHERE <-is_student<-person[0].full_name @@ $search
                            LIMIT 10;
                        SURQL, ['search' => $search]);

                        $students = $result[0]['result'] ?? [];

                        return collect($students)->mapWithKeys(function ($student) {
                            return [$student['id'] => ($student['full_name'] ?? 'Unknown')." ({$student['student_code']})"];
                        })->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $client = app(SurrealDbClient::class);
                        $result = $client->query(<<<'SURQL'
                            SELECT 
                                id, 
                                student_code,
                                <-is_student<-person[0].full_name AS full_name 
                            FROM $id;
                        SURQL, ['id' => $value]);

                        $student = $result[0]['result'][0] ?? null;

                        return $student ? ($student['full_name'] ?? 'Unknown')." ({$student['student_code']})" : null;
                    }),
            ]);
    }
}
