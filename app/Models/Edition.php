<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Edition extends Model
{
    use SoftDeletes;

    const DELETED_AT = 'removed_at';

    protected $fillable = [
        'year',
        'options',
        'active',
        'removed_at',
    ];

    protected $casts = [
        'options'    => 'array',
        'active'     => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function talks()
    {
        return $this->hasMany(Talk::class, 'edition_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'edition_id');
    }

    public function collaborators()
    {
        return $this->hasMany(Collaborator::class, 'edition_id');
    }

    public function organizers()
    {
        return $this->hasMany(Organizer::class, 'edition_id');
    }
}
