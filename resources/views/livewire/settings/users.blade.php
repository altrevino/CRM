<div>
    <x-page-header title="Configuración" subtitle="Usuarios con acceso al CRM">
        <x-slot:actions>
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4" /> Nuevo usuario</button>
        </x-slot:actions>
    </x-page-header>
    @include('livewire.settings.nav')

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/60">
                <tr><th class="table-th">Nombre</th><th class="table-th">Correo</th><th class="table-th">Rol</th><th class="table-th">Estado</th><th class="table-th">Último acceso</th><th class="table-th"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr wire:key="u-{{ $user->id }}" class="{{ $user->is_active ? '' : 'opacity-60' }}">
                        <td class="table-td font-medium text-slate-900">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs text-slate-400">(tú)</span>@endif</td>
                        <td class="table-td">{{ $user->email }}</td>
                        <td class="table-td"><x-badge :tone="$user->isAdmin() ? 'brand' : 'slate'">{{ $user->role->label() }}</x-badge></td>
                        <td class="table-td"><x-badge :tone="$user->is_active ? 'emerald' : 'rose'">{{ $user->is_active ? 'Activo' : 'Desactivado' }}</x-badge></td>
                        <td class="table-td">{{ fecha_hora($user->last_login_at) }}</td>
                        <td class="table-td text-right">
                            <button type="button" wire:click="edit('{{ $user->id }}')" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /> Editar</button>
                            @can('deactivate', $user)
                                <button type="button" wire:click="toggleActive('{{ $user->id }}')" @if ($user->is_active) wire:confirm="¿Desactivar a {{ $user->name }}? No podrá iniciar sesión." @endif class="btn-ghost btn-sm {{ $user->is_active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-modal :title="$userId ? 'Editar usuario' : 'Nuevo usuario'" size="md">
        <form id="user-form" wire:submit="save" class="space-y-4">
            <x-field label="Nombre" for="u-name" error="name" required><input id="u-name" type="text" wire:model="name" class="input" required></x-field>
            <x-field label="Correo electrónico" for="u-email" error="email" required><input id="u-email" type="email" wire:model="email" class="input" required autocomplete="off"></x-field>
            <x-field label="Rol" for="u-role" error="role" required hint="Administrador: usuarios, configuración y eliminación de registros."><x-select id="u-role" wire:model="role" :options="$roles" /></x-field>
            <x-field :label="$userId ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña'" for="u-pass" error="password" :required="! $userId" hint="Mínimo 8 caracteres con letras y números.">
                <input id="u-pass" type="password" wire:model="password" class="input" autocomplete="new-password">
            </x-field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-secondary" x-on:click="$wire.showModal = false">Cancelar</button>
            <button type="submit" form="user-form" class="btn-primary">Guardar usuario</button>
        </x-slot:footer>
    </x-modal>
</div>
