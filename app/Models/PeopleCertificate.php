<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeopleCertificate extends Model
{

    protected $fillable = [
        'people_id', 'edition_id', 'organizer_id', 'collaborator_id', 'talk_id', 'participant_id', 'name', 'federal_code', 'code', 'sent_at', 'last_view_at', 'created_at', 'updated_at', 'removed_at'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    public function organizer()
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class, 'collaborator_id');
    }

    public function talk()
    {
        return $this->belongsTo(Talk::class, 'talk_id');
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

}
