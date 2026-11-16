<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Http\Requests\StaffRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    use ResolvesPerPage;

    public function __construct(protected StaffService $staff)
    {
    }

    public function index(Request $request): View
    {
        $users = User::with('role')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->role_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.staff.index', ['users' => $users, 'roles' => Role::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['staff' => null, 'roles' => $this->assignableRoles()]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $user = $this->staff->create($request->validated(), $request->user());

        return redirect()->route('staff.index')->with('success', "Staff account {$user->username} created. They must change the temporary password at first login.");
    }

    public function edit(User $user): View|RedirectResponse
    {
        if ($user->hasRole('doctor')) {
            return redirect()->route('doctors.index')->with('error', 'Doctor accounts are managed in the Doctors module.');
        }

        return view('admin.staff.form', ['staff' => $user, 'roles' => $this->assignableRoles()]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        if ($user->hasRole('doctor')) {
            return redirect()->route('doctors.index')->with('error', 'Doctor accounts are managed in the Doctors module.');
        }

        try {
            $this->staff->update($user, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['name' => $e->getMessage()]);
        }

        return redirect()->route('staff.index')->with('success', 'Staff account updated.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        try {
            $this->staff->setActive($user, ! $user->is_active, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Account '.($user->is_active ? 'activated' : 'deactivated').'.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $temporary = $this->staff->resetPassword($user, $request->user());

        return back()->with('success', "Temporary password for {$user->username}: {$temporary} (shown once – they must change it at next login).");
    }

    protected function assignableRoles()
    {
        return Role::whereIn('slug', ['admin', 'receptionist', 'attendant'])->orderBy('name')->get();
    }
}
