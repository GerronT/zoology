<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    /** @use HasFactory<\Database\Factories\AnimalFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'alt_name',
        'description',
        'group_id'
    ];

    protected $hidden = ['deleted_at'];
}
