<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PengaduanController extends Controller
{
    public function index(Request $request)
    {
        $pengaduans = Pengaduan::with(['Instansi', 'Status'])
            ->where('id_pengadu', $request->user()->id)
            ->latest('tgl_aduan')
            ->get();

        return response()->json($pengaduans);
    }

    public function show(Request $request, Pengaduan $pengaduan)
    {
        abort_if((int) $pengaduan->id_pengadu !== (int) $request->user()->id, 404);

        $pengaduan->load(['Instansi', 'Status', 'statusHistories.Status', 'lampirans']);

        $waktuTanggapan = $pengaduan->statusHistories
            ->first(fn ($h) => $h->Status?->status === 'dijawab')?->created_at;

        return response()->json([
            'id' => $pengaduan->id,
            'no_tiket' => $pengaduan->no_tiket,
            'judul' => $pengaduan->judul,
            'jenis' => $pengaduan->is_aduan ? 'pengaduan' : 'informasi',
            'dibuat_pada' => $pengaduan->created_at,
            'alamat' => $pengaduan->alamat,
            'isi_aduan' => $pengaduan->aduan,
            'samarkan' => (bool) $pengaduan->samarkan,
            'instansi' => $pengaduan->Instansi?->nama_instansi,
            'status_saat_ini' => $pengaduan->Status?->status,
            'tanggapan' => $pengaduan->jawaban ? [
                'isi' => $pengaduan->jawaban,
                'waktu' => $waktuTanggapan,
            ] : null,
            'riwayat' => $pengaduan->statusHistories->map(fn ($h) => [
                'status' => $h->Status?->status,
                'catatan' => $h->catatan,
                'waktu' => $h->created_at,
            ]),
            
            'bukti_pengadu' => $pengaduan->nama_file_eviden ? [
                'nama_file' => $pengaduan->nama_file_eviden,
                'waktu' => $pengaduan->created_at,
                'url' => url("/api/pengaduan/{$pengaduan->no_tiket}/bukti"),
            ] : null,
            
            'lampiran' => $pengaduan->lampirans->map(fn ($l) => [
                'id' => $l->id,
                'jenis' => $l->jenis,
                'label' => $l->label,
                'tanggal' => $l->tanggal,
                'nama_file' => $l->nama_file,
                'ukuran' => $l->ukuran,
                'url' => url("/api/pengaduan/{$pengaduan->no_tiket}/lampiran/{$l->id}"),
            ]),
            'evaluasi' => [
                'rating' => $pengaduan->rating,
                'waktu' => $pengaduan->rated_at,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $cred = $request->validate([
            'instansi' => 'required|exists:instansis,id',
            'jenisAduan' => 'required|boolean', 
            'judul' => 'required|string|max:255',
            'alamat' => 'required|string',
            'isi_aduan' => 'required|string',
            'samarkan' => 'nullable|boolean',
            'bukti' => 'nullable|mimes:jpeg,png,pdf|file|max:5120',
        ]);

        $pengadu = $request->user();

        $data = [
            'id_pengadu' => $pengadu->id,
            'judul' => $cred['judul'],
            'tgl_aduan' => now('Asia/Makassar')->toDateString(),
            'nama' => $pengadu->nama,
            'id_instansi' => $cred['instansi'],
            'id_status' => 1,
            'is_aduan' => $cred['jenisAduan'],
            'alamat' => strip_tags($cred['alamat']),
            'aduan' => strip_tags($cred['isi_aduan']),
            'telepon' => '', 
            'nama_file' => '', 
            'penginput' => 'mandiri',
            'samarkan' => $cred['samarkan'] ?? 0,
            'nama_file_eviden' => '',
        ];

        if ($request->file('bukti')) {
            $eviden = 'evid_' . now()->format('YmdHis') . '_' . Str::random(6)
                . '.' . $request->file('bukti')->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('eviden', $request->file('bukti'), $eviden);
            $data['nama_file_eviden'] = $eviden;
        }

        $pengaduan = Pengaduan::create($data);

        $pengaduan->load('Instansi');
        app(\App\Services\NotifikasiAdmin::class)->kirim(
            "[Teman Baik] Laporan baru {$pengaduan->no_tiket}",
            "Ada " . ($pengaduan->is_aduan ? 'pengaduan' : 'permohonan informasi')
                . " baru untuk {$pengaduan->Instansi?->nama_instansi} dengan nomor {$pengaduan->no_tiket}. "
                . "Silakan buka dashboard untuk memverifikasi."
        );

        return response()->json($pengaduan, 201);
    }

    public function evaluasi(Request $request, Pengaduan $pengaduan)
    {
        abort_if((int) $pengaduan->id_pengadu !== (int) $request->user()->id, 404);

        $data = $request->validate([
            'rating' => 'required|integer|between:1,4',
        ]);

        if ($pengaduan->Status?->status !== 'dijawab') {
            return response()->json([
                'message' => 'Evaluasi baru bisa diberikan setelah laporan selesai ditanggapi.',
            ], 422);
        }

        if ($pengaduan->rating !== null) {
            return response()->json([
                'message' => 'Anda sudah memberikan evaluasi untuk laporan ini.',
            ], 422);
        }

        $pengaduan->update(['rating' => $data['rating'], 'rated_at' => now()]);

        return response()->json([
            'rating' => $pengaduan->rating,
            'waktu' => $pengaduan->rated_at,
        ]);
    }
}