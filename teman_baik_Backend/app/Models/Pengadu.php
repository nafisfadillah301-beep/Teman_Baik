<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Pengadu extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'nik',
        'nama',
        'verified_at',
    ];

    protected $hidden = [
        'nik', 
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function pengaduans()
    {
        return $this->hasMany(Pengaduan::class, 'id_pengadu');
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'id_pengadu');
    }
}