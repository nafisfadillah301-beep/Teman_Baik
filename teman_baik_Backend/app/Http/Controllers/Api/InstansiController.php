<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instansi;

class InstansiController extends Controller
{
    public function index()
    {
        return response()->json(
            Instansi::select('id', 'nama_instansi')->orderBy('nama_instansi')->get()
        );
    }
}