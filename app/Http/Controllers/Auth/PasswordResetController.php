<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(ForgotPasswordRequest $request)
    {
        Password::sendResetLink(['email' => $request->string('email')->toString()]);

        return redirect()->route('password.request')->with('status', 'Si el correo está registrado, recibirás instrucciones para recuperar tu contraseña.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login.show')->with('status', 'Tu contraseña fue actualizada. Ya podés iniciar sesión.');
        }

        return redirect()->route('password.reset', [
            'token' => $request->string('token')->toString(),
            'email' => $request->string('email')->toString(),
        ])->withErrors([
            'email' => 'El enlace de recuperación no es válido o venció. Solicitá uno nuevo.',
        ]);
    }
}
