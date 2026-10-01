<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ranch_id')->constrained('ranches')->restrictOnDelete();
            $table->string('stage', 30)->default('prospecto');
            $table->string('service_type', 20);
            $table->decimal('quoted_hectares', 12, 2)->nullable();
            $table->date('tentative_census_date')->nullable();
            $table->date('census_date')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->text('notes')->nullable();
            $table->string('lost_reason', 255)->nullable();
            $table->date('last_contact_at')->nullable();
            $table->timestamp('stage_changed_at')->nullable();
            $table->foreignUlid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'census_date']);
            $table->index('census_date');
        });

        Schema::create('opportunity_species', function (Blueprint $table) {
            $table->foreignUlid('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            $table->primary(['opportunity_id', 'species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_species');
        Schema::dropIfExists('opportunities');
    }
};
