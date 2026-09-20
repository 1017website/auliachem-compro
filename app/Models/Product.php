<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'chemical' => 'Bahan baku kimia',
        'industrial-salt' => 'Garam industri',
        'laboratory-chemical' => 'Bahan kimia laboratorium',
        'laboratory-solution' => 'Solusi laboratorium',
    ];

    protected $fillable = [
        'name_id', 'name_en', 'name_zh', 'slug', 'category', 'sku', 'grade', 'packaging', 'application',
        'summary_id', 'summary_en', 'summary_zh', 'image_path', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function localized(string $field, string $locale): ?string
    {
        return $this->{$field.'_'.$locale} ?: $this->{$field.'_id'};
    }
}
