<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'cms:admin {email?}';

    protected $description = 'Buat akun admin CMS dengan password yang Anda tentukan';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email admin');
        $name = $this->ask('Nama admin', 'Admin Auliachem');
        $password = $this->secret('Password (minimal 12 karakter)');
        $validator = Validator::make(compact('email', 'name', 'password'), [
            'email' => 'required|email|unique:users,email', 'name' => 'required|string|max:255',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $user = new User(compact('email', 'name', 'password'));
        $user->is_admin = true;
        $user->role = 'admin';
        $user->save();
        $this->info('Admin berhasil dibuat. Masuk melalui /admin/login.');

        return self::SUCCESS;
    }
}
