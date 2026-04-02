<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class Edition extends Model
{

    protected $fillable = [
        'year', 'options', 'active', 'created_at', 'updated_at', 'removed_at'
    ];

    protected $casts = [
        'options' => 'array',
        'active' => 'boolean',
    ];

    public function getOptionsAttribute($value)
    {
        try {
            $decoded = json_decode($value);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        } catch (Throwable $e) {
            return null;
        }
    }

}
