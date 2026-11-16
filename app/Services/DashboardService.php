<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/** Role-specific dashboard metrics, all computed from live data. */
class DashboardService
{
    public function __construct(protected AccessControl $access)
    {
    }

    public function forUser(User $user): array
    {
        return match ($user->role->slug) {
            'admin' => $this->admin(),
            'receptionist' => $this->receptionist(),
            'attendant' => $this->attendant(),
            'doctor' => $this->doctor($user),
            default => [],
        };
    }

    protected function today(): string
    {
        return today()->toDateString();
    }

    protected function outstandingTotal(): float
    {
        $fees = (float) Consultation::sum('fee_charged');
        $paid = (float) Payment::sum('amount_paid');

        return max(0, round($fees - $paid, 2));
    }

    /** @return array{labels:array<int,string>,values:array<int,float>} */
    protected function series(string $model, string $dateColumn, ?string $sumColumn, int $days): array
    {
        $from = today()->subDays($days - 1);
        $query = $model::query()
            ->whereDate($dateColumn, '>=', $from->toDateString())
            ->whereDate($dateColumn, '<=', $this->today());

        $select = $sumColumn ? "DATE($dateColumn) as d, SUM($sumColumn) as v" : "DATE($dateColumn) as d, COUNT(*) as v";
        $raw = $query->selectRaw($select)->groupBy('d')->pluck('v', 'd');

        $labels = [];
        $values = [];
        foreach (CarbonPeriod::create($from, today()) as $day) {
            $labels[] = $day->format('d M');
            $values[] = (float) ($raw[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function admin(): array
    {
        $today = $this->today();

        return [
            'patients' => Patient::count(),
            'doctors' => Doctor::active()->count(),
            'staff' => User::where('is_active', true)->count(),
            'appointments_today' => Appointment::whereDate('appointment_date', $today)->where('status', '!=', Appointment::CANCELLED)->count(),
            'consultations_today' => Consultation::whereDate('consultation_date', $today)->count(),
            'revenue_today' => (float) Payment::whereDate('paid_at', $today)->sum('amount_paid'),
            'outstanding' => $this->outstandingTotal(),
            'appointment_trend' => $this->series(Appointment::class, 'appointment_date', null, 14),
            'revenue_trend' => $this->series(Payment::class, 'paid_at', 'amount_paid', 14),
        ];
    }

    public function receptionist(): array
    {
        $today = $this->today();

        return [
            'appointments_today' => Appointment::whereDate('appointment_date', $today)->where('status', '!=', Appointment::CANCELLED)->count(),
            'upcoming' => Appointment::whereDate('appointment_date', '>', $today)->where('status', Appointment::SCHEDULED)->count(),
            'cancelled_today' => Appointment::where('status', Appointment::CANCELLED)->whereHas('cancellation', fn ($q) => $q->whereDate('cancelled_at', $today))->count(),
            'next_appointments' => Appointment::with(['patient', 'doctor.user'])
                ->whereDate('appointment_date', '>=', $today)->where('status', Appointment::SCHEDULED)
                ->orderBy('appointment_date')->orderBy('appointment_time')->limit(8)->get(),
        ];
    }

    public function attendant(): array
    {
        $today = $this->today();
        $todayQuery = fn () => Appointment::whereDate('appointment_date', $today);

        return [
            'appointments_today' => $todayQuery()->where('status', '!=', Appointment::CANCELLED)->count(),
            'awaiting' => $todayQuery()->where('status', Appointment::SCHEDULED)->count(),
            'checked_in' => $todayQuery()->where('status', Appointment::CHECKED_IN)->count(),
            'pending_payments' => Consultation::outstanding()->count(),
            'awaiting_list' => $todayQuery()->with(['patient', 'doctor.user'])->where('status', Appointment::SCHEDULED)
                ->orderBy('appointment_time')->limit(8)->get(),
        ];
    }

    public function doctor(User $user): array
    {
        $doctor = $this->access->doctorFor($user);
        $id = $doctor?->id ?? 0;
        $today = $this->today();
        $own = fn () => Appointment::where('doctor_id', $id);

        return [
            'appointments_today' => $own()->whereDate('appointment_date', $today)->where('status', '!=', Appointment::CANCELLED)->count(),
            'waiting' => $own()->whereDate('appointment_date', $today)->where('status', Appointment::CHECKED_IN)->count(),
            'completed_today' => Consultation::where('doctor_id', $id)->whereDate('consultation_date', $today)->count(),
            'upcoming' => $own()->whereDate('appointment_date', '>', $today)->where('status', Appointment::SCHEDULED)->count(),
            'waiting_list' => $own()->with('patient')->whereDate('appointment_date', $today)->where('status', Appointment::CHECKED_IN)
                ->orderBy('checked_in_at')->get(),
            'recent_patients' => Consultation::with('patient')->where('doctor_id', $id)
                ->orderByDesc('consultation_date')->orderByDesc('id')->limit(6)->get(),
        ];
    }
}
