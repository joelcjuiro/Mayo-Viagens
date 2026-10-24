<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CrearAdmin extends Command
{
    protected $signature = 'mayo:admin {email : Correo para iniciar sesión} {--name=Administrador} {--editor : Crear con rol de editor} {--password= : Contraseña (si no la pones, se te pide)}';

    protected $description = 'Crea (o actualiza) un usuario del panel con una contraseña segura';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('Correo no válido o contraseña de menos de 8 caracteres.');

            return self::FAILURE;
        }

        $rol = $this->option('editor') ? 'editor' : 'admin';
        User::updateOrCreate(['email' => $email], ['name' => $this->option('name'), 'password' => $password, 'role' => $rol, 'activo' => true]);

        $this->info("Listo: {$email} ({$rol}) ya puede entrar en /login.");

        return self::SUCCESS;
    }
}
