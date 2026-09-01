<?php

namespace App\Providers;

use App\Models\Constant;
use App\Models\User;
use App\Services\RoleAbilitiesService;
use App\Observers\ConstantObserver;
use App\Observers\UserObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind('abilities', function () {
            return include base_path('data/abilities.php');
        });

        $this->app->singleton(RoleAbilitiesService::class, function () {
            return new RoleAbilitiesService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::before(function ($user, $ability) {
            if ($user instanceof User && $user->super_admin) {
                return true;
            }
        });

        Gate::define('reports.view', function (User $user) {
            return $user->roles->where('role_name', 'reports.view')->isNotEmpty();
        });

        User::observe(UserObserver::class);
        Constant::observe(ConstantObserver::class);
    }
}
