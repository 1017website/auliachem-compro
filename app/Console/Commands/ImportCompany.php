<?php

namespace App\Console\Commands;

use App\Services\CompanyPage;
use Illuminate\Console\Command;

class ImportCompany extends Command
{
    protected $signature = 'cms:import';

    protected $description = 'Impor konten awal template tanpa menimpa perubahan CMS';

    public function handle(CompanyPage $page): int
    {
        $this->info($page->import().' field konten baru diimpor.');

        return self::SUCCESS;
    }
}
