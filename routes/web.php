<?php

use App\Http\Controllers\Auth\LoginController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('/', Livewire\Dashboard::class)->name('dashboard');
    Route::livewire('/pipeline', Livewire\Pipeline\Board::class)->name('pipeline');

    Route::livewire('/clientes', Livewire\Clients\Index::class)->name('clients.index');
    Route::livewire('/clientes/{client}', Livewire\Clients\Show::class)->name('clients.show');

    Route::livewire('/ranchos', Livewire\Ranches\Index::class)->name('ranches.index');
    Route::livewire('/ranchos/{ranch}', Livewire\Ranches\Show::class)->name('ranches.show');

    Route::livewire('/servicios/nuevo', Livewire\Opportunities\Create::class)->name('opportunities.create');
    Route::livewire('/servicios/{opportunity}', Livewire\Opportunities\Show::class)->name('opportunities.show');

    Route::livewire('/cotizaciones', Livewire\Quotes\Index::class)->name('quotes.index');
    Route::livewire('/pagos', Livewire\Payments\Index::class)->name('payments.index');
    Route::livewire('/censos', Livewire\Census\Index::class)->name('census.index');
    Route::livewire('/seguimientos', Livewire\Tasks\Index::class)->name('tasks.index');

    Route::middleware('can:manage-settings')->prefix('configuracion')->group(function () {
        Route::livewire('/', Livewire\Settings\General::class)->name('settings.general');
        Route::livewire('/usuarios', Livewire\Settings\Users::class)->name('settings.users');
        Route::livewire('/especies', Livewire\Settings\SpeciesCatalog::class)->name('settings.species');
        Route::livewire('/formas-de-pago', Livewire\Settings\PaymentMethods::class)->name('settings.payment-methods');
    });
});
