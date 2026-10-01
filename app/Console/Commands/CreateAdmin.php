<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'crm:create-admin
        {--name= : Nombre completo}
        {--email= : Correo electrónico}
        {--password= : Contraseña (si se omite, se pregunta)}';

    protected $description = 'Crea (o actualiza) un usuario administrador del CRM';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nombre completo', required: true);
        $email = $this->option('email') ?: text('Correo electrónico', required: true);
        $plain = $this->option('password') ?: password('Contraseña (mínimo 8 caracteres, letras y números)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plain],
            ['name' => ['required', 'max:120'], 'email' => ['required', 'email'], 'password' => ['required', Password::min(8)->letters()->numbers()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $plain,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info("Administrador listo: {$user->email}");

        return self::SUCCESS;
    }
}
