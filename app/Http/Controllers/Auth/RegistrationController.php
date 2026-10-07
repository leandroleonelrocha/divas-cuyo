<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\PublicationTypeChangeService;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function show()
    {
        return view('auth.register', [
            'provinces' => Province::query()->active()->orderBy('name')->get(),
            'publicationTypes' => PublicationType::query()->active()
                ->whereIn('slug', array_keys(config('publication.monthly_prices_ars')))
                ->orderBy('name')->get(),
        ]);
    }

    public function pending()
    {
        return view('auth.verification-status', [
            'email' => session('registered_email'),
        ]);
    }

    public function store(RegisterRequest $request, EmailVerificationService $verificationService)
    {
        $user = DB::transaction(function () use ($request): User {
            $province = Province::query()->active()->findOrFail($request->integer('province_id'));
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->input('password'),
                'whatsapp' => $request->string('whatsapp')->toString(),
                'location' => $province->name,
                'is_published' => false,
            ]);

            $profile = $user->modelProfile()->create([
                'name' => $request->string('name')->toString(),
                'whatsapp' => $request->string('whatsapp')->toString(),
                'location' => $province->name,
                'province_id' => $province->id,
                'is_published' => false,
                'review_status' => 'pending',
            ]);

            app(PublicationTypeChangeService::class)->change(
                $profile,
                PublicationType::query()->findOrFail($request->integer('publication_type_id')),
                $user,
                'model',
                'Modalidad elegida durante el registro.',
            );

            $user->policyAcceptances()->createMany([
                [
                    'policy' => 'terms',
                    'version' => config('policies.terms_version'),
                    'accepted_at' => now(),
                ],
                [
                    'policy' => 'privacy',
                    'version' => config('policies.privacy_version'),
                    'accepted_at' => now(),
                ],
            ]);

            return $user;
        });

        $verificationService->send($user);

        return redirect()->route('registration.pending')->with('registered_email', $user->email);
    }
}
