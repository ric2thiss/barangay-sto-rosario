<?php

use App\Models\BlotterRecord;
use App\Models\Resident;
use App\Models\User;

beforeEach(function () {
    // Create a user for authentication
    $this->user = User::factory()->create();

    // Create some residents
    $this->resident1 = Resident::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->resident2 = Resident::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);
});

test('can view blotter index page', function () {
    $response = $this->actingAs($this->user)->get('/blotter');

    $response->assertStatus(200);
    $response->assertSee('Blotter Records');
});

test('can view create blotter page', function () {
    $response = $this->actingAs($this->user)->get('/blotter/create');

    $response->assertStatus(200);
    $response->assertSee('New Blotter Record');
    $response->assertSee($this->resident1->full_name);
    $response->assertSee($this->resident2->full_name);
});

test('can create blotter record', function () {
    $data = [
        'form_number' => 'KP Form Number 1',
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
        'incident_type' => 'Noise Complaint',
        'purpose' => 'To file a complaint about loud noise',
        'incident_details' => 'The respondent has been making loud noises late at night',
        'incident_date' => '2026-01-28',
        'status' => 'Pending',
    ];

    $response = $this->actingAs($this->user)->post('/blotter', $data);

    $response->assertStatus(302); // Redirect
    $response->assertRedirect('/blotter');

    $this->assertDatabaseHas('blotter_records', [
        'form_number' => 'KP Form Number 1',
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
        'incident_type' => 'Noise Complaint',
        'status' => 'Pending',
    ]);

    // Check if DOCX file was created
    $filename = str_replace(' ', '_', 'KP Form Number 1').'.docx';
    expect(Storage::disk('public')->exists('blotter_docs/'.$filename))->toBeTrue();
});

test('cannot create blotter with duplicate form number', function () {
    // Create first blotter
    BlotterRecord::factory()->create([
        'form_number' => 'KP Form Number 1',
    ]);

    $data = [
        'form_number' => 'KP Form Number 1', // Duplicate
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
        'incident_type' => 'Noise Complaint',
        'purpose' => 'Test purpose',
        'incident_details' => 'Test details',
        'incident_date' => '2026-01-28',
        'status' => 'Pending',
    ];

    $response = $this->actingAs($this->user)->post('/blotter', $data);

    $response->assertSessionHasErrors(['form_number']);
});

test('can view blotter details', function () {
    $blotter = BlotterRecord::factory()->create([
        'form_number' => 'KP Form Number 1',
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
    ]);

    $response = $this->actingAs($this->user)->get("/blotter/{$blotter->blotter_id}");

    $response->assertStatus(200);
    $response->assertSee('KP Form Number 1');
    $response->assertSee($this->resident1->full_name);
    $response->assertSee($this->resident2->full_name);
});

test('can edit blotter record', function () {
    $blotter = BlotterRecord::factory()->create([
        'form_number' => 'KP Form Number 1',
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
        'status' => 'Pending',
    ]);

    $updatedData = [
        'form_number' => 'KP Form Number 1 Updated',
        'complainant_id' => $this->resident1->resident_id,
        'respondent_id' => $this->resident2->resident_id,
        'incident_type' => 'Updated Incident Type',
        'purpose' => 'Updated purpose',
        'incident_details' => 'Updated details',
        'incident_date' => '2026-01-29',
        'status' => 'Completed',
    ];

    $response = $this->actingAs($this->user)->put("/blotter/{$blotter->blotter_id}", $updatedData);

    $response->assertStatus(302);
    $response->assertRedirect('/blotter');

    $this->assertDatabaseHas('blotter_records', [
        'blotter_id' => $blotter->blotter_id,
        'form_number' => 'KP Form Number 1 Updated',
        'incident_type' => 'Updated Incident Type',
        'status' => 'Completed',
    ]);
});

test('can delete blotter record', function () {
    $blotter = BlotterRecord::factory()->create([
        'form_number' => 'KP Form Number 1',
    ]);

    $response = $this->actingAs($this->user)->delete("/blotter/{$blotter->blotter_id}");

    $response->assertStatus(302);
    $response->assertRedirect('/blotter');

    $this->assertDatabaseMissing('blotter_records', [
        'blotter_id' => $blotter->blotter_id,
    ]);

    // Check if DOCX file was deleted
    $filename = str_replace(' ', '_', 'KP Form Number 1').'.docx';
    expect(Storage::disk('public')->exists('blotter_docs/'.$filename))->toBeFalse();
});
