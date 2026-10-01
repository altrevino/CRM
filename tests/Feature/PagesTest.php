<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Ranch;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Tests\TestCase;

/** Todas las pantallas cargan con los datos de demostración. */
class PagesTest extends TestCase
{
    public function test_todas_las_pantallas_responden(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->actingAs(User::where('email', 'admin@espectro.local')->first());

        $paths = [
            '/', '/pipeline', '/clientes', '/ranchos', '/cotizaciones', '/pagos', '/censos', '/censos?vista=lista',
            '/seguimientos', '/servicios/nuevo', '/configuracion', '/configuracion/usuarios',
            '/configuracion/especies', '/configuracion/formas-de-pago',
            '/clientes/'.Client::first()->id,
            '/ranchos/'.Ranch::first()->id,
        ];
        foreach (Opportunity::pluck('id') as $id) {
            $paths[] = '/servicios/'.$id;
            $paths[] = '/servicios/'.$id.'?tab=cotizaciones';
            $paths[] = '/servicios/'.$id.'?tab=actividad';
        }

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_usuario_normal_ve_las_pantallas_de_trabajo(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->actingAs(User::where('email', 'operaciones@espectro.local')->first());

        foreach (['/', '/pipeline', '/clientes', '/ranchos', '/cotizaciones', '/pagos', '/censos', '/seguimientos', '/servicios/nuevo', '/servicios/'.Opportunity::first()->id] as $path) {
            $this->get($path)->assertOk()->assertDontSee('Configuración</span>', false);
        }
    }
}
