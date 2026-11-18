<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_report_maps_user_and_student_profile_ids_and_restricts_class_access(): void
    {
        $teacher = $this->user('giang_vien', 'teacher@example.test');
        $this->user('sinh_vien', 'filler@example.test');
        $studentUser = $this->user('sinh_vien', 'student@example.test');
        $this->academicSetup();
        $profileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUser->id, 'nganh_id' => 1, 'ma_sinh_vien' => 'TEST0001',
            'nam_trung_tuyen' => 2025, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertNotSame((int) $studentUser->id, $profileId);
        DB::table('nguoi_dung')->where('id', $studentUser->id)->update(['ho_ten' => 'Nguyễn Hoàng An']);
        $classId = DB::table('lop_hoc')->insertGetId(['ma_lop' => 'REPORT01', 'ten_lop' => 'Lớp báo cáo', 'giang_vien_id' => $teacher->id, 'created_at' => now(), 'updated_at' => now()]);
        $examId = DB::table('bai_tap')->insertGetId(['lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacher->id, 'tieu_de' => 'Kiểm tra báo cáo', 'thoi_gian_bat_dau' => now(), 'thoi_gian_ket_thuc' => now()->addHour(), 'han_nop' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
        $sessionId = DB::table('phien_giam_sat')->insertGetId(['lop_hoc_id' => $classId, 'bai_tap_id' => $examId, 'nguoi_tao_id' => $teacher->id, 'ten_phien' => 'Phiên kiểm thử', 'thoi_gian_bat_dau' => now(), 'trang_thai' => 'dang_dien_ra', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('thanh_vien_lop')->insert(['lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUser->id, 'trang_thai' => 'dang_hoc', 'created_at' => now(), 'updated_at' => now()]);
        $attemptId = DB::table('bai_kiem_tra_luot_lam')->insertGetId(['bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'bat_dau_luc' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $submissionId = DB::table('bai_nop')->insertGetId(['bai_tap_id' => $examId, 'sinh_vien_id' => $studentUser->id, 'ten_file' => 'online', 'duong_dan' => '', 'thoi_gian_nop' => now(), 'trang_thai' => 'dung_han', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('diem')->insert(['bai_nop_id' => $submissionId, 'nguoi_cham_id' => $teacher->id, 'diem' => 9.5, 'created_at' => now(), 'updated_at' => now()]);
        $eventId = (string) Str::uuid();
        DB::table('canh_bao_camera')->insert(['bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'event_id' => $eventId, 'loai_canh_bao' => 'phone_detected', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 6) as $index) {
            DB::table('canh_bao_camera')->insert(['bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'event_id' => (string) Str::uuid(), 'loai_canh_bao' => $index % 2 ? 'nhieu_khuon_mat' : 'khong_co_khuon_mat', 'created_at' => now()->addSeconds($index), 'updated_at' => now()->addSeconds($index)]);
        }
        DB::table('su_kien_bao_ve_bai_kiem_tra')->insert(['event_id' => $eventId, 'bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'phien_id' => $sessionId, 'loai_su_kien' => 'SCREENSHOT_SHORTCUT_DETECTED', 'thong_tin_tin_hieu' => json_encode(['signal' => 'PrintScreen', 'source' => 'keydown']), 'da_xem' => true, 'xem_boi_id' => $teacher->id, 'xem_luc' => now(), 'thoi_gian_su_kien' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $privatePath = 'private/evidence/never-export-this.jpg';
        $evidenceId = DB::table('anh_man_hinh')->insertGetId(['phien_id' => $sessionId, 'sinh_vien_id' => $studentUser->id, 'bai_tap_id' => $examId, 'event_id' => $eventId, 'nguon' => 'webcam', 'loai_canh_bao' => 'phat_hien_dien_thoai', 'duong_dan' => $privatePath, 'storage_disk' => 'private', 'mime_type' => 'image/jpeg', 'kich_thuoc_bytes' => 12, 'thoi_gian_chup' => now(), 'created_at' => now()]);

        $response = $this->actingAs($teacher, 'sanctum')->getJson('/api/giang-vien/bao-cao-ky-thi?class_id='.$classId.'&exam_id='.$examId);
        $response->assertOk()->assertJsonPath('data.students.0.user_id', $studentUser->id)
            ->assertJsonPath('data.students.0.student_profile_id', $profileId)
            ->assertJsonPath('data.students.0.attempt_id', $attemptId)
            ->assertJsonPath('data.students.0.submission_id', $submissionId)
            ->assertJsonPath('data.students.0.student_code', 'TEST0001')
            ->assertJsonPath('data.students.0.name', 'Nguyễn Hoàng An')
            ->assertJsonPath('data.students.0.alert_count', 7)
            ->assertJsonPath('data.students.0.camera_alert_count', 7)
            ->assertJsonPath('data.students.0.exam_protection_alert_count', 1)
            ->assertJsonPath('data.summary.alert_total', 7)
            ->assertJsonPath('data.students.0.score', 9.5)
            ->assertJsonPath('data.summary.mean_score', 9.5);

        DB::table('nguoi_dung')->where('id', $studentUser->id)->update(['ho_ten' => '=SUM(1,1)']);
        $countsBeforeExport = [DB::table('bai_nop')->count(), DB::table('diem')->count(), DB::table('canh_bao')->count(), DB::table('canh_bao_camera')->count(), DB::table('su_kien_bao_ve_bai_kiem_tra')->count(), DB::table('vi_pham_kiem_tra')->count(), DB::table('anh_man_hinh')->count()];
        $excelResponse = $this->actingAs($teacher, 'sanctum')->post('/api/giang-vien/bao-cao-ky-thi/xuat-excel', ['class_id' => $classId, 'exam_id' => $examId]);
        $tempFile = tempnam(sys_get_temp_dir(), 'exam-report-injection-');
        try {
            file_put_contents($tempFile, $excelResponse->streamedContent());
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setIncludeCharts(true);
            $book = $reader->load($tempFile);
            $nameCell = $book->getSheetByName('Sinh viên')->getCell('B5');
            $this->assertSame("'=SUM(1,1)", $nameCell->getValue());
            $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $nameCell->getDataType());
            $this->assertSame(7, (int) $book->getSheetByName('Sinh viên')->getCell('J5')->getValue());
            $this->assertSame(7, (int) $book->getSheetByName('Sinh viên')->getCell('K5')->getValue());
            $this->assertSame(1, (int) $book->getSheetByName('Sinh viên')->getCell('L5')->getValue());
            $alertSheet = $book->getSheetByName('Cảnh báo');
            $this->assertSame(11, $alertSheet->getHighestDataRow());
            $this->assertSame('Phát hiện điện thoại', $alertSheet->getCell('G5')->getValue());
            $this->assertSame("'=SUM(1,1)", $alertSheet->getCell('C5')->getValue());
            $this->assertSame('Lớp báo cáo', $alertSheet->getCell('D5')->getValue());
            $this->assertSame('Kiểm tra báo cáo', $alertSheet->getCell('E5')->getValue());
            $this->assertSame('Phiên kiểm thử', $alertSheet->getCell('F5')->getValue());
            $overview = $book->getSheetByName('Tổng quan');
            $this->assertSame('BÁO CÁO TỔNG QUAN KỲ THI', $overview->getCell('A1')->getValue());
            $this->assertSame(1, (int) $overview->getCell('A11')->getValue());
            $this->assertSame(1, (int) $overview->getCell('F11')->getValue());
            $this->assertSame(0, (int) $overview->getCell('K11')->getValue());
            $this->assertSame(1, (int) $overview->getCell('A15')->getValue());
            $this->assertSame(9.5, (float) $overview->getCell('F15')->getValue());
            $this->assertSame(7, (int) $overview->getCell('K15')->getValue());
            $this->assertSame(3, (int) $overview->getCell('E39')->getValue());
            $this->assertSame(1, (int) $overview->getCell('L38')->getValue());
            $this->assertSame('dd/mm/yyyy hh:mm:ss', $overview->getStyle('M7')->getNumberFormat()->getFormatCode());
            $this->assertSame('dd/mm/yyyy hh:mm:ss', $overview->getStyle('I6')->getNumberFormat()->getFormatCode());
            $this->assertSame('General', $overview->getStyle('C5')->getNumberFormat()->getFormatCode());
            $this->assertSame('Phát hiện điện thoại', $overview->getCell('P6')->getValue());
            $this->assertSame(1, (int) $overview->getCell('Q6')->getValue());
            $this->assertSame(1, (int) $overview->getCell('Q2')->getValue());
            $this->assertSame(0, (int) $overview->getCell('Q3')->getValue());
            $this->assertFalse($overview->getColumnDimension('P')->getVisible());
            $this->assertSame('A1:N43', $overview->getPageSetup()->getPrintArea());
            $this->assertSame(1, $overview->getPageSetup()->getFitToHeight());
            $this->assertSame(7, (int) $book->getSheetByName('Thống kê')->getCell('B11')->getValue());
            $this->assertSame(1, (int) $book->getSheetByName('Thống kê')->getCell('H2')->getValue());
            $this->assertSame('Ảnh #'.$evidenceId, $alertSheet->getCell('M5')->getValue());
            $this->assertSame($eventId, $alertSheet->getCell('N5')->getValue());
            $this->assertSame('dd/mm/yyyy hh:mm:ss', $alertSheet->getStyle('I5')->getNumberFormat()->getFormatCode());
            $this->assertSame('Đã xem', $book->getSheetByName('Rà soát')->getCell('E5')->getValue());
            $this->assertSame('teacher@example.test', $book->getSheetByName('Rà soát')->getCell('F5')->getValue());
            $this->assertSame('Chưa có trường kết luận', $book->getSheetByName('Rà soát')->getCell('H5')->getValue());
            $results = $book->getSheetByName('Kết quả');
            $this->assertSame('Chưa được phân tách', $results->getCell('F5')->getValue());
            $this->assertSame(9.5, (float) $results->getCell('H5')->getValue());
            $this->assertSame(2, $overview->getChartCount());
            $charts = $overview->getChartCollection()->getArrayCopy();
            $this->assertSame(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_DOUGHNUTCHART, $charts[0]->getPlotArea()->getPlotGroup()[0]->getPlotType());
            $this->assertSame("'Tổng quan'!\$P\$2:\$P\$3", $charts[0]->getPlotArea()->getPlotGroup()[0]->getPlotCategories()[0]->getDataSource());
            $this->assertSame("'Tổng quan'!\$P\$6:\$P\$8", $charts[1]->getPlotArea()->getPlotGroup()[0]->getPlotCategories()[0]->getDataSource());
            $serializedCells = '';
            foreach ($book->getAllSheets() as $sheet) foreach ($sheet->toArray(null, true, true, false) as $row) $serializedCells .= implode('|', array_map(static fn($v) => (string) $v, $row));
            $this->assertStringNotContainsString('#REF!', $serializedCells);
            $this->assertStringNotContainsString('#VALUE!', $serializedCells);
            $this->assertStringNotContainsString($privatePath, $serializedCells);
            $this->assertStringNotContainsString('/api/giang-vien/anh-man-hinh/', $serializedCells);
            $this->assertSame($countsBeforeExport, [DB::table('bai_nop')->count(), DB::table('diem')->count(), DB::table('canh_bao')->count(), DB::table('canh_bao_camera')->count(), DB::table('su_kien_bao_ve_bai_kiem_tra')->count(), DB::table('vi_pham_kiem_tra')->count(), DB::table('anh_man_hinh')->count()]);
        } finally {
            @unlink($tempFile);
        }

        $otherTeacher = $this->user('giang_vien', 'other-teacher@example.test');
        $this->actingAs($otherTeacher, 'sanctum')->getJson('/api/giang-vien/bao-cao-ky-thi?class_id='.$classId.'&exam_id='.$examId)->assertUnprocessable();
        $this->actingAs($otherTeacher, 'sanctum')->postJson('/api/giang-vien/bao-cao-ky-thi/xuat-excel', ['class_id' => $classId, 'exam_id' => $examId])->assertUnprocessable();
    }

    public function test_export_returns_excel_content_type_and_xlsx_signature(): void
    {
        $teacher = $this->user('giang_vien', 'export-teacher@example.test');
        $this->academicSetup();
        $classId = DB::table('lop_hoc')->insertGetId(['ma_lop' => 'REPORT02', 'ten_lop' => 'Lớp xuất', 'giang_vien_id' => $teacher->id, 'created_at' => now(), 'updated_at' => now()]);
        $examId = DB::table('bai_tap')->insertGetId(['lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacher->id, 'tieu_de' => 'Báo cáo', 'thoi_gian_bat_dau' => now(), 'thoi_gian_ket_thuc' => now()->addHour(), 'han_nop' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
        $response = $this->actingAs($teacher, 'sanctum')->post('/api/giang-vien/bao-cao-ky-thi/xuat-excel', ['class_id' => $classId, 'exam_id' => $examId]);
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $this->assertGreaterThan(1000, strlen($content));
        $tempFile = tempnam(sys_get_temp_dir(), 'exam-report-');
        try {
            file_put_contents($tempFile, $content);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setIncludeCharts(true);
            $book = $reader->load($tempFile);
            $this->assertSame(['Tổng quan', 'Sinh viên', 'Kết quả', 'Cảnh báo', 'Rà soát', 'Thống kê'], $book->getSheetNames());
            $this->assertSame('Chưa có dữ liệu', $book->getSheetByName('Cảnh báo')->getCell('A5')->getValue());
            $this->assertSame(1, $book->getSheetByName('Tổng quan')->getChartCount());
            $this->assertSame('Chưa có dữ liệu cảnh báo', $book->getSheetByName('Tổng quan')->getCell('H20')->getValue());
        } finally {
            @unlink($tempFile);
        }
    }

    public function test_all_alert_sources_join_their_real_student_key_and_export_as_distinct_events(): void
    {
        $teacher = $this->user('giang_vien', 'sources-teacher@example.test');
        $studentUser = $this->user('sinh_vien', 'sources-student@example.test');
        $this->academicSetup();
        $profileId = DB::table('sinh_vien')->insertGetId(['nguoi_dung_id' => $studentUser->id, 'nganh_id' => 1, 'ma_sinh_vien' => 'SOURCE01', 'nam_trung_tuyen' => 2025, 'created_at' => now(), 'updated_at' => now()]);
        $classId = DB::table('lop_hoc')->insertGetId(['ma_lop' => 'SOURCE01', 'ten_lop' => 'Lớp nguồn', 'giang_vien_id' => $teacher->id, 'created_at' => now(), 'updated_at' => now()]);
        $examId = DB::table('bai_tap')->insertGetId(['lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacher->id, 'tieu_de' => 'Bài nguồn', 'thoi_gian_bat_dau' => now(), 'thoi_gian_ket_thuc' => now()->addHour(), 'han_nop' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
        $sessionId = DB::table('phien_giam_sat')->insertGetId(['lop_hoc_id' => $classId, 'bai_tap_id' => $examId, 'nguoi_tao_id' => $teacher->id, 'ten_phien' => 'Phiên nguồn', 'thoi_gian_bat_dau' => now(), 'trang_thai' => 'dang_dien_ra', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('thanh_vien_lop')->insert(['lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUser->id, 'trang_thai' => 'dang_hoc', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('canh_bao')->insert(['phien_id' => $sessionId, 'sinh_vien_id' => $studentUser->id, 'loai_canh_bao' => 'copy_paste', 'noi_dung' => 'Sự kiện giám sát phiên', 'thoi_gian' => now(), 'da_xu_ly' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('canh_bao_camera')->insert(['bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'event_id' => (string) Str::uuid(), 'loai_canh_bao' => 'phat_hien_dien_thoai', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('su_kien_bao_ve_bai_kiem_tra')->insert(['event_id' => (string) Str::uuid(), 'bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'phien_id' => $sessionId, 'loai_su_kien' => 'SCREENSHOT_SHORTCUT_DETECTED', 'thoi_gian_su_kien' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('vi_pham_kiem_tra')->insert(['bai_tap_id' => $examId, 'sinh_vien_id' => $profileId, 'event_id' => (string) Str::uuid(), 'lan_thu' => 1, 'loai_vi_pham' => 'roi_tab', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($teacher, 'sanctum')->getJson('/api/giang-vien/bao-cao-ky-thi?class_id='.$classId.'&exam_id='.$examId);
        $response->assertOk()->assertJsonPath('data.summary.alert_total', 4)->assertJsonPath('data.summary.students_with_alert', 1)
            ->assertJsonPath('data.students.0.user_id', $studentUser->id)->assertJsonPath('data.students.0.student_profile_id', $profileId)
            ->assertJsonPath('data.students.0.alert_count', 4)->assertJsonPath('data.students.0.camera_alert_count', 1)->assertJsonPath('data.students.0.exam_protection_alert_count', 2);
        $response->assertJsonPath('data.alerts.0.ma_sinh_vien', 'SOURCE01')
            ->assertJsonPath('data.alerts.0.session_name', 'Phiên nguồn')
            ->assertJsonPath('data.alerts.0.review_status', 'Đã xử lý; chưa có kết luận')
            ->assertJsonPath('data.alerts.0.confirmed_result', null);
    }

    private function user(string $roleName, string $email): NguoiDung
    {
        $roleId = DB::table('vai_tro')->where('ten_vai_tro', $roleName)->value('id')
            ?? DB::table('vai_tro')->insertGetId(['ten_vai_tro' => $roleName, 'created_at' => now(), 'updated_at' => now()]);
        return NguoiDung::query()->create(['ho_ten' => $email, 'email' => $email, 'password' => 'password', 'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong']);
    }

    private function academicSetup(): void
    {
        DB::table('khoa')->insert(['ma_khoa' => 'REPORT', 'ten_khoa' => 'Khoa báo cáo', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('nganh')->insert(['khoa_id' => 1, 'ma_nganh' => 'REPORT', 'ten_nganh' => 'Ngành báo cáo', 'created_at' => now(), 'updated_at' => now()]);
    }
}
