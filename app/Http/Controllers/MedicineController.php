<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\MedicineRequest;
use App\Models\Medicine;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    use ResolvesPerPage;

    public function __construct(protected AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('generic_name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.medicines.index', ['medicines' => $medicines]);
    }

    public function create(): View
    {
        return view('admin.medicines.form', ['medicine' => null]);
    }

    public function store(MedicineRequest $request): RedirectResponse
    {
        $medicine = Medicine::create($request->validated() + ['is_active' => true]);
        $this->audit->log('medicine.created', "Added medicine {$medicine->label}", $medicine);

        return redirect()->route('medicines.index')->with('success', 'Medicine added to the catalogue.');
    }

    public function edit(Medicine $medicine): View
    {
        return view('admin.medicines.form', ['medicine' => $medicine]);
    }

    public function update(MedicineRequest $request, Medicine $medicine): RedirectResponse
    {
        $medicine->update($request->validated());
        $this->audit->log('medicine.updated', "Updated medicine {$medicine->label}", $medicine);

        return redirect()->route('medicines.index')->with('success', 'Medicine updated.');
    }

    /** Medicines are deactivated, never deleted, so old prescriptions stay intact. */
    public function toggle(Medicine $medicine): RedirectResponse
    {
        $medicine->update(['is_active' => ! $medicine->is_active]);
        $this->audit->log('medicine.updated', ($medicine->is_active ? 'Activated' : 'Deactivated')." medicine {$medicine->label}", $medicine);

        return back()->with('success', 'Medicine '.($medicine->is_active ? 'activated' : 'deactivated').'.');
    }
}
