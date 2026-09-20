<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentField extends Model
{
    protected $fillable = ['key', 'section', 'type', 'label', 'values', 'alt_values', 'mobile_value', 'image_position'];

    protected function casts(): array
    {
        return ['values' => 'array', 'alt_values' => 'array'];
    }
}
