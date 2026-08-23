<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminLogResource;
use App\Models\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AdminLog::query()
            ->with('admin.profile')
            ->when($request->filled('admin_id'), fn ($q) => $q->where('admin_id', $request->integer('admin_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 30), 100));

        return response()->json([
            'data' => AdminLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
