<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talk extends Model
{

    protected $fillable = [
        'edition_id', 'title', 'description', 'shift', 'kind', 'talk_subject_id', 'slide_file', 'slide_url', 'internal_note', 'audited_at', 'audit_note', 'approved_at', 'confirmed_at', 'created_at', 'updated_at', 'removed_at'
    ];

    public function speakerTalks()
    {
        return $this->hasMany(SpeakerTalk::class, 'talk_id');
    }

}
