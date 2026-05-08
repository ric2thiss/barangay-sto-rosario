<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_is_logged_when_resident_is_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Resident::factory()->create();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->user_id,
            'module' => 'Resident',
            'action' => 'Created Resident',
        ]);
    }

    public function test_activity_is_logged_when_resident_is_updated()
    {
        $user = User::factory()->create();
        $resident = Resident::factory()->create();

        $this->actingAs($user);

        $resident->update(['first_name' => 'Updated Name']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->user_id,
            'module' => 'Resident',
            'action' => 'Updated Resident',
            'reference_id' => $resident->resident_id,
        ]);
    }

    public function test_activity_is_not_logged_if_not_authenticated()
    {
        Resident::factory()->create();

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
