@php
    $scrollTo = isset($scrollTo) ? $scrollTo : 'body';
    $scrollIntoViewJsSnippet = ($scrollTo !== false) ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()" : '';
@endphp
<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-3">
            <p class="hidden text-sm text-slate-500 sm:block">
                Mostrando <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>
                a <span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
                de <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
            </p>
            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="btn-secondary btn-sm opacity-50">Anterior</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="btn-secondary btn-sm">Anterior</button>
                @endif

                <span class="px-2 text-sm text-slate-500 tabular-nums">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="btn-secondary btn-sm">Siguiente</button>
                @else
                    <span class="btn-secondary btn-sm opacity-50">Siguiente</span>
                @endif
            </div>
        </nav>
    @endif
</div>
