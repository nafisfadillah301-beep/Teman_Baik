<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LampiranController extends Controller
{
    public function show(Request $request, Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        abort_if((int) $pengaduan->id_pengadu !== (int) $request->user()->id, 403);
        abort_if((int) $lampiran->id_pengaduan !== (int) $pengaduan->id, 404);

        return $this->kirimFile($request, 'lampiran/' . $lampiran->nama_file, $lampiran->nama_file);
    }

    public function bukti(Request $request, Pengaduan $pengaduan)
    {
        abort_if((int) $pengaduan->id_pengadu !== (int) $request->user()->id, 403);
        abort_if(empty($pengaduan->nama_file_eviden), 404);

        return $this->kirimFile($request, 'eviden/' . $pengaduan->nama_file_eviden, $pengaduan->nama_file_eviden);
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