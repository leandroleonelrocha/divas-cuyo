<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    protected $signature = 'admin:promote {email : Email de la cuenta verificada}';

    protected $description = 'Concede acceso al panel Filament a una cuenta existente';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No existe una cuenta con ese email.');

            return self::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error('La cuenta debe tener el email verificado antes de ser administradora.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("La cuenta {$user->email} ahora puede acceder a /admin.");

        return self::SUCCESS;
    }
}
