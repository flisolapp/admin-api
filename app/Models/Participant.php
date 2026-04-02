<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{

    protected $fillable = [
        'edition_id', 'people_id', 'presented_at', 'prizedraw_confirmation_at', 'prizedraw_winner_at', 'prizedraw_order', 'prizedraw_description', 'created_at', 'updated_at', 'removed_at'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

}
