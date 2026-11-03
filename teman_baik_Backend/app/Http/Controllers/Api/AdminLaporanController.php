<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use App\Models\Pengaduan;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLaporanController extends Controller
{
    public function dashboard()
    {
        $jumlahPerStatus = Pengaduan::select('id_status', DB::raw('count(*) as jumlah'))
            ->groupBy('id_status')
            ->pluck('jumlah', 'id_status');

        $evaluasi = Pengaduan::whereNotNull('rating')
            ->selectRaw('count(*) as responden, avg(rating) as rata_rata')
            ->first();

        return response()->json([
            'total' => Pengaduan::count(),
            'per_status' => Status::orderBy('id')->get()->map(fn ($s) => [
                'status' => $s->status,
                'jumlah' => (int) ($jumlahPerStatus[$s->id] ?? 0),
            ]),
            'per_jenis' => [
                'pengaduan' => Pengaduan::where('is_aduan', 1)->count(),
                'informasi' => Pengaduan::where('is_aduan', 0)->count(),
            ],
            'per_instansi' => Instansi::withCount('Pengaduan')
                ->orderByDesc('pengaduan_count')
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'nama_instansi' => $i->nama_instansi,
                    'jumlah' => $i->pengaduan_count,
                ]),
            'evaluasi' => [
                'responden' => (int) $evaluasi->responden,
                'rata_rata' => $evaluasi->rata_rata !== null ? round((float) $evaluasi->rata_rata, 2) : null,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string',
            'instansi' => 'nullable|integer',
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date',
        ]);

        $query = Pengaduan::with(['Instansi', 'Status']);

        if ($request->filled('status')) {
            $query->where('id_status', Status::where('status', $request->status)->value('id'));
        }
        if ($request->filled('instansi')) {
            $query->where('id_instansi', $request->instansi);
        }
        if ($request->filled('dari')) {
            $query->whereDate('tgl_aduan', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tgl_aduan', '<=', $request->sampai);
        }

        $namaFile = 'data_pengaduan_' . now('Asia/Makassar')->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); 

            fputcsv($out, [
                'no_tiket', 'tgl_aduan', 'jenis', 'penginput', 'instansi_tujuan', 'status',
                'samarkan_identitas', 'nama', 'telepon', 'judul', 'alamat', 'isi', 'jawaban', 'rating',
            ], ';');

            $query->chunkById(1000, function ($baris) use ($out) {
                foreach ($baris as $p) {
                    $anonim = (bool) $p->samarkan;

                    fputcsv($out, array_map([$this, 'aman'], [
                        $p->no_tiket,
                        $p->tgl_aduan,
                        $p->is_aduan ? 'pengaduan' : 'informasi',
                        $p->penginput,
                        $p->Instansi?->nama_instansi,
                        $p->Status?->status,
                        $anonim ? 'ya' : 'tidak',
                        $anonim ? '' : $p->nama,
                        $anonim ? '' : $p->telepon,
                        $p->judul,
                        $p->alamat,
                        $p->aduan,
                        $p->jawaban,
                        $p->rating,
                    ]), ';');
                }
            });

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function aman($nilai)
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $nilai;
        }

        return $nilai;
    }
}