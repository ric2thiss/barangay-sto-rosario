<?php

namespace Tests\Feature;

use App\Livewire\Residents\Import;
use App\Models\ResidentsImportTemp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentImportEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_and_commit_updates_boolean_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $temp = ResidentsImportTemp::create([
            'first_name_raw' => 'John',
            'last_name_raw' => 'Doe',
            'sex_raw' => 'Male',
            'address_raw' => 'Purok 1',
            'purok_raw' => 'Purok 1',
            'import_status' => 'VALID',
        ]);

        Livewire::actingAs($user)
            ->test(Import::class)
            ->call('editRecord', $temp->temp_id)
            ->set('editingData.sanitary_toilet_raw', '1')
            ->set('editingData.smoker_raw', '0')
            ->set('editingData.binge_drinker_raw', '1')
            ->set('editingData.hpn_raw', '0')
            ->set('editingData.dm_raw', '1')
            ->set('editingData.pwd_raw', '0')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $temp->refresh();
        $this->assertSame(1, (int) $temp->sanitary_toilet_raw);
        $this->assertSame(0, (int) $temp->smoker_raw);
        $this->assertSame(1, (int) $temp->binge_drinker_raw);
        $this->assertSame(0, (int) $temp->hpn_raw);
        $this->assertSame(1, (int) $temp->dm_raw);
        $this->assertSame(0, (int) $temp->pwd_raw);

        Livewire::actingAs($user)
            ->test(Import::class)
            ->call('commitIndividual', $temp->temp_id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('residents', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sanitary_toilet' => 1,
            'smoker' => 0,
            'binge_drinker' => 1,
            'hpn' => 0,
            'dm' => 1,
            'pwd' => 0,
        ]);

        $this->assertDatabaseCount('residents_import_temp', 0);
    }
}
