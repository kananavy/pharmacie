<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'auditable'); // Eager load user and auditable model

        // Filtering options
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->input('auditable_id'));
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('created_at')->paginate(20);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        return $auditLog->load('user', 'auditable');
    }
}
