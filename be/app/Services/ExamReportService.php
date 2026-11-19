<?php

namespace App\Services;

use App\Support\ExamMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Read-only reporting queries. Student identity is always joined through the schema's actual FK. */
class ExamReportService
{
    public function build(int $teacherId, array $filters): array
    {
        $classId = (int) ($filters['class_id'] ?? 0);
        $examId = (int) ($filters['exam_id'] ?? 0);
        $class = DB::table('lop_hoc')->where('id', $classId)->where('giang_vien_id', $teacherId)->first();
        if (!$class) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không tồn tại hoặc bạn không có quyền xem.']);
        }
        $exam = DB::table('bai_tap')->where('id', $examId)->where('lop_hoc_id', $classId)->first();
        if (!$exam) {
            throw ValidationException::withMessages(['exam_id' => 'Bài kiểm tra không thuộc lớp đã chọn.']);
        }
        if (!empty($filters['session_id']) && !DB::table('phien_giam_sat')->where('id', $filters['session_id'])->where('lop_hoc_id', $classId)->where('bai_tap_id', $examId)->exists()) {
            throw ValidationException::withMessages(['session_id' => 'Phiên giám sát không thuộc bài kiểm tra và lớp đã chọn.']);
        }

        $members = DB::table('thanh_vien_lop as tv')
            ->join('nguoi_dung as nd', 'nd.id', '=', 'tv.sinh_vien_id')
            ->leftJoin('sinh_vien as sv', 'sv.nguoi_dung_id', '=', 'nd.id')
            ->where('tv.lop_hoc_id', $classId)->where('tv.trang_thai', 'dang_hoc')
            ->select('nd.id as user_id', 'nd.ho_ten', 'nd.email', 'sv.id as student_profile_id', 'sv.ma_sinh_vien')
            ->orderBy('nd.ho_ten')->get();

        $submissions = DB::table('bai_nop as bn')
            ->leftJoin('diem as d', 'd.bai_nop_id', '=', 'bn.id')
            ->where('bn.bai_tap_id', $examId)
            ->select('bn.id', 'bn.sinh_vien_id as user_id', 'bn.thoi_gian_nop', 'bn.trang_thai as submission_status', 'd.diem', 'd.nhan_xet', 'd.thoi_gian_cham')
            ->get()->keyBy('user_id');

        $attemptsByUser = collect();
        if (Schema::hasTable('bai_kiem_tra_luot_lam')) {
            $attemptsByUser = DB::table('bai_kiem_tra_luot_lam as a')
                ->join('sinh_vien as sv', 'sv.id', '=', 'a.sinh_vien_id')
                ->where('a.bai_tap_id', $examId)
                ->select('sv.nguoi_dung_id as user_id', 'a.id as attempt_id', 'a.bat_dau_luc')
                ->get()->keyBy('user_id');
        }

        $events = $this->alertRows($examId, $classId, $filters);
        $events = $this->normalizeAlertRows($events);
        $this->attachSafeEvidenceReferences($events);
        if (!empty($filters['session_id'])) {
            $events = array_values(array_filter($events, fn($event) => (int) ($event['session_id'] ?? 0) === (int) $filters['session_id']));
        }
        $events = $this->deduplicateEvents($events);
        $alertsByUser = collect($events)->groupBy('user_id');
        $students = [];
        foreach ($members as $member) {
            $attempt = $attemptsByUser->get($member->user_id);
            $submission = $submissions->get($member->user_id);
            $alerts = $alertsByUser->get($member->user_id, collect());
            $cameraAlertCount = $alerts->filter(fn($event) => in_array('camera', $event['source_keys'] ?? [], true))->count();
            $protectionAlertCount = $alerts->filter(fn($event) => (bool) array_intersect(['exam_protection', 'exam_violation'], $event['source_keys'] ?? []))->count();
            $reviewSummary = $this->reviewSummary($alerts);
            $graded = $submission && $submission->diem !== null;
            $student = [
                'user_id' => $member->user_id, 'student_profile_id' => $member->student_profile_id,
                'name' => $member->ho_ten, 'student_code' => $member->ma_sinh_vien,
                'email' => $member->email, 'attempt_id' => $attempt->attempt_id ?? null,
                'started_at' => $attempt->bat_dau_luc ?? null, 'participation' => $attempt ? 'Đã tham gia' : 'Chưa tham gia',
                'submission_id' => $submission->id ?? null, 'submitted_at' => $submission->thoi_gian_nop ?? null,
                'submission_status' => $submission ? 'Đã nộp' : ($attempt ? 'Đang làm / chưa nộp' : 'Chưa nộp'),
                'score' => $submission->diem ?? null, 'grading_status' => $graded ? 'Đã chấm' : ($submission ? 'Chưa chấm' : 'Chưa có bài nộp'),
                'comment' => $submission->nhan_xet ?? null, 'graded_at' => $submission->thoi_gian_cham ?? null,
                'alert_count' => $alerts->count(), 'camera_alert_count' => $cameraAlertCount,
                'exam_protection_alert_count' => $protectionAlertCount, 'alert_review_summary' => $reviewSummary,
                'alerts' => $alerts->values()->all(),
            ];
            $students[] = $student;
        }

