<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $cred = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('web')->attempt($cred)) {
            return response()->json(['message' => 'Username atau password salah.'], 422);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'id_instansi' => $user->id_instansi,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function gantiPassword(Request $request)
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string', 'current_password:web'],
            'password_baru' => 'required|string|min:8|max:100|different:password_lama|confirmed',
        ]);

        $user = $request->user();
        $user->password = $data['password_baru'];
        $user->save();

        return response()->json(['message' => 'Password berhasil diganti.']);
    }
}