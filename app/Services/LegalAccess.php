<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class LegalAccess
{
    public function state(Request $request): ?string
    {
        // Laravel's EncryptCookies authenticates and decrypts this value first.
        $value = json_decode($request->cookie(config('legal.cookie'), ''), true);
        if (! is_array($value)
            || ($value['version'] ?? null) !== (string) config('legal.version')
            || ! is_int($value['expires'] ?? null)
            || $value['expires'] <= now()->timestamp) {
            return null;
        }

        return in_array($value['state'] ?? null, ['accepted', 'denied'], true) ? $value['state'] : null;
    }

    public function cookie(string $state): Cookie
    {
        $minutes = max(1, (int) config('legal.days')) * 24 * 60;

        return cookie(config('legal.cookie'), json_encode([
            'version' => (string) config('legal.version'),
            'expires' => now()->addMinutes($minutes)->timestamp,
            'state' => $state,
        ], JSON_THROW_ON_ERROR), $minutes, '/', null, config('legal.secure'), true, false, 'lax');
    }
}
