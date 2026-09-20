<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('content_fields')->where('section', 'contact')->where('type', 'link')->update([
            'values' => json_encode(['id' => '#quote-form', 'en' => '#quote-form', 'zh' => '#quote-form']),
        ]);
    }

    public function down(): void
    {
        DB::table('content_fields')->where('section', 'contact')->where('type', 'link')->update([
            'values' => json_encode(['id' => 'mailto:info@auliachem.com', 'en' => 'mailto:info@auliachem.com', 'zh' => 'mailto:info@auliachem.com']),
        ]);
    }
};
