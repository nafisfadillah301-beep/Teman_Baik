<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengadu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class PengaduController extends Controller
{
    private const MAKS_GAGAL = 5;
    private const DETIK_BLOKIR = 12 * 60 * 60; 

    public function login(Request $request)
    {
        $cred = $request->validate([
            'nik' => 'required|digits:16',
            'nama' => 'required|string',
        ]);

        $limiterKey = 'login-pengadu:' . $cred['nik'] . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAKS_GAGAL)) {
            $menit = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);

            return response()->json([
                'message' => "Terlalu banyak percobaan gagal. Coba lagi dalam {$menit} menit.",
            ], 429);
        }

        $pengadu = Pengadu::where('nik', $cred['nik'])->first();

        if ($pengadu) {
            if (strtolower($pengadu->nama) !== strtolower($cred['nama'])) {
                RateLimiter::hit($limiterKey, self::DETIK_BLOKIR);

                return response()->json([
                    'message' => 'NIK dan nama tidak cocok dengan data kependudukan.',
                ], 422);
            }
        } else {
            // 1. Ganti $valid = true dengan pemanggilan API verifikasi NIK + nama.
            // 2. Kalau API timeout/error, balas 503 ("Layanan verifikasi sedang bermasalah")
            //    TANPA RateLimiter::hit(), karena itu bukan kesalahan pengguna.
            // 3. Setelah tahu kuota API, sesuaikan batas throttle per IP di routes/api.php.
            $valid = true;

            if (! $valid) {
                RateLimiter::hit($limiterKey, self::DETIK_BLOKIR);

                return response()->json([
                    'message' => 'NIK dan nama tidak cocok dengan data kependudukan.',
                ], 422);
            }

            $pengadu = Pengadu::create([
                'nik' => $cred['nik'],
                'nama' => $cred['nama'],
                'verified_at' => now(),
            ]);
        }

        RateLimiter::clear($limiterKey);

        Auth::guard('pengadu')->login($pengadu);
        $request->session()->regenerate();

        return response()->json([
            'pengadu' => [
                'id' => $pengadu->id,
                'nama' => $pengadu->nama,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('pengadu')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}