<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Filament\Pages\ManageSettings;
use Livewire\Livewire;

class ManageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_manage_settings_page()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/admin/manage-settings')
            ->assertStatus(200);
    }

    public function test_can_save_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $settings = GeneralSetting::factory()->create([
            'id' => 1,
            'library_name' => 'Original Name',
        ]);

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'library_name' => 'New Library Name',
                'default_loan_duration_days' => 14,
                'max_books_per_borrower' => 5,
                'fine_per_day' => 1000,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New Library Name', GeneralSetting::find(1)->library_name);
        $this->assertEquals(14, GeneralSetting::find(1)->default_loan_duration_days);
    }
}
