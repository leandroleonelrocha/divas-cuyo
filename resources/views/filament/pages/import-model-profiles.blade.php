<x-filament-panels::page>
    <x-filament::section heading="1. Subir Excel">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Seleccioná el archivo con la hoja Perfiles y las columnas Nombre, Tipo y Página.
            Formato .xlsx, hasta 5 MB y 500 filas. Las fotos se cargarán después desde cada perfil.
        </p>
        <form wire:submit="analyze" class="space-y-4">
            <div>
                <label for="profiles-excel" class="block text-sm font-medium">Archivo Excel</label>
                <input id="profiles-excel" type="file" wire:model="file" accept=".xlsx" class="mt-2 block w-full text-sm" />
                @error('file') <p role="alert" class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                <p wire:loading wire:target="file" role="status" class="mt-2 text-sm">Subiendo archivo…</p>
            </div>
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="file,analyze">
                Analizar Excel
            </x-filament::button>
            <span wire:loading wire:target="analyze" role="status">Analizando…</span>
        </form>
    </x-filament::section>

    <x-filament::section heading="2. Revisar datos">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Esta primera etapa permite revisar el Excel. Todavía no crea cuentas ni perfiles.
            El archivo se elimina después del análisis; la vista previa se conserva mientras permanezcas en esta pantalla.
        </p>
        @if ($preview)
            <p role="status" class="mb-4 font-medium">
                {{ count($preview['rows']) }} registros · {{ count($preview['headers']) }} columnas ·
                {{ collect($preview['rows'])->filter(fn ($row) => count($row['warnings']) > 0)->count() }} filas con observaciones
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Vista previa de la hoja Perfiles</caption>
                    <thead><tr>
                        <th scope="col" class="p-3">Fila</th>
                        <th scope="col" class="p-3">Observaciones</th>
                        @foreach ($preview['headers'] as $header)
                            <th scope="col" class="p-3 whitespace-nowrap">{{ $header }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                    @foreach ($preview['rows'] as $row)
                        <tr wire:key="preview-{{ $row['number'] }}" class="border-t border-gray-200 dark:border-gray-700">
                            <td class="p-3 align-top">{{ $row['number'] }}</td>
                            <td class="p-3 align-top">
                                @forelse ($row['warnings'] as $warning)
                                    <p class="min-w-48 text-warning-600">{{ $warning }}</p>
                                @empty
                                    Sin observaciones básicas
                                @endforelse
                            </td>
                            @foreach ($row['data'] as $value)
                                <td class="p-3 align-top"><div class="max-h-40 min-w-32 max-w-xs overflow-auto break-words">{{ $value !== '' ? $value : '—' }}</div></td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">Subí un Excel y seleccioná Analizar Excel para ver su contenido.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
