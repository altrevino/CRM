<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Instalación de evaluación: catálogos + datos de demostración.
     * En producción ejecuta solo: php artisan db:seed --class=CatalogSeeder
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
