<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminInstansiController extends Controller
{
    public function index()
    {
        return response()->json(
            Instansi::with('adminLogin')
                ->withCount('Pengaduan')
                ->orderBy('nama_instansi')
                ->get()
                ->map(fn ($i) => $this->tampil($i))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_instansi' => 'required|string|max:255|unique:instansis,nama_instansi',
            'email1' => 'required|email:rfc,dns',
            'email2' => 'nullable|email:rfc,dns',
            'email3' => 'nullable|email:rfc,dns',
            'no_wa1' => ['nullable', 'regex:/^[08][0-9]*$/', 'max:13'],
        ]);

        $instansi = Instansi::create($data);

        return response()->json(
            $this->tampil($instansi->load('adminLogin')->loadCount('Pengaduan')),
            201
        );
    }

    public function update(Request $request, Instansi $instansi)
    {
        $data = $request->validate([
            'nama_instansi' => [
                'required', 'string', 'max:255',
                Rule::unique('instansis', 'nama_instansi')->ignore($instansi->id),
            ],
            'email1' => 'required|email:rfc,dns',
            'email2' => 'nullable|email:rfc,dns',
            'email3' => 'nullable|email:rfc,dns',
            'no_wa1' => ['nullable', 'regex:/^[08][0-9]*$/', 'max:13'],
        ]);

        $instansi->update($data);

        return response()->json(
            $this->tampil($instansi->load('adminLogin')->loadCount('Pengaduan'))
        );
    }

    public function destroy(Instansi $instansi)
    {
        if ($instansi->Pengaduan()->exists()) {
            return response()->json([
                'message' => 'Instansi tidak bisa dihapus karena memiliki pengaduan.',
            ], 422);
        }

        if ($instansi->users()->exists()) {
            return response()->json([
                'message' => 'Instansi masih memiliki akun admin. Hapus akunnya terlebih dahulu.',
            ], 422);
        }

        $instansi->delete();

        return response()->json(['message' => 'Instansi berhasil dihapus.']);
    }

    private function tampil(Instansi $i): array
    {
        return [
            'id' => $i->id,
            'nama_instansi' => $i->nama_instansi,
            'email1' => $i->email1,
            'email2' => $i->email2,
            'email3' => $i->email3,
            'no_wa1' => $i->no_wa1,
            'pengaduan_count' => $i->pengaduan_count ?? 0,
            'admin_login' => $i->adminLogin ? [
                'id' => $i->adminLogin->id,
                'nama' => $i->adminLogin->nama,
                'username' => $i->adminLogin->username,
            ] : null,
        ];
    }
}