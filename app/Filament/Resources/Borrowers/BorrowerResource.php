<?php

namespace App\Filament\Resources\Borrowers;

use App\Enums\BorrowerType;
use App\Filament\Resources\Borrowers\Pages\CreateBorrower;
use App\Filament\Resources\Borrowers\Pages\EditBorrower;
use App\Filament\Resources\Borrowers\Pages\ImportBorrowers;
use App\Filament\Resources\Borrowers\Pages\ListBorrowers;
use App\Filament\Resources\Borrowers\Pages\ViewBorrower;
use App\Models\Borrower;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

use App\Filament\Resources\Borrowers\Schemas\BorrowerForm;
use App\Filament\Resources\Borrowers\Tables\BorrowersTable;

class BorrowerResource extends Resource
{
    protected static ?string $model = Borrower::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Circulation';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BorrowerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BorrowersTable::configure($table)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBorrowers::route('/'),
            'create' => CreateBorrower::route('/create'),
            'import' => ImportBorrowers::route('/import'),
            'view' => ViewBorrower::route('/{record}'),
            'edit' => EditBorrower::route('/{record}/edit'),
        ];
    }
}
