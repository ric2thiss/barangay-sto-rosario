<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  // database/migrations/xxxx_create_incident_types_table.php
public function up(): void
{
    Schema::create('incident_types', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100)->unique();
        $table->string('description', 255)->nullable();
        $table->timestamps();
    });

    // Seed common types
    DB::table('incident_types')->insert([
        ['name' => 'Dispute',           'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Robbery',           'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Assault',           'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Accident',          'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Theft',             'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Vandalism',         'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Noise Complaint',   'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Trespassing',       'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Domestic Violence', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Other',             'description' => null, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Pivot: blotter_record ↔ incident_type (many-to-many)
    Schema::create('blotter_incident_type', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('blotter_id');
        $table->unsignedBigInteger('incident_type_id');
        $table->timestamps();

        $table->foreign('blotter_id')
              ->references('blotter_id')->on('blotter_records')
              ->onDelete('cascade');
        $table->foreign('incident_type_id')
              ->references('id')->on('incident_types')
              ->onDelete('cascade');

        $table->unique(['blotter_id', 'incident_type_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('blotter_incident_type');
    Schema::dropIfExists('incident_types');
}
};
