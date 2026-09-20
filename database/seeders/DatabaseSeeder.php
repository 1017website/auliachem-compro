<?php

namespace Database\Seeders;

use App\Services\CompanyPage;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(CompanyPage::class)->import();
        $this->call(CmsUserSeeder::class);
    }
}
