<?php

namespace App\Providers;

use App\Models\User;
use App\View\Composers\FooterComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use Laravel\Pulse\Facades\Pulse;

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
        View::composer('components.layout', FooterComposer::class);

        Feature::discover();

        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });

        Pulse::user(fn ($user) => [
            'name' => $user->full_name,
            'extra' => $user->email,
            'avatar' => null,
        ]);
    }
}
