<?php

namespace App\Filament\Pages;

use App\Models\GeneralSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = GeneralSetting::find(1);
        
        if (! $settings) {
            $settings = GeneralSetting::create([
                'id' => 1,
                'library_name' => 'Perpustakaan SD Islam Insan Taqwa',
            ]);
        }

        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Library Information')
                    ->description('General information about your library.')
                    ->schema([
                        TextInput::make('library_name')
                            ->label('Library Name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('library_address')
                            ->label('Library Address')
                            ->rows(3),
                        TextInput::make('library_employee_name')
                            ->label('Library Employee Name')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Loan Configuration')
                    ->description('Rules for borrowing books.')
                    ->schema([
                        TextInput::make('default_loan_duration_days')
                            ->label('Default Loan Duration (Days)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('max_books_per_borrower')
                            ->label('Max Books per Borrower')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('fine_per_day')
                            ->label('Fine per Day (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        GeneralSetting::find(1)->update($data);

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }
}
