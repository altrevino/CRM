<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('status', 20)->default('pendiente');
            $table->foreignUlid('opportunity_id')->nullable()->constrained('opportunities')->cascadeOnDelete();
            $table->foreignUlid('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignUlid('ranch_id')->nullable()->constrained('ranches')->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40)->index();
            $table->string('description', 500);
            $table->string('subject_type', 60)->nullable();
            $table->string('subject_id', 26)->nullable();
            $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUlid('ranch_id')->nullable()->constrained('ranches')->nullOnDelete();
            $table->foreignUlid('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['opportunity_id', 'created_at']);
            $table->index(['ranch_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('tasks');
    }
};
