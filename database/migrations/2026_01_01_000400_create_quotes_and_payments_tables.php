<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('opportunity_id')->constrained('opportunities')->restrictOnDelete();
            $table->unsignedInteger('folio');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('number', 30)->unique();
            $table->date('issued_at');
            $table->decimal('hectares', 12, 2);
            $table->decimal('service_amount', 12, 2);
            $table->decimal('logistics_amount', 12, 2)->default(0);
            $table->boolean('apply_vat');
            $table->decimal('vat_rate', 5, 2)->default(0);
            // Valores calculados por el modelo y conservados como documento histórico.
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('MXN');
            $table->string('status', 20)->default('borrador')->index();
            // 1 solo cuando la cotización está aceptada; el índice único impide dos aceptadas por oportunidad.
            $table->unsignedTinyInteger('accepted_lock')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['folio', 'version']);
            $table->index('issued_at');
            $table->unique(['opportunity_id', 'accepted_lock']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('opportunity_id')->constrained('opportunities')->restrictOnDelete();
            $table->foreignUlid('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->date('paid_at')->index();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('MXN');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('quotes');
    }
};
