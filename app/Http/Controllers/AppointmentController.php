<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Requests\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AccessControl;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use ResolvesPerPage;

    public function __construct(
        protected AppointmentService $appointments,
        protected AvailabilityService $availability,
        protected AccessControl $access,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $appointments = $this->access->appointmentsQuery($user)
            ->with(['patient', 'doctor.user'])
            ->when($request->filled('date'), fn ($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->filled('doctor_id') && ! $user->hasRole('doctor'), fn ($q) => $q->where('appointments.doctor_id', $request->doctor_id))
            ->when($request->filled('status') && array_key_exists($request->status, Appointment::STATUSES), fn ($q) => $q->where('appointments.status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($request->q)))
            ->orderByDesc('appointment_date')->orderBy('appointment_time')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'doctors' => $user->hasRole('doctor') ? collect() : Doctor::with('user')->get()->sortBy('name')->values(),
        ]);
    }

    /** Booking workflow: 1) find patient  2) pick date  3) choose slot with the registered or an alternative doctor. */
    public function book(Request $request): View
    {
        $patient = null;
        $matches = null;
        $primarySlots = collect();
        $alternatives = collect();
        $date = today();

        if ($request->filled('date')) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay();
            } catch (\Throwable) {
                $date = today();
            }
            if ($date->lt(today())) {
                $date = today();
            }
        }

        if ($request->filled('patient_id')) {
            $patient = Patient::with('primaryDoctor.user')->find($request->input('patient_id'));
        } elseif ($request->filled('q')) {
            $matches = Patient::search($request->input('q'))->with('primaryDoctor.user')->orderBy('full_name')->limit(25)->get();
            if ($matches->count() === 1) {
                return redirect()->route('appointments.book', ['patient_id' => $matches->first()->id]);
            }
        }

        if ($patient) {
            $primary = $patient->primaryDoctor;
            $primarySlots = $primary ? $this->availability->availableSlots($primary, $date) : collect();

            // Alternatives are offered when the registered doctor has nothing, or when explicitly requested.
            if ($primarySlots->isEmpty() || $request->boolean('show_all')) {
                $alternatives = $this->availability->alternativeDoctors($date, $primary?->id);
            }
        }

        return view('appointments.book', [
            'patient' => $patient,
            'matches' => $matches,
            'date' => $date,
            'primarySlots' => $primarySlots,
            'alternatives' => $alternatives,
        ]);
    }

    public function store(BookAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $appointment = $this->appointments->book(
                Patient::findOrFail($data['patient_id']),
                Doctor::findOrFail($data['doctor_id']),
                $data['appointment_date'],
                $data['appointment_time'],
                $request->user(),
            );
        } catch (SlotUnavailableException|ValidationException $e) {
            return redirect()->route('appointments.book', ['patient_id' => $data['patient_id'], 'date' => $data['appointment_date']])
                ->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment booked successfully.');
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);

        return view('appointments.show', [
            'appointment' => $appointment->load(['patient', 'doctor.user', 'bookedBy', 'checkedInBy', 'cancellation.cancelledBy']),
        ]);
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        try {
            $this->appointments->cancel($appointment, $request->validated('reason'), $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Appointment cancelled. The slot is available for booking again.');
    }

    /** Visual schedule: every active doctor's slots for the chosen date. */
    public function schedule(Request $request): View
    {
        try {
            $date = $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay() : today();
        } catch (\Throwable) {
            $date = today();
        }

        $doctors = Doctor::active()->with('user')->get()->sortBy('name')->values();

        $bookings = Appointment::with('patient')
            ->whereDate('appointment_date', $date->toDateString())
            ->where('status', '!=', Appointment::CANCELLED)
            ->get()
            ->groupBy('doctor_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($a) => $a->time_short));

        $rows = $doctors->map(fn (Doctor $d) => [
            'doctor' => $d,
            'slots' => $this->availability->slotsFor($d, $date),
            'bookings' => $bookings->get($d->id, collect()),
        ]);

        return view('appointments.schedule', ['date' => $date, 'rows' => $rows]);
    }
}
