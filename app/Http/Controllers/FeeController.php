<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeRequest;
use App\Models\FeeSetting;
use App\Services\FeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function __construct(protected FeeService $fees)
    {
    }

    public function index(): View
    {
        return view('admin.fees', [
            'current' => $this->fees->current(),
            'history' => FeeSetting::with('createdBy')->orderByDesc('effective_from')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function store(FeeRequest $request): RedirectResponse
    {
        $this->fees->set((float) $request->validated('amount'), $request->validated('effective_from'), $request->validated('note'), $request->user());

        return back()->with('success', 'Consultation fee saved. Existing consultations keep the fee they were charged.');
    }
}
