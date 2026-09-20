<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\ContentField;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cms:media-audit', function () {
    $used = ContentField::where('type','image')->get()->flatMap(fn($field)=>collect([$field->values['id'] ?? null,$field->mobile_value]))->filter()->map(fn($path)=>str_replace('/storage/','',$path))->merge(Product::whereNotNull('image_path')->pluck('image_path'))->all();
    $orphans = collect(Storage::disk('public')->allFiles())->filter(fn($path)=>str_starts_with($path,'cms/')||str_starts_with($path,'products/'))->diff($used)->values();
    $this->info($orphans->isEmpty() ? 'Tidak ada file media yatim.' : $orphans->count().' file tidak lagi digunakan:');
    $orphans->each(fn($path)=>$this->line($path));
})->purpose('Mendeteksi file unggahan yang tidak lagi digunakan tanpa menghapusnya.');
