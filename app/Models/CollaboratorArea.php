<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollaboratorArea extends Model
{

    protected $fillable = [
        'collaborator_id', 'collaboration_area_id', 'created_at', 'updated_at', 'removed_at'
    ];

    public function area()
    {
        return $this->belongsTo(CollaborationArea::class, 'collaboration_area_id');
    }

}
