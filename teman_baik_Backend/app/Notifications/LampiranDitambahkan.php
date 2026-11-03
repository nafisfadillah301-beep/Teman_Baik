<?php

namespace App\Notifications;

use App\Channels\WebPushChannel;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class LampiranDitambahkan extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(protected Pengaduan $pengaduan)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    // lonceng di dalam aplikasi
    public function toArray(object $notifiable): array
    {
        return [
            'no_tiket' => $this->pengaduan->no_tiket,
            'judul' => $this->pengaduan->judul,
            'status' => 'lampiran',
            'pesan' => $this->pesan(),
        ];
    }

    // pop-up Web Push
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Teman Baik',
            'body' => $this->pesan(),
            'no_tiket' => $this->pengaduan->no_tiket,
            'status' => 'lampiran',
        ];
    }

    private function pesan(): string
    {
        return "Lampiran tindak lanjut untuk laporan {$this->pengaduan->no_tiket} telah ditambahkan.";
    }
}