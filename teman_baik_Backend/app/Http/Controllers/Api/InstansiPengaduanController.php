<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Models\Status;
use App\Services\NotifikasiAdmin;
use App\Notifications\LampiranDitambahkan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstansiPengaduanController extends Controller
{
    private const STATUS_TERLIHAT = ['dikirim', 'dijawab'];
    private const MAKS_LAMPIRAN = 10;

    public function ringkasan(Request $request)
    {
        $idInstansi = $request->user()->id_instansi;
        $idStatus = Status::whereIn('status', self::STATUS_TERLIHAT)->pluck('id', 'status');

        return response()->json([
            'menunggu_balasan' => Pengaduan::where('id_instansi', $idInstansi)
                ->where('id_status', $idStatus['dikirim'] ?? 0)->count(),
            'sudah_dijawab' => Pengaduan::where('id_instansi', $idInstansi)
                ->where('id_status', $idStatus['dijawab'] ?? 0)->count(),
        ]);
    }

    public function index(Request $request)
    {
        $idStatus = Status::whereIn('status', self::STATUS_TERLIHAT)->pluck('id', 'status');

        $query = Pengaduan::with('Status')
            ->withCount('lampirans')
            ->where('id_instansi', $request->user()->id_instansi)
            ->whereIn('id_status', $idStatus->values())
            ->latest('id');

        if ($request->filled('status') && isset($idStatus[$request->status])) {
            $query->where('id_status', $idStatus[$request->status]);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($w) => $w->where('no_tiket', 'like', "%{$q}%")
                ->orWhere('judul', 'like', "%{$q}%"));
        }

        return response()->json(
            $query->paginate(15)->through(fn ($p) => $this->ringkas($p))
        );
    }

    public function show(Request $request, Pengaduan $pengaduan)
    {
        $this->milikInstansi($request, $pengaduan);

        $pengaduan->load(['Status', 'statusHistories.Status', 'lampirans'])->loadCount('lampirans');
        $tiket = $pengaduan->no_tiket;

        return response()->json($this->ringkas($pengaduan) + [
            'alamat' => $pengaduan->alamat,
            'isi_aduan' => $pengaduan->aduan,
            'riwayat' => $pengaduan->statusHistories->map(fn ($h) => [
                'status' => $h->Status?->status,
                'catatan' => $h->catatan,
                'waktu' => $h->created_at,
            ]),
            'bukti_pengadu' => $pengaduan->nama_file_eviden ? [
                'nama_file' => $pengaduan->nama_file_eviden,
                'url' => url("/api/instansi/pengaduan/{$tiket}/bukti"),
            ] : null,
            'balasan_instansi' => $pengaduan->balasan_instansi ? [
                'isi' => $pengaduan->balasan_instansi,
                'waktu' => $pengaduan->balasan_instansi_at,
            ] : null,
            'tanggapan' => $pengaduan->jawaban ? ['isi' => $pengaduan->jawaban] : null,
            'lampiran' => $pengaduan->lampirans->map(fn ($l) => [
                'id' => $l->id,
                'jenis' => $l->jenis,
                'label' => $l->label,
                'tanggal' => $l->tanggal,
                'nama_file' => $l->nama_file,
                'ukuran' => $l->ukuran,
                'url' => url("/api/instansi/pengaduan/{$tiket}/lampiran/{$l->id}"),
            ]),
        ]);
    }

    // Balasan dari instansi disimpan sebagai draf. Admin Teman Baik yang menyaring dan memublikasikannya.
    public function balasan(Request $request, Pengaduan $pengaduan)
    {
        $this->milikInstansi($request, $pengaduan);

        $data = $request->validate(['isi' => 'required|string|max:5000']);

        if ($pengaduan->Status?->status !== 'dikirim') {
            return response()->json([
                'message' => 'Balasan sudah dipublikasikan oleh Admin Teman Baik dan tidak bisa diubah lagi.',
            ], 422);
        }

        $pengaduan->update([
            'balasan_instansi' => strip_tags($data['isi']),
            'balasan_instansi_at' => now(),
        ]);

        app(NotifikasiAdmin::class)->kirim(
            "[Teman Baik] Balasan instansi untuk {$pengaduan->no_tiket}",
            "Instansi telah mengirim balasan untuk laporan {$pengaduan->no_tiket}. "
                . "Silakan tinjau, edit bila perlu, lalu publikasikan di dashboard."
        );

        return response()->json(['message' => 'Balasan terkirim ke Admin Teman Baik.']);
    }

    public function unggahLampiran(Request $request, Pengaduan $pengaduan)
    {
        $this->milikInstansi($request, $pengaduan);

        $data = $request->validate([
            'jenis' => 'required|in:dokumentasi,berita_acara',
            'label' => 'nullable|string|max:100',
            'tanggal' => 'nullable|date',
            'file' => [
                'required', 'file', 'max:5120',
                $request->input('jenis') === 'berita_acara' ? 'mimes:pdf' : 'mimes:jpg,jpeg,png',
            ],
        ]);

        if ($pengaduan->lampirans()->count() >= self::MAKS_LAMPIRAN) {
            return response()->json([
                'message' => 'Jumlah lampiran sudah mencapai batas (' . self::MAKS_LAMPIRAN . ').',
            ], 422);
        }

        $file = $request->file('file');
        $ukuran = $file->getSize();
        $namaFile = 'lamp_' . $pengaduan->id . '_' . now()->format('YmdHis') . '_' . Str::random(6)
            . '.' . ($file->guessExtension() ?: 'bin');

        Storage::disk('local')->putFileAs('lampiran', $file, $namaFile);

        $lampiran = $pengaduan->lampirans()->create([
            'jenis' => $data['jenis'],
            'label' => $data['label'] ?? Str::limit(strip_tags(basename($file->getClientOriginalName())), 120, ''),
            'tanggal' => $data['tanggal'] ?? now('Asia/Makassar')->toDateString(),
            'nama_file' => $namaFile,
            'ukuran' => $ukuran,
        ]);

        $this->beritahuPengadu($pengaduan);

        return response()->json([
            'id' => $lampiran->id,
            'jenis' => $lampiran->jenis,
            'label' => $lampiran->label,
            'tanggal' => $lampiran->tanggal,
            'ukuran' => $lampiran->ukuran,
        ], 201);
    }

    public function hapusLampiran(Request $request, Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        $this->milikInstansi($request, $pengaduan);
        abort_if((int) $lampiran->id_pengaduan !== (int) $pengaduan->id, 404);

        Storage::disk('local')->delete('lampiran/' . $lampiran->nama_file);
        $lampiran->delete();

        return response()->json(['message' => 'Lampiran dihapus.']);
    }

    public function bukti(Request $request, Pengaduan $pengaduan)
    {
        $this->milikInstansi($request, $pengaduan);
        abort_if(empty($pengaduan->nama_file_eviden), 404);

        return $this->kirimFile($request, 'eviden/' . $pengaduan->nama_file_eviden, $pengaduan->nama_file_eviden);
    }

    public function lampiran(Request $request, Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        $this->milikInstansi($request, $pengaduan);
        abort_if((int) $lampiran->id_pengaduan !== (int) $pengaduan->id, 404);

        return $this->kirimFile($request, 'lampiran/' . $lampiran->nama_file, $lampiran->nama_file);
    }

    private function beritahuPengadu(Pengaduan $pengaduan): void
    {
        $pengadu = $pengaduan->pengadu;

        if (! $pengadu) {
            return;
        }

        $sudahDiberitahu = $pengadu->notifications()
            ->where('type', LampiranDitambahkan::class)
            ->where('data', 'like', '%"no_tiket":"' . $pengaduan->no_tiket . '"%')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if (! $sudahDiberitahu) {
            $pengadu->notify(new LampiranDitambahkan($pengaduan));
        }
    }

    private function milikInstansi(Request $request, Pengaduan $pengaduan): void
    {
        abort_unless(
            (int) $pengaduan->id_instansi === (int) $request->user()->id_instansi
                && in_array($pengaduan->Status?->status, self::STATUS_TERLIHAT, true),
            404
        );
    }

    private function ringkas(Pengaduan $p): array
    {
        return [
            'no_tiket' => $p->no_tiket,
            'judul' => $p->judul,
            'jenis' => $p->is_aduan ? 'pengaduan' : 'informasi',
            'status' => $p->Status?->status,
            'samarkan' => (bool) $p->samarkan,
            'nama_pelapor' => $p->samarkan ? null : $p->nama,
            'sudah_ada_balasan' => $p->balasan_instansi !== null,
            'jumlah_lampiran' => $p->lampirans_count ?? $p->lampirans()->count(),
            'dibuat_pada' => $p->created_at,
        ];
    }

    private function kirimFile(Request $request, string $path, string $namaUnduh)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($path, $namaUnduh);
        }

        return Storage::disk('local')->response($path);
    }
}