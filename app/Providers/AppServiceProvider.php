<?php

namespace App\Providers;

use App\Models\ModelDocument;
use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\ModelProfileBio;
use App\Models\ModelProfilePhysicalRevision;
use App\Models\ModelProfilePublicationTypeHistory;
use App\Models\User;
use App\Policies\ModelDocumentPolicy;
use App\Policies\ModelPhotoPolicy;
use App\Policies\ModelProfileBioPolicy;
use App\Policies\ModelProfilePhysicalRevisionPolicy;
use App\Policies\ModelProfilePolicy;
use App\Policies\ModelProfilePublicationTypeHistoryPolicy;
use App\Policies\UserPolicy;
use App\Services\PublicModelProfileVisibility;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPublicModelProfile', function (?User $user, ModelProfile $profile): Response {
            return app(PublicModelProfileVisibility::class)->publiclyVisible($profile)
                ? Response::allow()
                : Response::denyAsNotFound();
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ModelDocument::class, ModelDocumentPolicy::class);
        Gate::policy(ModelPhoto::class, ModelPhotoPolicy::class);
        Gate::policy(ModelProfile::class, ModelProfilePolicy::class);
        Gate::policy(ModelProfileBio::class, ModelProfileBioPolicy::class);
        Gate::policy(ModelProfilePhysicalRevision::class, ModelProfilePhysicalRevisionPolicy::class);
        Gate::policy(ModelProfilePublicationTypeHistory::class, ModelProfilePublicationTypeHistoryPolicy::class);
    }
}
