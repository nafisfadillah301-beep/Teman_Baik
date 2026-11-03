<?php

namespace App\Notifications;

use App\Channels\WebPushChannel;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class StatusPengaduanBerubah extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Pengaduan $pengaduan,
        protected string $status,
        protected ?string $catatan = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    // lonceng
    public function toArray(object $notifiable): array
    {
        return [
            'no_tiket' => $this->pengaduan->no_tiket,
            'judul' => $this->pengaduan->judul,
            'status' => $this->status,
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
            'status' => $this->status,
        ];
    }

    private function pesan(): string
    {
        $tiket = $this->pengaduan->no_tiket;

        return match ($this->status) {
            'diverifikasi' => "Laporan {$tiket} telah diverifikasi oleh admin.",
            'dikirim' => "Laporan {$tiket} sedang diproses" . ($this->catatan ? ": {$this->catatan}" : '.'),
            'dijawab' => "Laporan {$tiket} telah ditanggapi oleh instansi.",
            'ditolak' => "Laporan {$tiket} tidak dapat diproses" . ($this->catatan ? ": {$this->catatan}" : '.'),
            default => "Status laporan {$tiket} diperbarui.",
        };
    }
}