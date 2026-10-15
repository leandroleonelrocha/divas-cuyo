<?php

namespace App\Filament\Pages;

use App\Imports\ModelProfilesPreview;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Throwable;

class ImportModelProfiles extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Importar perfiles';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.pages.import-model-profiles';

    public $file;

    #[Locked]
    public array $preview = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isVerifiedAdmin() ?? false;
    }

    public function updatedFile(): void
    {
        $this->preview = [];
        $this->resetValidation();
    }

    public function analyze(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->preview = [];
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:5120']], [
            'file.required' => 'Seleccioná un archivo Excel.',
            'file.mimes' => 'El archivo debe ser un Excel .xlsx.',
            'file.extensions' => 'El archivo debe tener extensión .xlsx.',
            'file.max' => 'El Excel no puede superar 5 MB.',
        ]);

        $path = null;
        try {
            $path = $this->file->store('previews', 'profile_imports');
            $this->preview = app(ModelProfilesPreview::class)->read(Storage::disk('profile_imports')->path($path));
        } catch (InvalidArgumentException $exception) {
            $this->addError('file', $exception->getMessage());
        } catch (Throwable $exception) {
            // Do not expose filesystem paths or spreadsheet contents in the response.
            $this->addError('file', 'No se pudo leer el Excel. Revisá que no esté dañado ni protegido por contraseña.');
        } finally {
            if ($path) {
                Storage::disk('profile_imports')->delete($path);
            }
            $this->file->delete();
            $this->file = null;
        }
    }
}
