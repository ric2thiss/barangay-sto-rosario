<?php

namespace Tests\Feature;

use App\Livewire\Certificates\Create;
use App\Livewire\Certificates\Index;
use App\Models\CertificateRequest;
use App\Models\CertificateType;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateIssuanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_certificate_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resident = Resident::factory()->create();
        $type = CertificateType::factory()->create();

        Livewire::test(Create::class)
            ->set('resident_id', $resident->resident_id)
            ->set('certificate_type_id', $type->certificate_type_id)
            ->set('purpose', 'Employment')
            ->set('date_requested', now()->format('Y-m-d'))
            ->set('status', 'Pending')
            ->call('save')
            ->assertRedirect(route('certificates.index'));

        $this->assertDatabaseHas('certificate_requests', [
            'resident_id' => $resident->resident_id,
            'certificate_type_id' => $type->certificate_type_id,
            'status' => 'Pending',
        ]);
    }

    public function test_can_update_certificate_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = CertificateRequest::create([
            'resident_id' => Resident::factory()->create()->resident_id,
            'certificate_type_id' => CertificateType::factory()->create()->certificate_type_id,
            'purpose' => 'Test',
            'status' => 'Pending',
            'date_requested' => now(),
            'requested_by' => $user->name,
        ]);

        Livewire::test(Index::class)
            ->call('updateStatus', $request->request_id, 'Released');

        $this->assertDatabaseHas('certificate_requests', [
            'request_id' => $request->request_id,
            'status' => 'Released',
        ]);
    }

    public function test_search_certificate_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $resident = Resident::factory()->create(['first_name' => 'UniqueName']);
        CertificateRequest::create([
            'resident_id' => $resident->resident_id,
            'certificate_type_id' => CertificateType::factory()->create()->certificate_type_id,
            'purpose' => 'Specific Purpose',
            'status' => 'Pending',
            'date_requested' => now(),
            'requested_by' => $user->name,
        ]);

        Livewire::test(Index::class)
            ->set('search', 'UniqueName')
            ->assertSee('Specific Purpose')
            ->set('search', 'NonExistent')
            ->assertDontSee('Specific Purpose');
    }
}
