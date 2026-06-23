<?php

namespace App\Providers;

use App\Events\AuditLogEvent;
use App\Listeners\AuditLogListener;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\Customer;
use App\Models\Products;
use App\Models\Purchase_Order;
use App\Models\Sales_Order;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\ProductObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\SalesOrderObserver;
use App\Observers\SupplierObserver;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if (! File::exists(public_path('storage')) && ! is_link(public_path('storage'))) {
            try {
                File::link(storage_path('app/public'), public_path('storage'));
            } catch (\Throwable $e) {
                // Ignore if the environment does not allow symlink creation.
            }
        }

        // Register policies
        Gate::policy(User::class, UserPolicy::class);

        // ── Model Observers — Event-driven cache invalidation ──
        Products::observe(ProductObserver::class);
        Sales_Order::observe(SalesOrderObserver::class);
        Purchase_Order::observe(PurchaseOrderObserver::class);
        Supplier::observe(SupplierObserver::class);
        Customer::observe(CustomerObserver::class);

        // ── Event → Listener bindings (single source of truth) ──
        // Event::listen(AuditLogEvent::class, AuditLogListener::class);
        // Event::listen(Login::class, LogSuccessfulLogin::class);
        // Event::listen(Logout::class, LogSuccessfulLogout::class);
    }
}
