<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'classification_id',
        'level_id',
        'parent_group_id',
    ];

    protected $hidden = ['deleted_at'];
    
}
