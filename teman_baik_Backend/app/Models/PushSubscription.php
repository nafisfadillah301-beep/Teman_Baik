<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'id_pengadu',
        'endpoint',
        'p256dh_key',
        'auth_key',
    ];

    public function pengadu()
    {
        return $this->belongsTo(Pengadu::class, 'id_pengadu');
    }
}