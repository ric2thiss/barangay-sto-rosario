<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('payment_status')) {
            Schema::create('payment_status', function (Blueprint $table) {
                $table->id();
                $table->string('certificate_type');
                $table->string('purpose');
                $table->string('resident_fname');
                $table->decimal('amount', 10, 2);
                $table->decimal('bir_tax', 10, 2)->default(30.00);
                $table->enum('payment_status', ['Paid', 'Pending'])->default('Pending');
                $table->timestamp('created_at')->useCurrent();
            });

            return;
        }

        Schema::table('payment_status', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_status', 'certificate_type')) {
                $table->string('certificate_type')->nullable();
            }

            if (! Schema::hasColumn('payment_status', 'purpose')) {
                $table->string('purpose')->nullable();
            }

            if (! Schema::hasColumn('payment_status', 'resident_fname')) {
                $table->string('resident_fname')->nullable();
            }

            if (! Schema::hasColumn('payment_status', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('payment_status', 'bir_tax')) {
                $table->decimal('bir_tax', 10, 2)->default(30.00);
            }

            if (! Schema::hasColumn('payment_status', 'payment_status')) {
                $table->enum('payment_status', ['Paid', 'Pending'])->default('Pending');
            }

            if (! Schema::hasColumn('payment_status', 'created_at')) {
                $table->timestamp('created_at')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status');
    }
};
