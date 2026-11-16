<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\ConsultationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PaymentPolicy;
use App\Services\AccessControl;
use App\Services\DatabaseConnection;
use App\Support\Permissions;
use App\View\Composers\NotificationComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        // Clinic-local time (Malawi) for "today", appointment slots and receipts.
        date_default_timezone_set(config('chikondi.timezone', 'Africa/Blantyre'));

        // Singleton database connection wrapper + shared access-control service.
        $this->app->singleton(DatabaseConnection::class, fn () => new DatabaseConnection);
        $this->app->singleton(AccessControl::class, fn () => new AccessControl);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer('layouts.app', NotificationComposer::class);

        // One Gate per permission slug (users get them through their role).
        foreach (Permissions::all() as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
        }

        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Consultation::class, ConsultationPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
