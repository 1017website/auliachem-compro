<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class ProductionCheck extends Command
{
    protected $signature = 'cms:production-check';

    protected $description = 'Periksa konfigurasi dasar sebelum website dipublikasikan';

    public function handle(): int
    {
        try {
            $activeCmsUsers = User::where('is_admin', true)->where('is_active', true)->count();
            $databaseStatus = 'terhubung ('.config('database.default').')';
        } catch (Throwable $exception) {
            $activeCmsUsers = 0;
            $databaseStatus = 'gagal: '.$exception->getMessage();
        }

        $publicStorageLink = file_exists(public_path('storage'));
        $publicStorageRoute = config('filesystems.disks.public.serve') === true;
        $publicStorageStatus = match (true) {
            $publicStorageLink => 'symbolic link tersedia',
            $publicStorageRoute => 'route /storage/{path} aktif',
            default => 'belum tersedia',
        };

        $checks = [
            ['APP_ENV', app()->environment('production'), app()->environment()],
            ['APP_DEBUG', ! config('app.debug'), config('app.debug') ? 'true' : 'false'],
            ['APP_URL', str_starts_with(config('app.url'), 'https://'), config('app.url')],
            ['APP_KEY', filled(config('app.key')), filled(config('app.key')) ? 'tersedia' : 'kosong'],
            ['MAIL_MAILER', ! in_array(config('mail.default'), ['log', 'array'], true), config('mail.default')],
            ['QUOTE_RECIPIENT', filter_var(config('company.quote_recipient'), FILTER_VALIDATE_EMAIL) !== false, config('company.quote_recipient')],
            ['Database', ! str_starts_with($databaseStatus, 'gagal:'), $databaseStatus],
            ['CMS user aktif', $activeCmsUsers > 0, (string) $activeCmsUsers],
            ['storage writable', is_writable(storage_path()), is_writable(storage_path()) ? 'ya' : 'tidak'],
            ['public storage', $publicStorageLink || $publicStorageRoute, $publicStorageStatus],
        ];
        $this->table(['Pemeriksaan', 'Status', 'Nilai'], array_map(fn ($check) => [$check[0], $check[1] ? 'OK' : 'PERLU DIATUR', $check[2]], $checks));
        $failed = collect($checks)->where(fn ($check) => ! $check[1])->count();
        $failed ? $this->warn($failed.' item perlu diselesaikan sebelum production.') : $this->info('Konfigurasi dasar siap untuk production.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
