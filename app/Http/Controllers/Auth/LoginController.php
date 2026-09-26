<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthenticatedUserRedirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticatedUserRedirector $redirector)
    {
        $email = $request->string('email')->toString();
        $user = User::query()->where('email', $email)->first();

        if ($user && Hash::check($request->string('password')->toString(), $user->password)) {
            if (! $user->hasVerifiedEmail()) {
                return back()->withErrors([
                    'email' => 'Verificá tu correo electrónico antes de iniciar sesión.',
                ])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return $redirector->redirect($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales no son válidas.',
        ])->onlyInput('email');
    }
}
