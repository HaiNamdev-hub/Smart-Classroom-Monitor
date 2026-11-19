<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebRtcIceConfigController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $turn = config('services.turn');
        $servers = [[
            'urls' => config('services.stun.urls', [
                'stun:stun.l.google.com:19302',
                'stun:stun1.l.google.com:19302',
                'stun:stun2.l.google.com:19302',
            ]),
        ]];
        $turnConfigured = is_array($turn['urls'] ?? null)
            && $turn['urls'] !== []
            && is_string($turn['shared_secret'] ?? null)
            && $turn['shared_secret'] !== '';
        $expiresAt = null;

        if ($turnConfigured) {
            $expiresAt = now()->addMinutes(max(2, min(60, (int) ($turn['ttl_minutes'] ?? 10))));
            $username = $expiresAt->timestamp.':'.$request->user()->getAuthIdentifier();
            $servers[] = [
                'urls' => $turn['urls'],
                'username' => $username,
                'credential' => base64_encode(hash_hmac('sha1', $username, $turn['shared_secret'], true)),
                'credentialType' => 'password',
            ];
        }

        return response()->json([
            'iceServers' => $servers,
            'turnConfigured' => $turnConfigured,
            'expiresAt' => $expiresAt?->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
