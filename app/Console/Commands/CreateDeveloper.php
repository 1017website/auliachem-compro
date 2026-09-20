<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateDeveloper extends Command
{
    protected $signature = 'cms:developer {email?}';

    protected $description = 'Buat atau perbarui akun developer CMS';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email developer');
        $name = $this->ask('Nama developer', 'Developer Auliachem');
        $password = $this->secret('Password (minimal 12 karakter)');
        $validator = Validator::make(compact('email', 'name', 'password'), [
            'email' => 'required|email', 'name' => 'required|string|max:255',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->fill(compact('email', 'name', 'password'));
        $user->is_admin = true;
        $user->role = 'developer';
        $user->save();
        $this->info('Akun developer siap. Masuk melalui /admin/login.');

        return self::SUCCESS;
    }
}
