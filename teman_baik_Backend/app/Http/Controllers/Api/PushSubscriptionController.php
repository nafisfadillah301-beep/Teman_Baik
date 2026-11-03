<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $cred = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $cred['endpoint']],
            [
                'p256dh_key' => $cred['keys']['p256dh'],
                'auth_key' => $cred['keys']['auth'],
            ]
        );

        return response()->json(['message' => 'Subscription tersimpan.'], 201);
    }

    public function destroy(Request $request)
    {
        $cred = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint', $cred['endpoint'])
            ->delete();

        return response()->json(['message' => 'Subscription dihapus.']);
    }

    public function kunci()
    {
        return response()->json(['public_key' => config('services.vapid.public_key')]);
    }
}