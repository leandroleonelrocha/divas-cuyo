<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Models\User;
use App\Services\EmailVerificationService;

class EmailVerificationController extends Controller
{
    public function verify(string $token, EmailVerificationService $verificationService)
    {
        $user = $verificationService->verify($token);

        if (! $user) {
            return redirect()->route('register.show')->with('error', 'El enlace de verificación no es válido o venció.');
        }

        return redirect()->route('login.show')->with('status', 'Tu correo fue verificado. Ya podés iniciar sesión.');
    }

    public function resend(ResendVerificationRequest $request, EmailVerificationService $verificationService)
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $verificationService->send($user);
        }

        return back()->with('status', 'Si la cuenta existe y necesita verificación, recibirás un nuevo correo.');
    }
}
