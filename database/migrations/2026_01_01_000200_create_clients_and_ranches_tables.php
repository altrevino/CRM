<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 150)->index();
            $table->string('phone', 20)->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ranches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('name', 150)->index();
            $table->string('municipality', 100)->nullable()->index();
            $table->foreignId('state_id')->nullable()->constrained('mexican_states')->nullOnDelete();
            $table->text('maps_url')->nullable();
            $table->decimal('km_round_trip', 8, 1)->nullable();
            $table->string('fence_type', 10)->nullable()->index();
            $table->decimal('total_hectares', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranches');
        Schema::dropIfExists('clients');
    }
};
