<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Catálogos base (estados, especies, formas de pago, configuración) en cada prueba. */
    protected bool $seed = true;

    protected string $seeder = CatalogSeeder::class;

    protected function actingAsAdmin(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }
}
