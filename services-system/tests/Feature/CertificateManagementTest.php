<?php

namespace Tests\Feature;

use App\Livewire\CertificateTypes\Index as CertificateTypesIndex;
use App\Models\CertificateType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_types_page_is_displayed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('certificate-types.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(CertificateTypesIndex::class);
    }

    public function test_can_create_certificate_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('certificate-types.create')
            ->set('certificate_name', 'Barangay Clearance')
            ->set('price', 100.00)
            ->call('save')
            ->assertRedirect(route('certificate-types.index'));

        $this->assertDatabaseHas('certificate_types', [
            'certificate_name' => 'Barangay Clearance',
            'price' => 100.00,
        ]);
    }

    public function test_can_edit_certificate_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $type = CertificateType::factory()->create([
            'certificate_name' => 'Old Name',
            'price' => 50.00,
        ]);

        Livewire::test('certificate-types.edit', ['certificateType' => $type])
            ->set('certificate_name', 'New Name')
            ->set('price', 150.00)
            ->call('save')
            ->assertRedirect(route('certificate-types.index'));

        $this->assertDatabaseHas('certificate_types', [
            'certificate_type_id' => $type->certificate_type_id,
            'certificate_name' => 'New Name',
            'price' => 150.00,
        ]);
    }

    public function test_can_issue_certificate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resident = \App\Models\Resident::factory()->create();
        $type = CertificateType::factory()->create();

        Livewire::test('certificates.create')
            ->set('resident_id', $resident->resident_id)
            ->set('certificate_type_id', $type->certificate_type_id)
            ->set('purpose', 'For Employment')
            ->set('date_requested', '2023-10-01')
            ->set('status', 'Pending')
            ->call('save')
            ->assertRedirect(route('certificates.index'));

        $this->assertDatabaseHas('certificate_requests', [
            'resident_id' => $resident->resident_id,
            'certificate_type_id' => $type->certificate_type_id,
            'purpose' => 'For Employment',
            'status' => 'Pending',
        ]);
    }
}
