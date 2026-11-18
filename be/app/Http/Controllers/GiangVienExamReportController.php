<?php

namespace App\Http\Controllers;

use App\Services\ExamReportExportService;
use App\Services\ExamReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiangVienExamReportController extends Controller
{
    public function preview(Request $request, ExamReportService $reports): JsonResponse
    {
        $filters = $this->filters($request);
        return response()->json(['data' => $reports->build((int) $request->user()->id, $filters)]);
    }

    public function export(Request $request, ExamReportService $reports, ExamReportExportService $exports): StreamedResponse
    {
        $filters = $this->filters($request);
        $data = $reports->build((int) $request->user()->id, $filters);
        $validated = $request->validate(['sheets' => ['sometimes', 'array', 'min:1'], 'sheets.*' => ['string', 'in:overview,students,results,alerts,review,statistics']]);
        return $exports->export($data, $validated['sheets'] ?? []);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'class_id' => ['required', 'integer', 'min:1'],
            'exam_id' => ['required', 'integer', 'min:1'],
            'session_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                $from = $request->input('from');
                if ($value && $from && $value < $from) $fail('Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.');
            }],
            'participation' => ['nullable', 'in:Đã tham gia,Chưa tham gia'],
            'submission' => ['nullable', 'in:submitted,unsubmitted'],
        ]);
    }
}
