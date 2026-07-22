<?php

namespace App\Providers;

use App\Events\TransferCreated;
use App\Listeners\SendTransferCreatedTelegramNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;

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
        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('view-transfers', fn (User $user) =>
        true
        );

        Gate::define('create-transfer', fn (User $user) =>
        $user->isCoordinator()
        );

        Gate::define('receive-payment', fn (User $user) =>
        $user->isCoordinator()
        );

        Gate::define('cancel-transfer', fn (User $user) =>
        $user->isCoordinator()
        );

        Gate::define('execute-transfer', fn (User $user) =>
            $user->isExecutor() || $user->isTransferExecutor()
        );

        Gate::define('view-capital-transfers', fn (User $user) =>
            $user->isCoordinator() || $user->isExecutor()
        );

        Gate::define('create-capital-transfer', fn (User $user) =>
        $user->isCoordinator()
        );

        Gate::define('view-capital-accounts', fn (User $user) =>
        !$user->isTransferExecutor()
        );

        Gate::define('manage-capital-accounts', fn (User $user) =>
        $user->isExecutor()
        );

        Gate::define('manage-exchange-rates', fn (User $user) =>
        $user->isExecutor()
        );

        Gate::define('manage-commission-rules', fn (User $user) =>
        $user->isExecutor()
        );

        Gate::define('view-financial-dashboard', fn (User $user) =>
        !$user->isTransferExecutor()
        );

        Gate::define('update-transfer', fn (User $user) => $user->isCoordinator());

    }
}
