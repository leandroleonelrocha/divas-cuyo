<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AccountEnrollmentState;
use App\Services\EmailVerificationService;

class RegistrationController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function pending()
    {
        return view('auth.verification-status', [
            'email' => session('registered_email'),
        ]);
    }

    public function store(RegisterRequest $request, EmailVerificationService $verificationService)
    {
        $user = new User([
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'name' => null,
            'whatsapp' => null,
            'location' => null,
            'is_published' => false,
        ]);

        $user->forceFill(['profile_completion_status' => AccountEnrollmentState::INCOMPLETE])->save();

        $verificationService->send($user);

        return redirect()->route('registration.pending')->with('registered_email', $user->email);
    }
}
