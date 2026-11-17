<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link público de la cotización (para enviarlo por WhatsApp), visitas del cliente y
 * su respuesta (aceptar / rechazar) desde esa página. No modifica datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('public_token', 48)->nullable()->unique()->after('number');
            $table->timestamp('client_first_viewed_at')->nullable();
            $table->timestamp('client_last_viewed_at')->nullable();
            $table->unsignedInteger('client_views')->default(0);
            $table->string('client_response', 20)->nullable();
            $table->timestamp('client_responded_at')->nullable();
            $table->string('client_responded_by', 120)->nullable();
            $table->string('client_response_reason', 60)->nullable();
            $table->text('client_response_comment')->nullable();
            $table->string('client_response_ip', 45)->nullable();
            $table->string('client_response_agent', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn([
                'public_token', 'client_first_viewed_at', 'client_last_viewed_at', 'client_views', 'client_response',
                'client_responded_at', 'client_responded_by', 'client_response_reason', 'client_response_comment',
                'client_response_ip', 'client_response_agent',
            ]);
        });
    }
};
