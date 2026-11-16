<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CheckInController extends Controller
{
    public function __construct(protected AppointmentService $appointments)
    {
    }

    /** Today's appointments, optionally narrowed by patient number. */
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $appointments = Appointment::with(['patient', 'doctor.user'])
            ->whereDate('appointment_date', today()->toDateString())
            ->when($term !== '', fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('patient_number', 'like', '%'.$term.'%')))
            ->when($request->filled('status') && array_key_exists($request->status, Appointment::STATUSES), fn ($q) => $q->where('status', $request->status))
            ->orderBy('appointment_time')
            ->get();

        return view('checkin.index', [
            'appointments' => $appointments,
            'term' => $term,
            'notFound' => $term !== '' && $appointments->isEmpty(),
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('checkIn', $appointment);

        try {
            $this->appointments->checkIn($appointment, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$appointment->patient->full_name} has been checked in.");
    }

    public function noShow(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('checkIn', $appointment);

        try {
            $this->appointments->markNoShow($appointment, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Appointment marked as no-show.');
    }
}
