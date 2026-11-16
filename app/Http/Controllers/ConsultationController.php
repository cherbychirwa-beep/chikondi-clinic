<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\ConsultationRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Medicine;
use App\Services\AccessControl;
use App\Services\AuditService;
use App\Services\ConsultationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    use ResolvesPerPage;

    public function __construct(
        protected ConsultationService $consultations,
        protected AccessControl $access,
        protected AuditService $audit,
    ) {
    }

    /** The doctor's queue for today: checked-in patients first. */
    public function queue(Request $request): View
    {
        $appointments = $this->access->appointmentsQuery($request->user())
            ->with('patient')
            ->whereDate('appointment_date', today()->toDateString())
            ->whereIn('status', [Appointment::CHECKED_IN, Appointment::SCHEDULED, Appointment::COMPLETED])
            ->orderByRaw("CASE status WHEN 'checked_in' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('appointment_time')
            ->get();

        return view('consultations.queue', ['appointments' => $appointments]);
    }

    public function index(Request $request): View
    {
        $consultations = $this->access->consultationsQuery($request->user())
            ->with(['patient', 'doctor.user', 'prescriptions'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($request->q)))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('consultation_date', $request->date))
            ->orderByDesc('consultation_date')->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('consultations.index', ['consultations' => $consultations]);
    }

    /** Consultation workspace. */
    public function create(Request $request, Appointment $appointment): View|RedirectResponse
    {
        Gate::authorize('consult', $appointment);

        if ($appointment->status === Appointment::COMPLETED && $appointment->consultation) {
            return redirect()->route('consultations.show', $appointment->consultation)->with('info', 'This consultation has already been recorded.');
        }
        if ($appointment->status !== Appointment::CHECKED_IN) {
            return redirect()->route('consultations.queue')->with('error', 'The patient must be checked in before consultation.');
        }

        $user = $request->user();
        $appointment->load(['patient.primaryDoctor.user', 'doctor.user']);

        $this->audit->log('patient.viewed', "Opened consultation workspace for {$appointment->patient->patient_number}", $appointment->patient, $user);

        $history = $this->access->consultationsQuery($user)
            ->where('patient_id', $appointment->patient_id)
            ->with('prescriptions.medicine')
            ->orderByDesc('consultation_date')->orderByDesc('id')->get();

        return view('consultations.create', [
            'appointment' => $appointment,
            'patient' => $appointment->patient,
            'history' => $history,
            'medicines' => Medicine::active()->orderBy('name')->get(),
        ]);
    }

    public function store(ConsultationRequest $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('consult', $appointment);
        $data = $request->validated();

        try {
            $consultation = $this->consultations->record(
                $appointment,
                collect($data)->only(['consultation_date', 'diagnosis', 'observations', 'notes'])->all(),
                array_values($data['prescriptions'] ?? []),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['diagnosis' => $e->getMessage()])->with('error', $e->getMessage());
        } catch (AuthorizationException $e) {
            throw $e;
        }

        return redirect()->route('consultations.show', $consultation)->with('success', 'Consultation saved and appointment completed.');
    }

    public function show(Request $request, Consultation $consultation): View
    {
        Gate::authorize('view', $consultation);

        $consultation->load(['patient', 'doctor.user', 'appointment', 'prescriptions.medicine']);
        $this->audit->log('prescription.viewed', "Viewed consultation #{$consultation->id} and prescriptions of {$consultation->patient->patient_number}", $consultation, $request->user());

        return view('consultations.show', ['consultation' => $consultation]);
    }
}
