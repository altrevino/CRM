<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

/** Re-renderiza la pantalla cuando un formulario modal guarda cambios. */
trait RefreshesOnSave
{
    #[On('crm:refresh')]
    public function refreshAfterSave(): void
    {
        // Livewire vuelve a renderizar y recalcula las propiedades #[Computed].
    }
}
