<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StatistikController extends Controller
{
    public function index()
    {
        return response()->json([
            ...$this->hitung(),
            'ikm' => $this->ikm(),
        ]);
    }

    private function hitung(): array
    {
        return Cache::remember('statistik:hitung', 600, function () {
            $idDiproses = Status::whereIn('status', ['diverifikasi', 'dikirim', 'dijawab'])->pluck('id');

            return [
                'total_laporan' => Pengaduan::count(),
                'laporan_diproses' => Pengaduan::whereIn('id_status', $idDiproses)->count(),
            ];
        });
    }

    private function ikm(): ?array
    {
        if ($tersimpan = Cache::get('statistik:ikm')) {
            return $tersimpan;
        }

        if (Cache::has('statistik:ikm:gagal')) {
            return null;
        }

        try {
            $respons = Http::timeout(5)->get(
                'https://sheets.googleapis.com/v4/spreadsheets/'
                . config('services.ikm.sheet_id') . '/values/' . config('services.ikm.range'),
                ['key' => config('services.ikm.api_key')]
            );

            $values = $respons->json('values');

            if ($respons->successful() && is_array($values) && count($values) >= 3) {
                $data = [
                    'tahun' => $values[0][0] ?? null,
                    'bulan' => $values[1][0] ?? null,
                    'nilai' => $values[2][0] ?? null,
                ];

                Cache::put('statistik:ikm', $data, 3600);

                return $data;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        Cache::put('statistik:ikm:gagal', true, 300);

        return null;
    }
}