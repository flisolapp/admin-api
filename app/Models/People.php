<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class People extends Model
{

    protected $fillable = [
        'name', 'federal_code', 'email', 'phone', 'photo', 'bio', 'site', 'use_free', 'distro_id', 'student_info_id', 'student_place', 'student_course', 'address_state', 'created_at', 'updated_at', 'removed_at'
    ];

    protected $casts = [
        'use_free' => 'boolean',
    ];

}
