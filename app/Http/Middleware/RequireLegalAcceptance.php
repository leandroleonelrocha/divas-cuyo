<?php

namespace App\Http\Middleware;

use App\Services\LegalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLegalAcceptance
{
    public function handle(Request $request, Closure $next): Response
    {
        $state = app(LegalAccess::class)->state($request);
        if ($state !== 'accepted') {
            if (! $request->routeIs('public.models.photos.show')) {
                $request->session()->put('legal.destination', '/'.ltrim($request->getRequestUri(), '/'));
            }
            $response = $request->expectsJson()
                ? response()->json(['message' => 'Se requiere aceptar el aviso legal.', 'url' => route('legal.show')], 403)
                : redirect()->route($state === 'denied' ? 'legal.denied' : 'legal.show');
        } else {
            $response = $next($request);
        }

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}
