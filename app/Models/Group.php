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

    // // Define the inverse of the relationship: the parent group
    // public function parent()
    // {
    //     return $this->belongsTo(Group::class, 'parent_group_id');
    // }

    // Define the one-to-many relationship: the child groups
    public function childrenOnly()
    {
        return $this->hasMany(Group::class, 'parent_group_id');
    }

    public function children()
    {
        return $this->hasMany(Group::class, 'parent_group_id')->with('children', 'classification', 'level');
    }

    public function parent()
    {
        return $this->belongsTo(Group::class, 'parent_group_id');
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }
    
}
