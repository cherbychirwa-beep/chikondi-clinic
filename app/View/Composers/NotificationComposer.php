<?php

namespace App\View\Composers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Services\AccessControl;
use Illuminate\View\View;

/** Supplies the topbar notification list (role-specific, from live data). */
class NotificationComposer
{
    public function __construct(protected AccessControl $access)
    {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();
        $items = [];

        if ($user && $user->role) {
            $today = today()->toDateString();

            if ($user->hasPermission('checkin.perform')) {
                $awaiting = Appointment::whereDate('appointment_date', $today)->where('status', Appointment::SCHEDULED)->count();
                if ($awaiting > 0) {
                    $items[] = ['text' => "{$awaiting} patient(s) awaiting check-in", 'route' => route('checkin.index')];
                }
            }

            if ($user->hasPermission('payments.record')) {
                $pending = Consultation::outstanding()->count();
                if ($pending > 0) {
                    $items[] = ['text' => "{$pending} consultation(s) with unpaid balance", 'route' => route('payments.pending')];
                }
            }

            if ($user->hasPermission('consultations.perform')) {
                $waiting = $this->access->appointmentsQuery($user)
                    ->whereDate('appointment_date', $today)->where('status', Appointment::CHECKED_IN)->count();
                if ($waiting > 0) {
                    $items[] = ['text' => "{$waiting} patient(s) waiting for you", 'route' => route('consultations.queue')];
                }
            }
        }

        $view->with('notifications', $items);
    }
}
