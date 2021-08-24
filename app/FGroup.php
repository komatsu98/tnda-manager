<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FGroup extends Model
{
    //
    protected $table = 'fgroups';
    protected $fillable = ['name'];

    /**
     * The users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany('App\FUser', 'user_groups', 'group_id', 'user_id')
                        ->withPivot([
                            'is_master'
                        ])
                        ->withTimestamps();
    }
}