        $sessionQuery = DB::table('phien_giam_sat')->where('lop_hoc_id', $classId)->where('bai_tap_id', $examId);
        if (!empty($filters['session_id'])) $sessionQuery->where('id', $filters['session_id']);
        $sessions = $sessionQuery->orderBy('thoi_gian_bat_dau')->get();

        $selectedStudents = collect($students)
            ->when(($filters['participation'] ?? '') !== '', fn($c) => $c->where('participation', $filters['participation']))
            ->when(($filters['submission'] ?? '') === 'submitted', fn($c) => $c->where('submission_id', '!=', null))
            ->when(($filters['submission'] ?? '') === 'unsubmitted', fn($c) => $c->whereNull('submission_id'))
            ->when(!empty($filters['from']) || !empty($filters['to']), fn($c) => $c->filter(fn($student) => $this->matchesDateRange($student['started_at'] ?? null, $filters) || $this->matchesDateRange($student['submitted_at'] ?? null, $filters)))
            ->values();
        $scored = $selectedStudents->whereNotNull('score')->pluck('score');
        $alertCounts = collect($events)->groupBy('type_label')->map(fn($group, $label) => ['label' => $label, 'count' => $group->count()])->values();

        return [
            'meta' => [
                'class' => $class,
                'exam' => $exam,
                'sessions' => $sessions,
                'filters' => $filters,
                'generated_at' => now()->toISOString(),
                'exam_mode' => ExamMode::forAssignment($examId),
                'exam_mode_label' => ExamMode::forAssignment($examId) === ExamMode::LAB_NO_CAMERA
                    ? 'Thi tại phòng máy — Không yêu cầu camera'
                    : 'Thi trực tuyến — Camera AI bắt buộc',
                'camera_monitoring' => ExamMode::requiresCamera($examId),
            ],
            'students' => $selectedStudents->all(),
            'alerts' => $events,
            'summary' => [
                'eligible' => $members->count(), 'participated' => $selectedStudents->whereNotNull('attempt_id')->count(),
                'unparticipated' => $selectedStudents->whereNull('attempt_id')->count(),
                'in_progress' => $selectedStudents->where('submission_id', null)->whereNotNull('attempt_id')->count(),
                'submitted' => $selectedStudents->whereNotNull('submission_id')->count(),
                'unsubmitted' => $selectedStudents->whereNull('submission_id')->count(),
                'graded' => $selectedStudents->where('grading_status', 'Đã chấm')->count(),
                'ungraded' => $selectedStudents->where('grading_status', 'Chưa chấm')->count(),
                'mean_score' => $scored->isEmpty() ? null : round((float) $scored->avg(), 2),
                'alert_total' => count($events), 'students_with_alert' => collect($events)->pluck('user_id')->unique()->count(),
                'alerts_by_type' => $alertCounts->all(),
            ],
        ];
    }

    private function alertRows(int $examId, int $classId, array $filters): array
    {
        $rows = [];
        if (Schema::hasTable('canh_bao')) {
            $q = DB::table('canh_bao as cb')->join('phien_giam_sat as p', 'p.id', '=', 'cb.phien_id')
                ->join('nguoi_dung as nd', 'nd.id', '=', 'cb.sinh_vien_id')
                ->leftJoin('sinh_vien as sv', 'sv.nguoi_dung_id', '=', 'nd.id')
                ->where('p.lop_hoc_id', $classId)->where('p.bai_tap_id', $examId)
                ->when(!empty($filters['session_id']), fn($q) => $q->where('p.id', $filters['session_id']))
                ->when(!empty($filters['from']), fn($q) => $q->whereDate('cb.thoi_gian', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn($q) => $q->whereDate('cb.thoi_gian', '<=', $filters['to']))
                ->select('cb.id', DB::raw('NULL as event_id'), 'cb.loai_canh_bao as type', 'cb.thoi_gian as happened_at', 'cb.noi_dung as description', 'nd.id as user_id', 'sv.ma_sinh_vien', 'nd.ho_ten', DB::raw("'session' as source"), 'p.ten_phien as session_name', 'cb.phien_id as session_id', 'cb.da_xu_ly');
            foreach ($q->get() as $row) $rows[] = (array) $row + ['review_status' => !empty($row->da_xu_ly) ? 'Đã xử lý; chưa có kết luận' : 'Chưa xử lý; chưa có kết luận', 'review_action' => !empty($row->da_xu_ly) ? 'Đã xử lý' : 'Chưa xử lý', 'reviewed_at' => null, 'reviewer_name' => null, 'evidence_reference' => null];
        }
        if (Schema::hasTable('canh_bao_camera')) {
            $hasEvidenceSession = Schema::hasTable('anh_man_hinh') && Schema::hasColumn('anh_man_hinh', 'event_id') && Schema::hasColumn('anh_man_hinh', 'phien_id');
            $q = DB::table('canh_bao_camera as c')->join('sinh_vien as sv', 'sv.id', '=', 'c.sinh_vien_id')
                ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')
                ->where('c.bai_tap_id', $examId)->when(!empty($filters['from']), fn($q) => $q->whereDate('c.created_at', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn($q) => $q->whereDate('c.created_at', '<=', $filters['to']))
                ->when(!empty($filters['session_id']) && !$hasEvidenceSession, fn($q) => $q->whereRaw('1 = 0'));
            if ($hasEvidenceSession) $q->leftJoin('anh_man_hinh as evidence_link', 'evidence_link.event_id', '=', 'c.event_id')
                ->leftJoin('phien_giam_sat as p', 'p.id', '=', 'evidence_link.phien_id')
                ->when(!empty($filters['session_id']), fn($q) => $q->where('evidence_link.phien_id', $filters['session_id']));
            $q->select('c.id', 'c.event_id', 'c.loai_canh_bao as type', 'c.created_at as happened_at', DB::raw('NULL as description'), 'sv.nguoi_dung_id as user_id', 'sv.ma_sinh_vien', 'nd.ho_ten', DB::raw("'camera' as source"), $hasEvidenceSession ? 'evidence_link.phien_id as session_id' : DB::raw('NULL as session_id'), $hasEvidenceSession ? 'p.ten_phien as session_name' : DB::raw('NULL as session_name'));
            foreach ($q->get() as $row) $rows[] = (array) $row + ['review_status' => 'Không có trạng thái xem xét', 'review_action' => 'Không có trạng thái xem xét', 'reviewed_at' => null, 'reviewer_name' => null, 'evidence_reference' => null];
        }
        if (Schema::hasTable('su_kien_bao_ve_bai_kiem_tra')) {
            $q = DB::table('su_kien_bao_ve_bai_kiem_tra as e')->join('sinh_vien as sv', 'sv.id', '=', 'e.sinh_vien_id')
                ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')->where('e.bai_tap_id', $examId)
                ->when(!empty($filters['from']), fn($q) => $q->whereDate('e.thoi_gian_su_kien', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn($q) => $q->whereDate('e.thoi_gian_su_kien', '<=', $filters['to']))
                ->when(!empty($filters['session_id']), fn($q) => $q->where('e.phien_id', $filters['session_id']))
                ->leftJoin('phien_giam_sat as p', 'p.id', '=', 'e.phien_id')
                ->leftJoin('nguoi_dung as viewer', 'viewer.id', '=', 'e.xem_boi_id')
                ->select('e.id', 'e.event_id', 'e.loai_su_kien as type', 'e.thoi_gian_su_kien as happened_at', 'e.thong_tin_tin_hieu', 'sv.nguoi_dung_id as user_id', 'sv.ma_sinh_vien', 'nd.ho_ten', DB::raw("'exam_protection' as source"), 'e.phien_id as session_id', 'p.ten_phien as session_name', 'e.da_xem', 'e.xem_boi_id', 'viewer.ho_ten as reviewer_name', 'e.xem_luc as reviewed_at');
            foreach ($q->get() as $row) {
                $event = (array) $row;
                $signal = json_decode((string) ($row->thong_tin_tin_hieu ?? ''), true);
                $details = [];
                if (is_array($signal)) {
                    if (!empty($signal['signal']) && is_string($signal['signal'])) $details[] = 'Tín hiệu: '.$signal['signal'];
                    if (!empty($signal['source']) && is_string($signal['source'])) $details[] = 'Nguồn tín hiệu: '.$signal['source'];
                }
                $event['description'] = $details ? implode('; ', $details).'. Trình duyệt ghi nhận tín hiệu; không xác nhận đã chụp ảnh.' : null;
                unset($event['thong_tin_tin_hieu']);
                $event['review_status'] = !empty($row->da_xem) ? 'Đã xem; chưa có kết luận' : 'Chưa xem; chưa có kết luận';
                $event['review_action'] = !empty($row->da_xem) ? 'Đã xem' : 'Chưa xem';
                $event['evidence_reference'] = null;
                $rows[] = $event;
            }
        }
        if (Schema::hasTable('vi_pham_kiem_tra')) {
            $q = DB::table('vi_pham_kiem_tra as v')->join('sinh_vien as sv', 'sv.id', '=', 'v.sinh_vien_id')
                ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')->where('v.bai_tap_id', $examId)
                ->when(!empty($filters['from']), fn($q) => $q->whereDate('v.created_at', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn($q) => $q->whereDate('v.created_at', '<=', $filters['to']))
                ->select('v.id', 'v.event_id', 'v.loai_vi_pham as type', 'v.created_at as happened_at', 'v.lan_thu', 'sv.nguoi_dung_id as user_id', 'sv.ma_sinh_vien', 'nd.ho_ten', DB::raw("'exam_violation' as source"), DB::raw('NULL as session_id'), DB::raw('NULL as session_name'));
            foreach ($q->get() as $row) {
                $event = (array) $row;
                $event['description'] = isset($row->lan_thu) ? 'Lần vi phạm theo hệ thống: '.$row->lan_thu : null;
                $rows[] = $event + ['review_status' => 'Không có trạng thái xem xét', 'review_action' => 'Không có trạng thái xem xét', 'reviewed_at' => null, 'reviewer_name' => null, 'evidence_reference' => null];
            }
        }
        return $rows;
    }

    /** Only expose a safe record identifier; never return private disk paths or URLs. */
    private function attachSafeEvidenceReferences(array &$events): void
    {
        if (!Schema::hasTable('anh_man_hinh')) return;
        $eventIds = collect($events)->pluck('event_id')->filter()->unique()->values();
        if ($eventIds->isEmpty() || !Schema::hasColumn('anh_man_hinh', 'event_id')) return;
        $references = DB::table('anh_man_hinh')->whereIn('event_id', $eventIds)->pluck('id', 'event_id');
        foreach ($events as &$event) {
            $event['evidence_reference'] = isset($references[$event['event_id'] ?? '']) ? 'Ảnh #'.$references[$event['event_id']] : null;
        }
    }

    private function normalizeAlertRows(array $events): array
    {
        foreach ($events as &$event) {
            $event['source_keys'] = [$event['source']];
            $event['source_family'] = match ($event['source']) {
                'camera' => 'camera_ai', 'exam_protection', 'exam_violation' => 'exam_protection', default => 'session',
            };
            $event['source_label'] = $this->sourceLabel($event['source']);
            $event['type_label'] = $this->alertTypeLabel((string) $event['type']);
            $event['confirmed_result'] = null;
        }
        unset($event);
        return $events;
    }

    private function alertTypeLabel(string $type): string
    {
        $labels = [
            'phat_hien_dien_thoai' => 'Phát hiện điện thoại', 'phone_detected' => 'Phát hiện điện thoại',
            'nhieu_khuon_mat' => 'Nhiều khuôn mặt', 'khong_co_khuon_mat' => 'Không thấy khuôn mặt',
            'camera_ngat' => 'Camera bị ngắt', 'roi_tab' => 'Rời tab', 'fullscreen_exit' => 'Thoát toàn màn hình',
            'SCREENSHOT_SHORTCUT_DETECTED' => 'Nghi vấn phím chụp màn hình', 'focus_lost' => 'Mất focus',
            'copy_blocked' => 'Sao chép bị chặn', 'cut_blocked' => 'Cắt nội dung bị chặn',
            'select_all_blocked' => 'Chọn toàn bộ bị chặn', 'context_menu_blocked' => 'Menu chuột phải bị chặn',
            'drag_blocked' => 'Kéo thả nội dung bị chặn', 'ung_dung_khong_cho_phep' => 'Ứng dụng không được phép',
            'website_khong_cho_phep' => 'Website không được phép', 'copy_paste' => 'Sao chép/dán',
            'roi_phien' => 'Rời phiên giám sát', 'mat_ket_noi' => 'Mất kết nối',
        ];
        if (isset($labels[$type])) return $labels[$type];
        return 'Cảnh báo khác: '.ucfirst(str_replace(['_', '-'], ' ', $type));
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'camera' => 'Camera AI', 'session' => 'Giám sát phiên',
            'exam_protection' => 'Bảo vệ bài thi', 'exam_violation' => 'Vi phạm bài thi',
            default => 'Nguồn khác',
        };
    }

    private function reviewSummary($alerts): string
    {
        if ($alerts->isEmpty()) return 'Không có cảnh báo';
        $states = $alerts->pluck('review_action')->unique()->values();
        return $states->count() === 1 ? $states->first() : $states->implode('; ');
    }

    private function matchesDateRange(?string $timestamp, array $filters): bool
    {
        if (!$timestamp) return false;
        $date = substr($timestamp, 0, 10);
        return (empty($filters['from']) || $date >= $filters['from']) && (empty($filters['to']) || $date <= $filters['to']);
    }

    private function deduplicateEvents(array $events): array
    {
        return collect($events)->groupBy(fn($event) => !empty($event['event_id']) ? 'event:'.$event['event_id'] : $event['source'].':'.$event['id'])
            ->map(function ($duplicates) {
                $primary = $duplicates->first();
                $primary['source_keys'] = $duplicates->flatMap(fn($event) => $event['source_keys'] ?? [$event['source']])->unique()->values()->all();
                $primary['source'] = $primary['source_keys'][0] ?? $primary['source'];
                $primary['source_label'] = collect($primary['source_keys'])->map(fn($source) => $this->sourceLabel($source))->unique()->implode(', ');
                foreach ($duplicates as $event) {
                    foreach (['da_xem', 'da_xu_ly', 'xem_boi_id', 'xem_luc', 'reviewed_at', 'reviewer_name', 'evidence_reference', 'description', 'session_id', 'session_name'] as $field) {
                        if (empty($primary[$field]) && !empty($event[$field])) $primary[$field] = $event[$field];
                    }
                }
                if (array_key_exists('da_xem', $primary)) {
                    $primary['review_status'] = !empty($primary['da_xem']) ? 'Đã xem; chưa có kết luận' : 'Chưa xem; chưa có kết luận';
                    $primary['review_action'] = !empty($primary['da_xem']) ? 'Đã xem' : 'Chưa xem';
                } elseif (array_key_exists('da_xu_ly', $primary)) {
                    $primary['review_action'] = !empty($primary['da_xu_ly']) ? 'Đã xử lý' : 'Chưa xử lý';
                }
                return $primary;
            })->values()->all();
    }
}
