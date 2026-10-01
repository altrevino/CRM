<?php

namespace Database\Seeders;

use App\Models\MexicanState;
use Illuminate\Database\Seeder;

class MexicanStatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['Aguascalientes', 'AGS'], ['Baja California', 'BC'], ['Baja California Sur', 'BCS'], ['Campeche', 'CAMP'],
            ['Chiapas', 'CHIS'], ['Chihuahua', 'CHIH'], ['Ciudad de México', 'CDMX'], ['Coahuila', 'COAH'],
            ['Colima', 'COL'], ['Durango', 'DGO'], ['Estado de México', 'MEX'], ['Guanajuato', 'GTO'],
            ['Guerrero', 'GRO'], ['Hidalgo', 'HGO'], ['Jalisco', 'JAL'], ['Michoacán', 'MICH'],
            ['Morelos', 'MOR'], ['Nayarit', 'NAY'], ['Nuevo León', 'NL'], ['Oaxaca', 'OAX'],
            ['Puebla', 'PUE'], ['Querétaro', 'QRO'], ['Quintana Roo', 'QROO'], ['San Luis Potosí', 'SLP'],
            ['Sinaloa', 'SIN'], ['Sonora', 'SON'], ['Tabasco', 'TAB'], ['Tamaulipas', 'TAMPS'],
            ['Tlaxcala', 'TLAX'], ['Veracruz', 'VER'], ['Yucatán', 'YUC'], ['Zacatecas', 'ZAC'],
        ];

        foreach ($states as [$name, $abbreviation]) {
            MexicanState::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation]);
        }
    }
}
