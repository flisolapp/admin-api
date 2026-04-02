<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{

    protected $fillable = [
        'name', 'acronym', 'created_at', 'updated_at', 'removed_at'
    ];

}
