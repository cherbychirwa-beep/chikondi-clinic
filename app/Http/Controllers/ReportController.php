<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Http\Requests\ReportRangeRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AccessControl;
use App\Services\AuditService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reports,
        protected AccessControl $access,
        protected AuditService $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reports.index', [
            'ownDoctor' => $this->access->doctorFor($user),
            'doctors' => $user->hasRole('doctor') ? collect() : Doctor::with('user')->get()->sortBy('name')->values(),
        ]);
    }

    /** Resolve the doctor a report is for. Doctors are always forced to their own record. */
    protected function resolveDoctor(Request $request): Doctor
    {
        $user = $request->user();

        if ($user->hasRole('doctor')) {
            return $user->doctor ?? abort(403);
        }

        $request->validate(['doctor_id' => ['required', 'integer', 'exists:doctors,id']]);

        return Doctor::with('user')->findOrFail($request->input('doctor_id'));
    }

    /** Daily appointment list per doctor – PDF or Excel. */
    public function daily(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'format' => ['nullable', 'in:pdf,xlsx'],
        ]);

        $doctor = $this->resolveDoctor($request);
        $date = Carbon::parse($request->input('date', today()->toDateString()));

        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date->toDateString())
            ->where('status', '!=', Appointment::CANCELLED)
            ->orderBy('appointment_time')->get();

        $this->audit->log('report.generated', "Daily appointment list for {$doctor->doctor_number} on {$date->toDateString()}", $doctor, $request->user());

        $filename = 'appointments-'.$doctor->doctor_number.'-'.$date->toDateString();

        if ($request->input('format') === 'xlsx') {
            $rows = $appointments->map(fn ($a) => [
                $a->time_short, $a->appointment_number, $a->patient->patient_number, $a->patient->full_name,
                $a->patient->phone, $a->status_label,
            ])->all();

            return Excel::download(new ArrayExport(['Time', 'Appointment No', 'Patient No', 'Patient', 'Phone', 'Status'], $rows, 'Daily list'), $filename.'.xlsx');
        }

        return Pdf::loadView('pdf.daily_list', compact('doctor', 'date', 'appointments'))->download($filename.'.pdf');
    }

    /** Patient list per doctor – Excel only. */
    public function doctorPatients(Request $request)
    {
        $doctor = $this->resolveDoctor($request);

        $patients = Patient::query()
            ->where(function ($w) use ($doctor) {
                $w->where('primary_doctor_id', $doctor->id)
                    ->orWhereHas('appointments', fn ($a) => $a->where('doctor_id', $doctor->id)->where('status', '!=', Appointment::CANCELLED));
            })
            ->orderBy('full_name')->get();

        $this->audit->log('report.generated', "Patient list for {$doctor->doctor_number} ({$patients->count()} patients)", $doctor, $request->user());

        $rows = $patients->map(fn ($p) => [
            $p->patient_number, $p->full_name, $p->date_of_birth->format('Y-m-d'), ucfirst($p->gender),
            $p->phone, $p->address, $p->registered_on->format('Y-m-d'),
            $p->primary_doctor_id === $doctor->id ? 'Registered doctor' : 'Seen / booked',
        ])->all();

        return Excel::download(
            new ArrayExport(['Patient No', 'Name', 'Date of Birth', 'Gender', 'Phone', 'Address', 'Registered On', 'Relationship'], $rows, 'Patients'),
            'patients-'.$doctor->doctor_number.'.xlsx'
        );
    }

    /** Revenue and attendance summary (screen). */
    public function revenue(ReportRangeRequest $request): View
    {
        [$from, $to] = $this->range($request);

        return view('reports.revenue', ['summary' => $this->reports->revenueSummary($from, $to), 'from' => $from, 'to' => $to]);
    }

    public function revenueDownload(ReportRangeRequest $request)
    {
        [$from, $to] = $this->range($request);
        $summary = $this->reports->revenueSummary($from, $to);

        $this->audit->log('report.generated', "Revenue & attendance report {$summary['from']} to {$summary['to']}", null, $request->user());

        $filename = 'revenue-attendance-'.$summary['from'].'_to_'.$summary['to'];

        if ($request->input('format') === 'xlsx') {
            $rows = [
                ['Period', $summary['from'].' to '.$summary['to']],
                ['Total appointments', $summary['total_appointments']],
                ['Completed appointments', $summary['completed']],
                ['Cancelled appointments', $summary['cancelled']],
                ['No-show appointments', $summary['no_show']],
                ['Total consultations', $summary['total_consultations']],
                ['Total revenue (MWK)', $summary['total_revenue']],
                ['Outstanding balances (MWK)', $summary['outstanding']],
                ['Completion rate (%)', $summary['attendance']['completion_rate']],
                ['Cancellation rate (%)', $summary['attendance']['cancellation_rate']],
                ['No-show rate (%)', $summary['attendance']['no_show_rate']],
                [],
                ['PAYMENT METHOD BREAKDOWN', ''],
                ['Method', 'Payments / Total (MWK)'],
            ];
            foreach ($summary['methods'] as $m) {
                $rows[] = [$m['method'].' ('.$m['count'].' payments)', $m['total']];
            }
            $rows[] = [];
            $rows[] = ['DAILY REVENUE (MWK)', ''];
            foreach ($summary['daily_revenue'] as $d) {
                $rows[] = [$d['date'], $d['total']];
            }
            $rows[] = [];
            $rows[] = ['ATTENDANCE PER DOCTOR', ''];
            $rows[] = ['Doctor', 'Total / Completed / Cancelled / No-show'];
            foreach ($summary['attendance']['per_doctor'] as $d) {
                $rows[] = [$d['doctor'], "{$d['total']} / {$d['completed']} / {$d['cancelled']} / {$d['no_show']}"];
            }

            return Excel::download(new ArrayExport(['Metric', 'Value'], $rows, 'Revenue summary'), $filename.'.xlsx');
        }

        return Pdf::loadView('pdf.revenue', ['summary' => $summary])->download($filename.'.pdf');
    }

    /** Visiting doctor schedule – PDF. */
    public function visiting(Request $request)
    {
        $doctors = Doctor::with(['user', 'schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')->orderBy('start_time')])
            ->where('employment_type', Doctor::VISITING)->where('is_active', true)
            ->orderBy('next_visit_date')->get();

        $this->audit->log('report.generated', 'Visiting doctor schedule report', null, $request->user());

        return Pdf::loadView('pdf.visiting_schedule', compact('doctors'))->download('visiting-doctor-schedule.pdf');
    }

    /** Prescription record for a patient – PDF (doctor: own consultations only; admin: all). */
    public function patientPrescriptions(Request $request, Patient $patient)
    {
        Gate::authorize('viewPrescriptions', $patient);

        $consultations = $this->access->consultationsQuery($request->user())
            ->where('patient_id', $patient->id)
            ->with(['doctor.user', 'prescriptions.medicine'])
            ->orderByDesc('consultation_date')->orderByDesc('id')->get();

        $this->audit->log('prescription.viewed', "Prescription record PDF for {$patient->patient_number}", $patient, $request->user());

        return Pdf::loadView('pdf.prescription', compact('patient', 'consultations'))
            ->download('prescriptions-'.$patient->patient_number.'.pdf');
    }

    /** @return array{0:Carbon,1:Carbon} */
    protected function range(ReportRangeRequest $request): array
    {
        $from = Carbon::parse($request->input('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', today()->toDateString()))->startOfDay();

        return [$from, $to];
    }
}
