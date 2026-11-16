<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\PaymentRequest;
use App\Models\Consultation;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ResolvesPerPage;

    public function __construct(protected PaymentService $payments)
    {
    }

    public function index(Request $request): View
    {
        $payments = Payment::with(['patient', 'consultation', 'recordedBy'])
            ->when($request->filled('receipt'), fn ($q) => $q->where('receipt_number', 'like', '%'.trim($request->receipt).'%'))
            ->when($request->filled('patient_number'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('patient_number', 'like', '%'.trim($request->patient_number).'%')))
            ->when($request->filled('method') && array_key_exists($request->method, Payment::METHODS), fn ($q) => $q->where('payment_method', $request->method))
            ->when($request->filled('status') && array_key_exists($request->status, Payment::STATUSES), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('paid_at', $request->date))
            ->orderByDesc('paid_at')->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('payments.index', ['payments' => $payments]);
    }

    /** Consultations that still carry an outstanding balance. */
    public function pending(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $consultations = Consultation::outstanding()
            ->with(['patient', 'doctor.user'])
            ->withSum('payments as paid_total', 'amount_paid')
            ->when($term !== '', fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($term)))
            ->orderByDesc('consultation_date')->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('payments.pending', ['consultations' => $consultations]);
    }

    public function create(Consultation $consultation): View|RedirectResponse
    {
        $consultation->load(['patient', 'doctor.user']);

        if ((float) $consultation->outstandingBalance() <= 0) {
            return redirect()->route('payments.pending')->with('info', 'This consultation has already been paid in full.');
        }

        return view('payments.create', [
            'consultation' => $consultation,
            'paid' => $consultation->totalPaid(),
            'outstanding' => $consultation->outstandingBalance(),
            'token' => (string) Str::uuid(),
            'previous' => $consultation->payments()->orderBy('paid_at')->get(),
        ]);
    }

    public function store(PaymentRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();

        try {
            $result = $this->payments->record(
                $consultation,
                (float) $data['amount_paid'],
                $data['payment_method'],
                $data['submission_token'],
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['amount_paid' => $e->getMessage()])->with('error', $e->getMessage());
        }

        $payment = $result['payment'];

        return redirect()->route('payments.show', $payment)->with(
            $result['duplicate'] ? 'info' : 'success',
            $result['duplicate']
                ? 'This payment was already recorded – no duplicate was created.'
                : "Payment recorded. Receipt {$payment->receipt_number}."
        );
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        return view('payments.show', ['payment' => $payment->load(['patient', 'consultation.doctor.user', 'recordedBy'])]);
    }

    public function receipt(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load(['patient', 'consultation.doctor.user', 'recordedBy']);

        return Pdf::loadView('pdf.receipt', ['payment' => $payment])
            ->setPaper('a5')
            ->download($payment->receipt_number.'.pdf');
    }
}
