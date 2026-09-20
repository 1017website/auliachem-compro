<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'category', 'description', 'file_path', 'original_name', 'file_size', 'is_published', 'downloads'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
