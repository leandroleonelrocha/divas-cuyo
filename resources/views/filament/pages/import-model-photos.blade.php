<x-filament-panels::page>
    <x-filament::section heading="1. Subir ZIP">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Una subcarpeta por modelo, nombrada con su <strong>ID o slug</strong> (ej. <code>8/</code>, <code>anastasia/</code>).
            Imágenes JPG/PNG/WebP, hasta 100 MB por ZIP. Se respeta el máximo de 5 fotos por perfil.
        </p>
        <form wire:submit="analyze" class="space-y-4">
            <div>
                <span class="block text-sm font-medium">Archivo ZIP</span>
                <div style="display:flex;align-items:center;gap:12px;margin-top:8px;flex-wrap:wrap;">
                    <label for="photos-zip" style="display:inline-flex;align-items:center;gap:8px;background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap;">Elegir archivo ZIP</label>
                    <span style="font-size:13px;opacity:.75;">{{ $file ? $file->getClientOriginalName() : 'Ningún archivo seleccionado' }}</span>
                </div>
                <input id="photos-zip" type="file" wire:model="file" accept=".zip"
                    style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;" />
                <p style="margin-top:8px;font-size:12px;opacity:.6;">ZIP con una subcarpeta por ID o slug, hasta 100 MB.</p>
                @error('file') <p role="alert" class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                <p wire:loading wire:target="file" role="status" class="mt-2 text-sm">Subiendo archivo…</p>
            </div>
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="file,analyze">
                Analizar ZIP
            </x-filament::button>
            <span wire:loading wire:target="analyze" role="status">Analizando…</span>
        </form>
    </x-filament::section>

    <x-filament::section heading="2. Revisar e importar">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Todavía no se importó nada. Elegí si las fotos quedan pendientes de moderación o aprobadas en el acto
            (la primera aprobada de cada perfil queda como principal si no tiene una).
        </p>
        @if ($preview)
            <p role="status" class="mb-4 font-medium">
                {{ count($preview) }} carpetas ·
                {{ collect($preview)->sum('files') }} imágenes ·
                {{ collect($preview)->whereNotNull('warning')->count() }} con observaciones
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Vista previa de carpetas</caption>
                    <thead><tr>
                        <th scope="col" class="p-3">Carpeta</th>
                        <th scope="col" class="p-3">Perfil</th>
                        <th scope="col" class="p-3">Imágenes</th>
                        <th scope="col" class="p-3">Observaciones</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($preview as $row)
                        <tr wire:key="folder-{{ $row['folder'] }}" class="border-t border-gray-200 dark:border-gray-700">
                            <td class="p-3 font-mono">{{ $row['folder'] }}</td>
                            <td class="p-3">{{ $row['stageName'] ?? '—' }}@if($row['profileId']) <span class="text-gray-500">#{{ $row['profileId'] }}</span>@endif</td>
                            <td class="p-3">{{ $row['files'] }}</td>
                            <td class="p-3">@if($row['warning'])<p class="text-warning-600">{{ $row['warning'] }}</p>@else Sin observaciones @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                    <input type="checkbox" wire:model="premarked" style="width:18px;height:18px;accent-color:#dc2626;" />
                    Ya traen marca de agua (no aplicar la del sitio)
                </label>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button wire:click="import(false)" wire:loading.attr="disabled" wire:target="import">
                    Importar pendientes
                </x-filament::button>
                <x-filament::button wire:click="import(true)" color="success" wire:loading.attr="disabled" wire:target="import">
                    Importar y aprobar
                </x-filament::button>
                <span wire:loading wire:target="import" role="status">Importando…</span>
            </div>
        @endif
        @if ($result)
            <p role="status" class="font-medium">
                {{ $result['imported'] }} fotos importadas en {{ $result['profiles'] }} perfiles · {{ $result['omitted'] }} omitidas.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
