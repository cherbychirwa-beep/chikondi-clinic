<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPerPage;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only: there is deliberately no create/update/delete route for audit records. */
class AuditLogController extends Controller
{
    use ResolvesPerPage;

    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', trim($request->action).'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.trim($request->q).'%'))
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.audit', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name', 'username']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
