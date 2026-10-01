<?php

namespace Tests\Feature;

use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Settings\Users;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_rutas_protegidas_redirigen_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/clientes')->assertRedirect('/login');
    }

    public function test_login_correcto(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_desactivado_no_puede_entrar(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'secreto123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_usuario_desactivado_pierde_la_sesion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_limita_intentos(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_usuario_normal_no_administra_usuarios_ni_configuracion(): void
    {
        $this->actingAsUser();

        $this->get('/configuracion')->assertForbidden();
        $this->get('/configuracion/usuarios')->assertForbidden();
        Livewire::test(Users::class)->assertForbidden();
    }

    public function test_usuario_normal_trabaja_pero_no_elimina(): void
    {
        $this->actingAsUser();
        $client = Client::factory()->create();

        $this->get('/clientes')->assertOk();
        $this->get('/clientes/'.$client->id)->assertOk();

        Livewire::test(ClientsIndex::class)->call('delete', $client->id)->assertForbidden();
        $this->assertNotSoftDeleted($client);
    }

    public function test_administrador_crea_y_desactiva_usuarios(): void
    {
        $admin = $this->actingAsAdmin();

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Nuevo Usuario')
            ->set('email', 'nuevo@espectro.local')
            ->set('password', 'clave1234')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'nuevo@espectro.local')->first();
        $this->assertNotNull($created);
        $this->assertNotSame('clave1234', $created->password);

        Livewire::test(Users::class)->call('toggleActive', $created->id);
        $this->assertFalse($created->fresh()->is_active);

        // No puede desactivarse a sí mismo.
        Livewire::test(Users::class)->call('toggleActive', $admin->id)->assertForbidden();
    }

    public function test_administrador_puede_eliminar_cliente_sin_ranchos(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create();

        Livewire::test(ClientsIndex::class)->call('delete', $client->id);

        $this->assertSoftDeleted($client);
    }

    public function test_urls_usan_ulid(): void
    {
        $this->actingAsUser();
        $client = Client::factory()->create();

        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $client->id);
        $this->get('/clientes/1')->assertNotFound();
    }
}
