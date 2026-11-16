<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ScheduleRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\DoctorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    use ResolvesPerPage;

    public function __construct(protected DoctorService $doctors)
    {
    }

    /** Admin and receptionist (read-only for receptionist; manage buttons hidden AND routes protected). */
    public function index(Request $request): View
    {
        $doctors = Doctor::with('user')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(fn ($w) => $w->where('doctor_number', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('employment_type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('doctor_number')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('doctors.index', ['doctors' => $doctors]);
    }

    public function show(Doctor $doctor): View
    {
        $doctor->load(['user', 'schedules' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time')]);

        return view('doctors.show', [
            'doctor' => $doctor,
            'upcomingCount' => Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', '>=', today()->toDateString())
                ->where('status', Appointment::SCHEDULED)->count(),
            'days' => DoctorSchedule::DAYS,
        ]);
    }

    public function create(): View
    {
        return view('doctors.form', ['doctor' => null]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        try {
            $doctor = $this->doctors->register($request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['name' => $e->getMessage()]);
        }

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor registered. Now configure their weekly availability below.');
    }

    public function edit(Doctor $doctor): View
    {
        return view('doctors.form', ['doctor' => $doctor->load('user')]);
    }

    public function update(DoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        try {
            $this->doctors->update($doctor, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['name' => $e->getMessage()]);
        }

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor profile updated.');
    }

    public function toggle(Request $request, Doctor $doctor): RedirectResponse
    {
        $this->doctors->setActive($doctor, ! $doctor->is_active, $request->user());

        return back()->with('success', 'Doctor '.($doctor->is_active ? 'activated' : 'deactivated').'.');
    }

    public function storeSchedule(ScheduleRequest $request, Doctor $doctor): RedirectResponse
    {
        try {
            $this->doctors->addSchedule($doctor, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['start_time' => $e->getMessage()]);
        }

        return back()->with('success', 'Availability added.');
    }

    public function destroySchedule(Request $request, Doctor $doctor, DoctorSchedule $schedule): RedirectResponse
    {
        $this->doctors->removeSchedule($schedule, $request->user());

        return back()->with('success', 'Availability removed.');
    }
}
