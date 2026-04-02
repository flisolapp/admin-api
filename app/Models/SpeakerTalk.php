<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeakerTalk extends Model
{

    protected $fillable = [
        'speaker_id', 'talk_id', 'created_at', 'updated_at', 'removed_at'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'speaker_id');
    }

    public function talk()
    {
        return $this->belongsTo(Talk::class, 'talk_id');
    }

}
