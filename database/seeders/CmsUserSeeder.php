<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CmsUserSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = config('cms.bootstrap_user');
        $configured = collect($credentials)->filter(fn ($value) => filled($value));

        if ($configured->isEmpty()) {
            if (User::where('is_admin', true)->where('is_active', true)->exists()) {
                $this->command?->info('User CMS aktif sudah tersedia; bootstrap user dilewati.');

                return;
            }

            if (app()->environment('production')) {
                throw new RuntimeException(
                    'CMS_BOOTSTRAP_NAME, CMS_BOOTSTRAP_EMAIL, dan CMS_BOOTSTRAP_PASSWORD wajib diisi sebelum seeding production.'
                );
            }

            $this->command?->warn('Bootstrap user dilewati karena CMS_BOOTSTRAP_* belum diatur.');

            return;
        }

        try {
            Validator::make($credentials, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:12'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new RuntimeException(
                'Konfigurasi bootstrap user tidak valid: '.implode(' ', $exception->validator->errors()->all()),
                previous: $exception,
            );
        }

        $user = User::firstOrCreate(
            ['email' => $credentials['email']],
            [
                'name' => $credentials['name'],
                'password' => $credentials['password'],
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
                'role' => 'developer',
            ],
        );

        if ($user->wasRecentlyCreated) {
            $this->command?->info('Bootstrap user CMS berhasil dibuat: '.$user->email);
        } else {
            $this->command?->info('Bootstrap user CMS sudah tersedia; data dan password tidak diubah.');
        }
    }
}
