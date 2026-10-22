<?php

namespace App\Http\Controllers;

use App\Services\LegalAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegalAccessController extends Controller
{
    public function show(): Response
    {
        return response()->view('legal.access', ['denied' => false], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function denied(): Response
    {
        return response()->view('legal.access', ['denied' => true], 403, ['Cache-Control' => 'private, no-store']);
    }

    public function decide(Request $request, LegalAccess $access): Response
    {
        $data = $request->validate(['decision' => 'required|in:accept,reject', 'version' => 'required|string']);
        if ($data['version'] !== (string) config('legal.version')) {
            return redirect()->route('legal.show')->withErrors(['version' => 'El aviso legal cambió. Leé la versión actual antes de continuar.']);
        }

        $accepted = $data['decision'] === 'accept';
        $destination = $accepted ? $request->session()->pull('legal.destination', '/') : route('legal.denied');
        if ($accepted && (! is_string($destination) || ! str_starts_with($destination, '/') || str_starts_with($destination, '//') || str_contains($destination, '\\'))) {
            $destination = '/';
        }

        return redirect()->to($destination, 303)
            ->withCookie($access->cookie($accepted ? 'accepted' : 'denied'))
            ->header('Cache-Control', 'private, no-store');
    }
}
