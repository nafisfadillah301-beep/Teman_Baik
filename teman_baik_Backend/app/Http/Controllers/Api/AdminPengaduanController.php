<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendMail;
use App\Models\Instansi;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminPengaduanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengaduan::with(['Instansi', 'Status'])->latest('id');

        if ($request->filled('status')) {
            $query->where('id_status', Status::where('status', $request->status)->value('id'));
        }
        if ($request->filled('instansi')) {
            $query->where('id_instansi', $request->instansi);
        }
        if ($request->filled('jenis')) {
            $query->where('is_aduan', $request->jenis === 'pengaduan' ? 1 : 0);
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

    public function show(Pengaduan $pengaduan)
    {
        $pengaduan->load(['Instansi', 'Status', 'statusHistories.Status', 'lampirans']);
        $tiket = $pengaduan->no_tiket;

        return response()->json($this->ringkas($pengaduan) + [
            'id_instansi' => $pengaduan->id_instansi,
            'alamat' => $pengaduan->alamat,
            'isi_aduan' => $pengaduan->aduan,
            'riwayat' => $pengaduan->statusHistories->map(fn ($h) => [
                'status' => $h->Status?->status,
                'catatan' => $h->catatan,
                'waktu' => $h->created_at,
            ]),
            'bukti_pengadu' => $pengaduan->nama_file_eviden ? [
                'nama_file' => $pengaduan->nama_file_eviden,
                'url' => url("/api/admin/pengaduan/{$tiket}/bukti"),
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
                'url' => url("/api/admin/pengaduan/{$tiket}/lampiran/{$l->id}"),
            ]),
            'evaluasi' => ['rating' => $pengaduan->rating, 'waktu' => $pengaduan->rated_at],
        ]);
    }

    public function verifikasi(Request $request, Pengaduan $pengaduan)
    {
        $data = $request->validate(['catatan' => 'nullable|string|max:500']);

        if ($pengaduan->Status?->status !== 'diterima') {
            return response()->json([
                'message' => 'Hanya laporan berstatus "diterima" yang bisa diverifikasi.',
            ], 422);
        }

        $this->ubahStatus($pengaduan, 'diverifikasi', $data['catatan'] ?? null);

        return response()->json(['status' => 'diverifikasi']);
    }

    public function tolak(Request $request, Pengaduan $pengaduan)
    {
        $data = $request->validate(['alasan' => 'required|string|max:500']);

        if (! in_array($pengaduan->Status?->status, ['diterima', 'diverifikasi'], true)) {
            return response()->json([
                'message' => 'Laporan hanya bisa ditolak sebelum diteruskan ke instansi.',
            ], 422);
        }

        $this->ubahStatus($pengaduan, 'ditolak', strip_tags($data['alasan']));

        return response()->json(['status' => 'ditolak']);
    }

    public function teruskan(Request $request, Pengaduan $pengaduan)
    {
        $data = $request->validate(['instansi' => 'nullable|exists:instansis,id']);

        $statusSekarang = $pengaduan->Status?->status;

        if (! in_array($statusSekarang, ['diverifikasi', 'dikirim'], true)) {
            return response()->json([
                'message' => 'Laporan harus diverifikasi dulu sebelum diteruskan ke instansi.',
            ], 422);
        }

        // Instansi tujuan hanya boleh dialihkan selama belum pernah dikirim
        $idInstansi = $statusSekarang === 'diverifikasi'
            ? ($data['instansi'] ?? $pengaduan->id_instansi)
            : $pengaduan->id_instansi;

        $instansi = Instansi::findOrFail($idInstansi);
        $emails = array_values(array_filter([$instansi->email1, $instansi->email2, $instansi->email3]));

        if (! $emails) {
            return response()->json(['message' => 'Instansi ini belum punya alamat email.'], 422);
        }

        $adaBukti = $pengaduan->nama_file_eviden
            && Storage::disk('local')->exists('eviden/' . $pengaduan->nama_file_eviden);

        $jenis = $pengaduan->is_aduan ? 'Pengaduan Layanan' : 'Permohonan Informasi Layanan';

        $dataEmail = [
            'subject' => "{$jenis} [{$pengaduan->no_tiket}]",
            'instansi' => $instansi,
            'aduan' => "{$pengaduan->judul}. {$pengaduan->aduan}",
            'tgl_aduan' => $pengaduan->tgl_aduan,
            'ttd' => $pengaduan->samarkan ? 'Pemohon' : '',
            'nama' => $pengaduan->samarkan ? '' : e($pengaduan->nama),
            'bukti_dukung' => $adaBukti ? 'Bukti pendukung terlampir pada email ini.' : '',
            'bukti_path' => $adaBukti ? 'eviden/' . $pengaduan->nama_file_eviden : null,
            'penginput' => $pengaduan->penginput === 'admin' ? 'Admin' : 'Form Pengaduan Mandiri',
        ];

        try {
            Mail::to($emails[0])->cc(array_slice($emails, 1))->send(new SendMail($dataEmail));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Gagal mengirim email ke instansi. Coba lagi.'], 502);
        }

        // Status berubah hanya pada pengiriman pertama; kirim ulang tidak menambah riwayat
        if ($statusSekarang === 'diverifikasi') {
            $this->ubahStatus(
                $pengaduan,
                'dikirim',
                "Diteruskan ke {$instansi->nama_instansi}",
                ['id_instansi' => $instansi->id]
            );
        }

        return response()->json([
            'status' => 'dikirim',
            'instansi' => $instansi->nama_instansi,
            'jumlah_email' => count($emails),
        ]);
    }

    public function balasan(Request $request, Pengaduan $pengaduan)
    {
        $data = $request->validate(['isi' => 'required|string|max:5000']);

        if ($pengaduan->Status?->status !== 'dikirim') {
            return response()->json([
                'message' => 'Balasan hanya bisa diberikan untuk laporan yang sudah diteruskan ke instansi.',
            ], 422);
        }

        $this->ubahStatus($pengaduan, 'dijawab', null, ['jawaban' => strip_tags($data['isi'])]);

        return response()->json(['status' => 'dijawab']);
    }

    public function bukti(Request $request, Pengaduan $pengaduan)
    {
        abort_if(empty($pengaduan->nama_file_eviden), 404);

        return $this->kirimFile($request, 'eviden/' . $pengaduan->nama_file_eviden, $pengaduan->nama_file_eviden);
    }

    public function lampiran(Request $request, Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        abort_if((int) $lampiran->id_pengaduan !== (int) $pengaduan->id, 404);

        return $this->kirimFile($request, 'lampiran/' . $lampiran->nama_file, $lampiran->nama_file);
    }

    private function ringkas(Pengaduan $p): array
    {
        return [
            'no_tiket' => $p->no_tiket,
            'judul' => $p->judul,
            'jenis' => $p->is_aduan ? 'pengaduan' : 'informasi',
            'instansi' => $p->Instansi?->nama_instansi,
            'status' => $p->Status?->status,
            'samarkan' => (bool) $p->samarkan,
            'nama_pelapor' => $p->samarkan ? null : $p->nama,
            'dibuat_pada' => $p->created_at,
        ];
    }

    // Ubah status dan catat riwayat. Riwayat baru otomatis memicu notifikasi lonceng ke pengadu
    private function ubahStatus(Pengaduan $pengaduan, string $status, ?string $catatan = null, array $tambahan = []): void
    {
        $idStatus = Status::where('status', $status)->value('id');

        DB::transaction(function () use ($pengaduan, $idStatus, $catatan, $tambahan) {
            $pengaduan->update(['id_status' => $idStatus] + $tambahan);
            $pengaduan->statusHistories()->create(['id_status' => $idStatus, 'catatan' => $catatan]);
        });
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