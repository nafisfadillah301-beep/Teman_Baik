<?php

namespace App\Models;

use App\Notifications\StatusPengaduanBerubah;
use Illuminate\Database\Eloquent\Model;

class PengaduanStatusHistory extends Model
{
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::created(function (PengaduanStatusHistory $riwayat) {
            $pengaduan = $riwayat->pengaduan;
            $pengadu = $pengaduan?->pengadu;
            $status = $riwayat->Status?->status;

            if (! $pengadu || $status === 'diterima') {
                return;
            }

            $pengadu->notify(new StatusPengaduanBerubah($pengaduan, $status, $riwayat->catatan));
        });
    }

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'id_pengaduan');
    }

    public function Status()
    {
        return $this->belongsTo(Status::class, 'id_status');
    }
}