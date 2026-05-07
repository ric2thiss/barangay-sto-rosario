<?php

namespace Tests\Feature;

use App\Livewire\Residents\Index as ResidentsIndex;
use App\Models\Purok;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_residents_page_is_displayed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('residents.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(ResidentsIndex::class);
    }

    public function test_can_create_resident()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purok = Purok::create(['purok_name' => 'Test Purok']);

        Livewire::test('residents.create')
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('sex', 'Male')
            ->set('civil_status', 'Single')
            ->set('address', '123 Test St')
            ->set('purok_id', $purok->purok_id)
            ->set('date_registered', now()->format('Y-m-d'))
            ->call('save')
            ->assertRedirect(route('residents.index'));

        $this->assertDatabaseHas('residents', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_can_edit_resident()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purok = Purok::create(['purok_name' => 'Test Purok']);
        $resident = Resident::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'address' => '456 Test Ave',
            'purok_id' => $purok->purok_id,
            'date_registered' => now(),
            'residency_status' => 'Active',
        ]);

        Livewire::test('residents.edit', ['resident' => $resident])
            ->set('first_name', 'Janet')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('residents.index'));

        $this->assertDatabaseHas('residents', [
            'resident_id' => $resident->resident_id,
            'first_name' => 'Janet',
        ]);
    }

    public function test_can_view_resident()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purok = Purok::create(['purok_name' => 'Test Purok']);
        $resident = Resident::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'address' => '456 Test Ave',
            'purok_id' => $purok->purok_id,
            'date_registered' => now(),
        ]);

        $response = $this->get(route('residents.show', $resident));

        $response->assertStatus(200);
        $response->assertSeeLivewire('residents.show');
        $response->assertSee('Jane');
    }
}
