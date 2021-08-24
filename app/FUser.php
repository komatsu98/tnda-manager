<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FUser extends Model
{
    //
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'name', 'mail', 'role', 'status'];

    /**
     * The groups that user belonged to.
     */
    public function groups()
    {
        return $this->belongsToMany('App\FGroup', 'user_groups', 'user_id', 'group_id')
            ->withPivot([
                'is_master'
            ])
            ->withTimestamps();
    }

    /**
     * Get the histories for the user.
     */
    public function histories()
    {
        return $this->hasMany('App\History', 'user_id');
    }
}
