<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $table = 'sessions';
    protected $fillable = ['user_id', 'ip_addr', 'mac_addr', 'device', 'location', 'access_token', 'expired_at'];
    protected $hidden = [
        'access_token'
    ];
    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * The user that history belonged to.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
