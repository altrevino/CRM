<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige datos: cotizaciones eliminadas que conservaban el candado de "aceptada"
 * e impedían aceptar otra cotización del mismo servicio (error 500).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')
            ->whereNotNull('accepted_lock')
            ->where(fn ($q) => $q->whereNotNull('deleted_at')->orWhere('status', '!=', 'aceptada'))
            ->update(['accepted_lock' => null]);
    }

    public function down(): void
    {
        // Corrección de datos; no se revierte.
    }
};
