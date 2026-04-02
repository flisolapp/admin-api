<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollaboratorAvailability extends Model
{

    protected $fillable = [
        'collaborator_id', 'collaborator_shift_id', 'created_at', 'updated_at', 'removed_at'
    ];

    public function shift()
    {
        return $this->belongsTo(CollaborationShift::class, 'collaborator_shift_id');
    }

}
