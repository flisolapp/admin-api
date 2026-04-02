<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{

    protected $fillable = [
        'edition_id', 'people_id', 'created_at', 'updated_at', 'removed_at'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

}
