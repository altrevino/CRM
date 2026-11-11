<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tarifario versionado (Configuración → Cotizaciones) y copia de la tarifa usada
 * en cada cotización. Solo agrega tablas y columnas: no modifica datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->unique();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamp('activated_at')->nullable();
            $table->decimal('minimum_hectares', 12, 2);
            $table->decimal('minimum_price', 12, 2);
            $table->json('blocks');
            $table->decimal('km_rate', 10, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->unsignedSmallInteger('validity_days');
            $table->text('payment_terms')->nullable();
            $table->text('service_scope')->nullable();
            $table->text('separate_charges')->nullable();
            $table->json('business');
            $table->string('change_note', 255)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('opportunity_id')->constrained('price_lists')->nullOnDelete();
            $table->json('pricing_snapshot')->nullable();
            $table->string('pricing_override_reason', 500)->nullable();
            $table->foreignUlid('revision_of')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignUlid('duplicated_from')->nullable()->constrained('quotes')->nullOnDelete();
            $table->timestamp('emitted_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('tentative_service_date')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('observations')->nullable();

            $table->decimal('service_amount_calculated', 12, 2)->nullable();
            $table->string('service_adjust_reason', 500)->nullable();
            $table->foreignUlid('service_adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('service_adjusted_at')->nullable();

            $table->decimal('one_way_km', 10, 2)->nullable();
            $table->decimal('route_km_calculated', 10, 2)->nullable();
            $table->decimal('route_km', 10, 2)->nullable();
            $table->string('route_adjust_reason', 500)->nullable();
            $table->decimal('km_rate', 10, 2)->nullable();

            $table->decimal('travel_expenses', 12, 2)->default(0);

            $table->string('discount_type', 10)->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('discount_scope', 10)->nullable();
            $table->decimal('discount_base', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason', 500)->nullable();

            $table->index('valid_until');
        });

        // Tarifa inicial (versión 1). Conserva el IVA que ya estaba configurado.
        $vat = DB::table('settings')->where('key', 'vat_rate')->value('value') ?? '16';
        DB::table('price_lists')->insert([
            'version' => 1,
            'is_current' => true,
            'activated_at' => now(),
            'minimum_hectares' => 500,
            'minimum_price' => 37500,
            'blocks' => json_encode([
                ['up_to' => '1000', 'price' => '65'],
                ['up_to' => '1500', 'price' => '60'],
                ['up_to' => '2000', 'price' => '55'],
                ['up_to' => '3000', 'price' => '50'],
                ['up_to' => '4000', 'price' => '45'],
                ['up_to' => '5000', 'price' => '40'],
                ['up_to' => null, 'price' => '35'],
            ]),
            'km_rate' => 15,
            'vat_rate' => is_numeric($vat) ? $vat : 16,
            'validity_days' => 15,
            // Condiciones y alcance tomados de la propuesta COT-159.
            'payment_terms' => "Anticipo 25% para reservar fecha\n50% siete días antes del censo\n25% contra entrega de reporte",
            'service_scope' => "Cobertura sistemática\nBitácora digital\nAuditoría de detecciones\nAnálisis poblacional\nReporte técnico",
            'separate_charges' => 'Traslado y movilización, y viáticos cuando apliquen, se cobran por separado y se detallan en esta cotización.',
            'business' => json_encode([
                'name' => 'Espectro Soluciones',
                'email' => 'alonso@espectrosoluciones.com.mx',
                'whatsapp' => '+52 81 8022 7298',
                'website' => 'espectrosoluciones.com.mx',
                'address' => '',
            ]),
            'change_note' => 'Tarifa inicial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropConstrainedForeignId('revision_of');
            $table->dropConstrainedForeignId('duplicated_from');
            $table->dropConstrainedForeignId('service_adjusted_by');
            $table->dropIndex(['valid_until']);
            $table->dropColumn([
                'pricing_snapshot', 'pricing_override_reason', 'emitted_at', 'valid_until', 'tentative_service_date',
                'payment_terms', 'observations', 'service_amount_calculated', 'service_adjust_reason', 'service_adjusted_at',
                'one_way_km', 'route_km_calculated', 'route_km', 'route_adjust_reason', 'km_rate', 'travel_expenses',
                'discount_type', 'discount_value', 'discount_scope', 'discount_base', 'discount_amount', 'discount_reason',
            ]);
        });
        Schema::dropIfExists('price_lists');
    }
};
