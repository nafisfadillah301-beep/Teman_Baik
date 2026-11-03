<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotifikasiAdmin
{
    public function kirim(string $subjek, string $isi): void
    {
        $penerima = User::where('role', 'super_admin')->whereNotNull('email')->pluck('email')->all();

        if (! $penerima) {
            return;
        }

        try {
            Mail::raw($isi, fn ($pesan) => $pesan->to($penerima)->subject($subjek));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}