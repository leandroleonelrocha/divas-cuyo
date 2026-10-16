<?php

namespace App\Filament\Pages;

use App\Services\ModelPhotoBulkImport;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Throwable;
use ZipArchive;

class ImportModelPhotos extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Importar fotos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament.pages.import-model-photos';

    public $file;

    public bool $premarked = true;

    #[Locked]
    public array $preview = [];

    #[Locked]
    public ?string $batch = null;

    #[Locked]
    public ?array $result = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isVerifiedAdmin() ?? false;
    }

    public function updatedFile(): void
    {
        $this->preview = [];
        $this->batch = null;
        $this->result = null;
        $this->resetValidation();
    }

    public function analyze(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->preview = [];
        $this->batch = null;
        $this->result = null;
        $this->validate(['file' => ['required', 'file', 'mimes:zip', 'extensions:zip', 'max:102400']], [
            'file.required' => 'Seleccioná un archivo ZIP.',
            'file.mimes' => 'El archivo debe ser un ZIP.',
            'file.max' => 'El ZIP no puede superar 100 MB.',
        ]);

        $path = null;
        try {
            $path = $this->file->store('batches', 'profile_imports');
            $zipName = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
            $dir = $this->extract(Storage::disk('profile_imports')->path($path), $zipName);
            $this->batch = basename($dir);
            $this->preview = app(ModelPhotoBulkImport::class)->preview($dir);
            if ($this->preview === []) {
                $this->cleanupBatch();
                $this->addError('file', 'El ZIP no contiene carpetas con imágenes. Usá una subcarpeta por ID o slug.');
            }
        } catch (Throwable) {
            $this->cleanupBatch();
            $this->addError('file', 'No se pudo leer el ZIP. Revisá que no esté dañado.');
        } finally {
            if (is_string($path)) {
                Storage::disk('profile_imports')->delete($path);
            }
        }
    }

    public function import(bool $approve): void
    {
        abort_unless(static::canAccess(), 403);
        if ($this->batch === null || $this->preview === []) {
            $this->addError('file', 'Primero analizá un ZIP.');
            return;
        }

        $dir = Storage::disk('profile_imports')->path('extracted'.DIRECTORY_SEPARATOR.$this->batch);
        if (! is_dir($dir)) {
            $this->addError('file', 'El lote expiró. Volvé a subir el ZIP.');
            $this->cleanupBatch();
            return;
        }

        try {
            $this->result = app(ModelPhotoBulkImport::class)->import($dir, auth()->user(), $approve, ! $this->premarked);
        } finally {
            $this->cleanupBatch();
            $this->preview = [];
            $this->batch = null;
        }
    }

    private function extract(string $zipPath, string $zipName): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true || $zip->numFiles < 1) {
            throw new \RuntimeException('ZIP inválido.');
        }
        $dir = Storage::disk('profile_imports')->path(
            'extracted'.DIRECTORY_SEPARATOR.'fotos-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4))
        );
        @mkdir($dir, 0755, true);
        if (! $zip->extractTo($dir) || ! is_dir($dir)) {
            $zip->close();
            throw new \RuntimeException('No se pudo extraer el ZIP.');
        }
        $zip->close();

        // Si el ZIP trae una única carpeta raíz que a su vez contiene
        // carpetas, se sube un nivel. Si solo trae imágenes, se conserva.
        $entries = array_values(array_filter(
            glob($dir.DIRECTORY_SEPARATOR.'*') ?: [],
            fn (string $path): bool => ! str_starts_with(basename($path), '.'),
        ));
        if (count($entries) === 1 && is_dir($entries[0]) && $this->hasSubdirs($entries[0])) {
            foreach (glob($entries[0].DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                @rename($path, $dir.DIRECTORY_SEPARATOR.basename($path));
            }
            @rmdir($entries[0]);
        }

        // Sin subcarpetas pero con imágenes sueltas: se agrupan bajo el
        // nombre del ZIP para matchear un único perfil (ej. zaira.zip).
        if (! $this->hasSubdirs($dir) && $this->hasImages($dir) && $this->safeFolder($zipName) !== '') {
            $target = $dir.DIRECTORY_SEPARATOR.$this->safeFolder($zipName);
            @mkdir($target, 0755, true);
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                if (is_file($path)) {
                    @rename($path, $target.DIRECTORY_SEPARATOR.basename($path));
                }
            }
        }

        return $dir;
    }

    private function hasSubdirs(string $dir): bool
    {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            if (is_dir($path) && ! str_starts_with(basename($path), '.')) {
                return true;
            }
        }

        return false;
    }

    private function hasImages(string $dir): bool
    {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            if (is_file($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                return true;
            }
        }

        return false;
    }

    private function safeFolder(string $name): string
    {
        $name = trim(basename($name));
        if ($name === '' || $name === '.' || str_contains($name, DIRECTORY_SEPARATOR)) {
            return '';
        }

        return $name;
    }

    private function cleanupBatch(): void
    {
        if ($this->batch === null) {
            return;
        }
        $dir = Storage::disk('profile_imports')->path('extracted'.DIRECTORY_SEPARATOR.$this->batch);
        if (str_contains((string) realpath($dir), 'extracted') && is_dir($dir)) {
            $this->deleteDir($dir);
        }
        $this->batch = null;
    }

    private function deleteDir(string $dir): void
    {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            is_dir($path) ? $this->deleteDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
