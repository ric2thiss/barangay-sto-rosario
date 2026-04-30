<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CertificateTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default certificate types
        \App\Models\CertificateType::factory()->create([
            'certificate_name' => 'Barangay Clearance',
            'price' => 0.00,
        ]);

        \App\Models\CertificateType::factory()->create([
            'certificate_name' => 'Certificate of Residency',
            'price' => 0.00,
        ]);

        \App\Models\CertificateType::factory()->create([
            'certificate_name' => 'Indigency Certificate',
            'price' => 0.00,
        ]);

        \App\Models\CertificateType::factory()->create([
            'certificate_name' => 'Good Moral Certificate',
            'price' => 0.00,
        ]);

        \App\Models\CertificateType::factory()->create([
            'certificate_name' => 'Barangay Permit',
            'price' => 0.00,
        ]);
    }
}
