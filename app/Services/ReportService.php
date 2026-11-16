<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/** All report figures come from live database queries – nothing is hard-coded. */
class ReportService
{
    /**
     * Revenue and attendance summary for an inclusive date range.
     */
    public function revenueSummary(Carbon $from, Carbon $to): array
    {
        $f = $from->toDateString();
        $t = $to->toDateString();

        $byStatus = Appointment::query()
            ->whereDate('appointment_date', '>=', $f)->whereDate('appointment_date', '<=', $t)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $totalAppointments = (int) $byStatus->sum();
        $completed = (int) ($byStatus[Appointment::COMPLETED] ?? 0);
        $cancelled = (int) ($byStatus[Appointment::CANCELLED] ?? 0);
        $noShow = (int) ($byStatus[Appointment::NO_SHOW] ?? 0);

        $consultations = Consultation::whereDate('consultation_date', '>=', $f)->whereDate('consultation_date', '<=', $t);

        $payments = fn () => Payment::query()->whereDate('paid_at', '>=', $f)->whereDate('paid_at', '<=', $t);

        $methods = $payments()
            ->selectRaw('payment_method, COUNT(*) as n, SUM(amount_paid) as total')
            ->groupBy('payment_method')->get()
            ->map(fn ($r) => [
                'method' => Payment::METHODS[$r->payment_method] ?? $r->payment_method,
                'count' => (int) $r->n,
                'total' => (float) $r->total,
            ])->values()->all();

        $dailyRaw = $payments()
            ->selectRaw('DATE(paid_at) as day, SUM(amount_paid) as total')
            ->groupBy('day')->pluck('total', 'day');

        $daily = [];
        foreach (CarbonPeriod::create($f, $t) as $day) {
            $key = $day->toDateString();
            $daily[] = ['date' => $key, 'total' => (float) ($dailyRaw[$key] ?? 0)];
        }

        $consultationIds = (clone $consultations)->select('id');
        $fees = (float) (clone $consultations)->sum('fee_charged');
        $paidForThese = (float) Payment::whereIn('consultation_id', $consultationIds)->sum('amount_paid');

        $doctorNames = Doctor::with('user')->get()->keyBy('id');
        $perDoctor = Appointment::query()
            ->whereDate('appointment_date', '>=', $f)->whereDate('appointment_date', '<=', $t)
            ->selectRaw("doctor_id, COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show")
            ->groupBy('doctor_id')->get()
            ->map(fn ($r) => [
                'doctor' => $doctorNames[$r->doctor_id]->name ?? 'Unknown',
                'total' => (int) $r->total,
                'completed' => (int) $r->completed,
                'cancelled' => (int) $r->cancelled,
                'no_show' => (int) $r->no_show,
            ])->values()->all();

        $rate = fn (int $n) => $totalAppointments > 0 ? round($n / $totalAppointments * 100, 1) : 0.0;

        return [
            'from' => $f,
            'to' => $t,
            'total_appointments' => $totalAppointments,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
            'scheduled' => (int) ($byStatus[Appointment::SCHEDULED] ?? 0),
            'checked_in' => (int) ($byStatus[Appointment::CHECKED_IN] ?? 0),
            'total_consultations' => (clone $consultations)->count(),
            'total_revenue' => (float) $payments()->sum('amount_paid'),
            'payments_count' => $payments()->count(),
            'outstanding' => max(0, round($fees - $paidForThese, 2)),
            'fees_charged' => $fees,
            'methods' => $methods,
            'daily_revenue' => $daily,
            'attendance' => [
                'completion_rate' => $rate($completed),
                'cancellation_rate' => $rate($cancelled),
                'no_show_rate' => $rate($noShow),
                'per_doctor' => $perDoctor,
            ],
        ];
    }
}
