<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $pengadu = $request->user();

        $items = $pengadu->notifications()->latest()->limit(20)->get()->map(fn ($n) => [
            'id' => $n->id,
            'no_tiket' => $n->data['no_tiket'] ?? null,
            'judul' => $n->data['judul'] ?? null,
            'status' => $n->data['status'] ?? null,
            'pesan' => $n->data['pesan'] ?? null,
            'dibaca' => $n->read_at !== null,
            'waktu' => $n->created_at,
        ]);

        return response()->json([
            'belum_dibaca' => $pengadu->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function baca(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'Ditandai sudah dibaca.']);
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}