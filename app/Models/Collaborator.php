<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{

    protected $fillable = [
        'edition_id', 'people_id', 'audited_at', 'audit_note', 'approved_at', 'confirmed_at', 'created_at', 'updated_at', 'removed_at'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    public function areas()
    {
        return $this->hasMany(CollaboratorArea::class, 'collaborator_id');
    }

    public function availabilities()
    {
        return $this->hasMany(CollaboratorAvailability::class, 'collaborator_id');
    }

}
