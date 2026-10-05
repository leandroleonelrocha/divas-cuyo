<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {--from-env : Lee los datos de ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD}';

    protected $description = 'Crea una cuenta administradora verificada desde la terminal';

    public function handle(): int
    {
        $fromEnv = (bool) $this->option('from-env');

        if (! $fromEnv && ! $this->input->isInteractive()) {
            $this->error('Ejecutá este comando en una terminal interactiva o usá --from-env.');

            return self::FAILURE;
        }

        $name = trim((string) ($fromEnv ? config('admin.name') : $this->ask('Nombre')));
        $email = strtolower(trim((string) ($fromEnv ? config('admin.email') : $this->ask('Email'))));

        $validator = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $fromEnv ? config('admin.password') : $this->secret('Contraseña (mínimo 12 caracteres)', false);
        $confirmation = $fromEnv ? $password : $this->secret('Repetí la contraseña', false);

        if (! is_string($password) || mb_strlen($password) < 12 || $password !== $confirmation) {
            $this->error('La contraseña debe tener al menos 12 caracteres y ambas entradas deben coincidir.');

            return self::FAILURE;
        }

        (new User)->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info('Cuenta administradora creada. Podés ingresar en /admin.');

        return self::SUCCESS;
    }
}
