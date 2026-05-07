<?php

namespace Tests\Feature;

use App\Livewire\Residents\Import;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ResidentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_is_accessible()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('residents.import'))
            ->assertStatus(200);
    }

    public function test_can_upload_file_and_trigger_import()
    {
        Excel::shouldReceive('import')
            ->once()
            ->andReturn(true);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Import::class)
            ->set('file', UploadedFile::fake()->create('residents.xlsx'))
            ->call('upload')
            ->assertSee('File imported successfully');
    }

    public function test_can_commit_staged_records()
    {
        $user = User::factory()->create();

        \App\Models\ResidentsImportTemp::create([
            'full_name_raw' => 'Juan Dela Cruz',
            'sex_raw' => 'Male',
            'address_raw' => 'Purok 1',
            'purok_raw' => 'Purok 1',
            'import_status' => 'VALID',
        ]);

        Livewire::actingAs($user)
            ->test(Import::class)
            ->call('commit')
            ->assertSee('Successfully committed 1 residents');

        $this->assertDatabaseHas('residents', [
            'first_name' => 'Juan Dela',
            'last_name' => 'Cruz',
        ]);

        $this->assertDatabaseCount('residents_import_temp', 0);
    }
}
