<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Species;
use Illuminate\Database\Seeder;

/** Catálogos base. Seguro de ejecutar en producción (no duplica). */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MexicanStatesSeeder::class);

        $species = [
            'Venado cola blanca', 'Venado bura', 'Pecarí de collar', 'Jabalí europeo', 'Guajolote silvestre',
            'Codorniz cotuí', 'Codorniz escamosa', 'Paloma alas blancas', 'Borrego cimarrón', 'Berrendo',
            'Antílope nilgai', 'Borrego berberisco (aoudad)', 'Coyote', 'Puma', 'Gato montés',
        ];
        foreach ($species as $name) {
            Species::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        foreach (['Transferencia', 'Efectivo', 'Cheque', 'Otro'] as $i => $name) {
            PaymentMethod::firstOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $i + 1]);
        }

        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
