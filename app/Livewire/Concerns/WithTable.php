<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Session;
use Livewire\WithPagination;

/**
 * Búsqueda, orden, paginación y limpieza de filtros para tablas.
 * Los valores se guardan en sesión para conservarlos al navegar.
 * Cada componente define $sortable (columnas permitidas) y $filterProperties.
 */
trait WithTable
{
    use WithPagination;

    #[Session]
    public string $search = '';

    #[Session]
    public string $sortField = '';

    #[Session]
    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortable(), true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if ($property === 'search' || in_array($property, $this->filterProperties(), true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        foreach ($this->filterProperties() as $property) {
            $this->reset($property);
        }
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        if ($this->search !== '') {
            return true;
        }

        foreach ($this->filterProperties() as $property) {
            if (filled($this->{$property})) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    protected function sortable(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function filterProperties(): array
    {
        return [];
    }

    protected function direction(): string
    {
        return $this->sortDirection === 'desc' ? 'desc' : 'asc';
    }
}
