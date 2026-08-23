<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $request->string('reportable_type'),
            'reportable_id' => $request->integer('reportable_id'),
            'reason' => $request->string('reason'),
            'message' => $request->input('message'),
            'status' => Report::STATUS_PENDING,
        ]);

        return response()->json(['data' => new ReportResource($report)], 201);
    }
}
