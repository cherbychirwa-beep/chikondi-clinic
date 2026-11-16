<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\PatientRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AccessControl;
use App\Services\AuditService;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    use ResolvesPerPage;

    public function __construct(
        protected PatientService $patients,
        protected AccessControl $access,
        protected AuditService $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $query = $this->access->patientsQuery($request->user())
            ->with('primaryDoctor.user')
            ->search($request->input('q'))
            ->when($request->filled('dob'), fn ($q) => $q->whereDate('date_of_birth', $request->dob))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->gender))
            ->orderBy('full_name');

        return view('patients.index', [
            'patients' => $query->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('patients.form', ['patient' => null, 'doctors' => $this->doctors()]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        try {
            $patient = $this->patients->register($request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['full_name' => $e->getMessage()]);
        }

        return redirect()->route('patients.show', $patient)
            ->with('success', "Patient registered. Patient number: {$patient->patient_number}");
    }

    public function show(Request $request, Patient $patient): View
    {
        Gate::authorize('view', $patient);
        $user = $request->user();

        $this->audit->log('patient.viewed', "Viewed record of {$patient->patient_number}", $patient, $user);

        $patient->load('primaryDoctor.user', 'registeredBy');

        $appointments = $user->hasPermission('appointments.view')
            ? $this->access->appointmentsQuery($user)->where('patient_id', $patient->id)
                ->with('doctor.user')->orderByDesc('appointment_date')->orderByDesc('appointment_time')->limit(50)->get()
            : null;

        $consultations = $user->hasPermission('consultations.view')
            ? $this->access->consultationsQuery($user)->where('patient_id', $patient->id)
                ->with('doctor.user', 'prescriptions.medicine')->orderByDesc('consultation_date')->orderByDesc('id')->get()
            : null;

        $payments = $user->hasPermission('payments.view')
            ? $patient->payments()->with('consultation')->orderByDesc('paid_at')->get()
            : null;

        return view('patients.show', compact('patient', 'appointments', 'consultations', 'payments'));
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('update', $patient);

        return view('patients.form', ['patient' => $patient, 'doctors' => $this->doctors($patient)]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        try {
            $this->patients->update($patient, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['full_name' => $e->getMessage()]);
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Patient information updated.');
    }

    protected function doctors(?Patient $patient = null)
    {
        return Doctor::with('user')
            ->where(fn ($q) => $q->where('is_active', true)->when($patient?->primary_doctor_id, fn ($w) => $w->orWhere('id', $patient->primary_doctor_id)))
            ->get()->sortBy('name')->values();
    }
}
