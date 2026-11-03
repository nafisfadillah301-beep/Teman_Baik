<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private const POLA_USERNAME = '/^[A-Za-z0-9._-]+$/';

    public function index()
    {
        return response()->json(
            User::where('role', 'admin_instansi')
                ->with('instansi')
                ->orderBy('nama')
                ->get()
                ->map(fn ($u) => $this->tampil($u))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', 'regex:' . self::POLA_USERNAME, 'unique:users,username'],
            'email' => 'required|email:rfc,dns|max:255',
            'password' => 'required|string|min:8|max:100',
            'id_instansi' => [
                'required', 'exists:instansis,id',
                Rule::unique('users', 'id_instansi')->where('role', 'admin_instansi'),
            ],
        ], [
            'id_instansi.unique' => 'Instansi ini sudah punya akun admin yang bisa login.',
        ]);

        $user = new User();
        $user->fill(Arr::only($data, ['nama', 'username', 'email', 'password']));
        $user->role = 'admin_instansi';
        $user->id_instansi = $data['id_instansi'];
        $user->save();

        return response()->json($this->tampil($user->load('instansi')), 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'admin_instansi', 404);

        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes', 'required', 'string', 'max:50', 'regex:' . self::POLA_USERNAME,
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => 'sometimes|required|email:rfc,dns|max:255',
            'password' => 'sometimes|required|string|min:8|max:100', // reset password
            'id_instansi' => [
                'sometimes', 'required', 'exists:instansis,id',
                Rule::unique('users', 'id_instansi')->where('role', 'admin_instansi')->ignore($user->id),
            ],
        ], [
            'id_instansi.unique' => 'Instansi ini sudah punya akun admin yang bisa login.',
        ]);

        $user->fill(Arr::only($data, ['nama', 'username', 'email', 'password']));

        if (isset($data['id_instansi'])) {
            $user->id_instansi = $data['id_instansi'];
        }

        $user->save();

        return response()->json($this->tampil($user->load('instansi')));
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'admin_instansi', 404);

        $user->delete();

        return response()->json(['message' => 'Akun berhasil dihapus.']);
    }

    private function tampil(User $u): array
    {
        return [
            'id' => $u->id,
            'nama' => $u->nama,
            'username' => $u->username,
            'email' => $u->email,
            'id_instansi' => $u->id_instansi,
            'instansi' => $u->instansi?->nama_instansi,
            'dibuat_pada' => $u->created_at,
        ];
    }
}