<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Product;
use App\Models\Report;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly AdminLogService $adminLog) {}

    public function index(Request $request): JsonResponse
    {
        $reports = Report::query()
            ->with('reporter.profile')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reportable_type'), fn ($q) => $q->where('reportable_type', $request->string('reportable_type')))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return response()->json([
            'data' => ReportResource::collection($reports->items()),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $report->forceFill([
            'status' => $request->string('status'),
            'resolution_note' => $request->input('resolution_note'),
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ])->save();

        if ($request->boolean('block_reportable') && $report->reportable_type === 'product') {
            /** @var ?Product $product */
            $product = Product::find($report->reportable_id);
            $product?->forceFill([
                'status' => Product::STATUS_BLOCKED,
                'rejection_reason' => 'Signalement : '.($request->input('resolution_note') ?? $report->reason),
            ])->save();
        }

        $this->adminLog->log($request->user(), 'report.resolve', $report, $request->string('status'), $request->ip());

        return response()->json(['data' => new ReportResource($report)]);
    }
}
