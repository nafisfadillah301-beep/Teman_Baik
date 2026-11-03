<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'rated_at' => 'datetime',
        'balasan_instansi_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Pengaduan $pengaduan) {
            if (empty($pengaduan->no_tiket)) {
                $pengaduan->no_tiket = static::generateNoTiket($pengaduan->is_aduan);
            }
        });

        static::created(function (Pengaduan $pengaduan) {
            $pengaduan->statusHistories()->create([
                'id_status' => $pengaduan->id_status,
            ]);
        });
    }

    public static function generateNoTiket($isAduan): string
    {
        $prefix = filter_var($isAduan, FILTER_VALIDATE_BOOLEAN) ? 'PGD' : 'INF';

        do {
            $kode = $prefix . '-' . now()->year . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)
                . chr(65 + random_int(0, 25));
        } while (static::where('no_tiket', $kode)->exists());

        return $kode;
    }

    public function Instansi()
    {
        return $this->belongsTo(Instansi::class, 'id_instansi');
    }

    public function Status()
    {
        return $this->belongsTo(Status::class, 'id_status');
    }

    public function statusHistories()
    {
        return $this->hasMany(PengaduanStatusHistory::class, 'id_pengaduan')->orderBy('created_at');
    }

    public function lampirans()
    {
        return $this->hasMany(PengaduanLampiran::class, 'id_pengaduan');
    }

    public function pengadu()
    {
        return $this->belongsTo(Pengadu::class, 'id_pengadu');
    }
}