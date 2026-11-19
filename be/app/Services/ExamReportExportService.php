<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamReportExportService
{
    private const SHEETS = ['overview', 'students', 'results', 'alerts', 'review', 'statistics'];
    private const NAVY = '17365D';
    private const BLUE = '2F75B5';
    private const LIGHT_BLUE = 'D9EAF7';
    private const PALE = 'F3F7FB';
    private const TEXT = '243247';
    private const MUTED = '64748B';
    private const WHITE = 'FFFFFF';
    private const BORDER = 'D9E2F0';

    public function export(array $report, array $selected = []): StreamedResponse
    {
        $selected = $selected ?: self::SHEETS;
        $book = new Spreadsheet();
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->getColor()->setRGB(self::TEXT);
        $book->removeSheetByIndex(0);

        foreach (self::SHEETS as $key) {
            if (!in_array($key, $selected, true)) continue;
            $sheet = $book->createSheet();
            $sheet->setTitle(match ($key) {
                'overview' => 'Tổng quan', 'students' => 'Sinh viên', 'results' => 'Kết quả',
                'alerts' => 'Cảnh báo', 'review' => 'Rà soát', default => 'Thống kê',
            });

            $this->{'fill'.ucfirst($key)}($sheet, $report);
            if ($key !== 'overview') {
                $lastRow = max(4, $sheet->getHighestDataRow());
                $lastColumn = $sheet->getHighestDataColumn();
                $headerRow = $key === 'statistics' ? 1 : 4;
                $sheet->freezePane($key === 'statistics' ? 'A2' : 'A5');
                $sheet->setAutoFilter($key === 'statistics'
                    ? "G1:H{$lastRow}"
                    : "A{$headerRow}:{$lastColumn}{$lastRow}");
                $this->styleTable($sheet, $headerRow, $lastRow, $lastColumn, $key);
                $sheet->getPageSetup()->setRowsToRepeatAtTop($key === 'statistics' ? [1, 1] : [1, 4]);
            } else {
                $sheet->freezePane('A5');
            }

            $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight($key === 'overview' ? 1 : 0);
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $sheet->getPageSetup()->setHorizontalCentered(true);
            $sheet->getPageMargins()->setTop(0.45)->setBottom(0.45)->setLeft(0.3)->setRight(0.3);
            $sheet->getHeaderFooter()->setOddHeader('&C&"Arial,Bold"'.$this->safeText($sheet->getTitle()));
            $sheet->getHeaderFooter()->setOddFooter('&LSmart Classroom Monitor&RTrang &P / &N');
            $sheet->setShowGridlines(false);
            $sheet->getPageSetup()->setPrintArea($key === 'overview'
                ? 'A1:N43'
                : "A1:{$sheet->getHighestDataColumn()}".$sheet->getHighestDataRow());
            $this->setColumnWidths($sheet, $key);
        }

        $book->setActiveSheetIndex(0);
        $filename = 'bao-cao-ky-thi-'.$report['meta']['exam']->id.'-'.now()->format('Ymd-His').'.xlsx';
        return response()->streamDownload(static function () use ($book): void {
            $writer = new Xlsx($book);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function fillOverview($sheet, array $r): void
    {
        $meta = $r['meta'];
        $summary = $r['summary'];
        $teacherName = DB::table('nguoi_dung')->where('id', $meta['class']->giang_vien_id)->value('ho_ten');

        $sheet->mergeCells('A1:N2');
        $this->writeText($sheet, 'A1', 'BÁO CÁO TỔNG QUAN KỲ THI');
        $sheet->getStyle('A1:N2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 24, 'bold' => true, 'color' => ['rgb' => self::WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $sheet->mergeCells('A3:N3');
        $this->writeText($sheet, 'A3', 'SMART CLASSROOM MONITOR  |  BÁO CÁO KẾT QUẢ VÀ GIÁM SÁT');
        $sheet->getStyle('A3:N3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => self::WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '24476D']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 2],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $this->overviewSection($sheet, 4, 'THÔNG TIN KỲ THI');
        $this->overviewField($sheet, 'A5:B5', 'C5:F5', 'Lớp học', $meta['class']->ten_lop);
        $this->overviewField($sheet, 'G5:H5', 'I5:N5', 'Bài kiểm tra', $meta['exam']->tieu_de);
        $this->overviewField($sheet, 'A6:B6', 'C6:F6', 'Giảng viên', $teacherName);
        $this->overviewField($sheet, 'G6:H6', 'I6:J6', 'Bắt đầu', $this->date($meta['exam']->thoi_gian_bat_dau), true);
        $this->overviewField($sheet, 'K6:L6', 'M6:N6', 'Kết thúc', $this->date($meta['exam']->thoi_gian_ket_thuc), true);
        $this->overviewField($sheet, 'A7:B7', 'C7:J7', 'Khoảng thời gian lọc', $this->reportPeriod($meta));
        $this->overviewField($sheet, 'K7:L7', 'M7:N7', 'Xuất báo cáo', $this->date($meta['generated_at'] ?? null), true);
        $this->overviewField($sheet, 'A8:B8', 'C8:F8', 'Hình thức thi', $meta['exam_mode_label'] ?? 'Thi trực tuyến — Camera AI bắt buộc');
        $this->overviewField($sheet, 'G8:H8', 'I8:N8', 'Giám sát camera', ($meta['camera_monitoring'] ?? true) ? 'Được yêu cầu' : 'Không yêu cầu');
        $sheet->getRowDimension(5)->setRowHeight(28);
        $sheet->getRowDimension(6)->setRowHeight(28);
        $sheet->getRowDimension(7)->setRowHeight(28);
        $sheet->getRowDimension(8)->setRowHeight(28);

        $this->overviewSection($sheet, 9, 'CHỈ SỐ TỔNG QUAN');
        $cards = [
            ['A', 'D', 10, 'Tổng Sinh viên đủ điều kiện', $summary['eligible'] ?? null, self::BLUE],
            ['F', 'I', 10, 'Đã tham gia', $summary['participated'] ?? null, '3687A8'],
            ['K', 'N', 10, 'Chưa tham gia', $summary['unparticipated'] ?? null, '7F8FA6'],
            ['A', 'D', 14, 'Đã nộp', $summary['submitted'] ?? null, '548235'],
            ['F', 'I', 14, 'Điểm trung bình', $summary['mean_score'] ?? null, self::BLUE],
            ['K', 'N', 14, 'Tổng số sự kiện cảnh báo', $summary['alert_total'] ?? null, 'C65911'],
        ];
        foreach ($cards as [$start, $end, $row, $label, $value, $accent]) {
            $this->overviewKpiCard($sheet, $start, $end, $row, $label, $value, $accent);
        }

        $this->overviewSection($sheet, 19, 'BIỂU ĐỒ THAM GIA VÀ CẢNH BÁO');
        $this->writeOverviewChartData($sheet, $summary);
        $this->addParticipationChart($sheet, $summary);
        $alertTypeCount = count($summary['alerts_by_type'] ?? []);
        if ($alertTypeCount > 0) $this->addAlertChart($sheet, 6, 5 + $alertTypeCount);
        else $this->overviewNoAlerts($sheet);
        $sheet->getRowDimension(20)->setRowHeight(8);
        for ($row = 21; $row <= 34; $row++) $sheet->getRowDimension($row)->setRowHeight(19);
        $sheet->getRowDimension(35)->setRowHeight(8);

        $this->overviewSection($sheet, 36, 'THỐNG KÊ CHI TIẾT');
        $details = [
            ['A37:D37', 'Đang làm / chưa nộp', 'E37:G37', $summary['in_progress'] ?? null],
            ['H37:K37', 'Đã chấm', 'L37:N37', $summary['graded'] ?? null],
            ['A38:D38', 'Chưa chấm', 'E38:G38', $summary['ungraded'] ?? null],
            ['H38:K38', 'Sinh viên có cảnh báo', 'L38:N38', $summary['students_with_alert'] ?? null],
            ['A39:D39', 'Số loại cảnh báo', 'E39:G39', count($summary['alerts_by_type'] ?? [])],
            ['H39:K39', 'Điểm trung bình', 'L39:N39', $summary['mean_score'] ?? null],
            ['A40:D40', 'Thời gian tạo báo cáo', 'E40:G40', $this->date($meta['generated_at'] ?? null), true],
            ['H40:N40', 'Ghi chú', null, 'Cảnh báo tự động là tín hiệu cần xem xét, không phải kết luận gian lận.', false],
        ];
        foreach ($details as $detail) {
            [$labelRange, $label, $valueRange, $value, $isDate] = array_pad($detail, 5, false);
            $this->overviewDetail($sheet, $labelRange, $valueRange, $label, $value, $isDate ?? false);
        }
        $sheet->getRowDimension(40)->setRowHeight(32);
        $sheet->getStyle('H40:N40')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getPageSetup()->setRowsToRepeatAtTop([1, 3]);

        // Helper values live outside the print area and remain hidden from the dashboard view.
        $sheet->getColumnDimension('P')->setVisible(false);
        $sheet->getColumnDimension('Q')->setVisible(false);
        $sheet->getColumnDimension('O')->setWidth(2);
    }

    private function overviewSection($sheet, int $row, string $title): void
    {
        $sheet->mergeCells("A{$row}:N{$row}");
        $this->writeText($sheet, "A{$row}", $title);
        $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::NAVY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LIGHT_BLUE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(21);
    }

    private function overviewField($sheet, string $labelRange, string $valueRange, string $label, $value, bool $isDate = false): void
    {
        $sheet->mergeCells($labelRange);
        $sheet->mergeCells($valueRange);
        $labelCell = explode(':', $labelRange)[0];
        $valueCell = explode(':', $valueRange)[0];
        $this->writeText($sheet, $labelCell, $label);
        if ($value === null || $value === '') $this->writeText($sheet, $valueCell, 'Chưa có dữ liệu');
        elseif (is_numeric($value) && !is_string($value)) $sheet->setCellValue($valueCell, $value);
        else $this->writeText($sheet, $valueCell, (string) $value);
        $sheet->getStyle($labelRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::NAVY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PALE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle($valueRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::TEXT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
        ]);
        if ($isDate && $value !== null) $sheet->getStyle($valueCell)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm:ss');
    }

    private function overviewKpiCard($sheet, string $start, string $end, int $row, string $label, $value, string $accent): void
    {
        $labelRange = "{$start}{$row}:{$end}{$row}";
        $valueRange = "{$start}".($row + 1).":{$end}".($row + 2);
        $sheet->mergeCells($labelRange);
        $sheet->mergeCells($valueRange);
        $this->writeText($sheet, "{$start}{$row}", $label);
        if ($value === null) $this->writeText($sheet, "{$start}".($row + 1), '—');
        else $sheet->setCellValue("{$start}".($row + 1), $value);
        $sheet->getStyle("{$start}{$row}:{$end}".($row + 2))->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
        ]);
        $sheet->getStyle($labelRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => self::MUTED]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PALE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle($valueRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 22, 'color' => ['rgb' => $accent]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::WHITE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        if ($label === 'Điểm trung bình') $sheet->getStyle($valueRange)->getNumberFormat()->setFormatCode('0.00');
    }

    private function overviewDetail($sheet, string $labelRange, ?string $valueRange, string $label, $value, bool $isDate = false): void
    {
        if ($valueRange === null) {
            $sheet->mergeCells($labelRange);
            $this->writeText($sheet, explode(':', $labelRange)[0], $label.' '.$value);
            $sheet->getStyle($labelRange)->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '7C4A03']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true, 'indent' => 1],
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
            ]);
            return;
        }
        if ($value === null && $label === 'Điểm trung bình') $value = '—';
        $this->overviewField($sheet, $labelRange, $valueRange, $label, $value, $isDate);
    }

    private function writeOverviewChartData($sheet, array $summary): void
    {
        $this->writeRow($sheet, 2, [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 'Đã tham gia', (int) ($summary['participated'] ?? 0)]);
        $this->writeRow($sheet, 3, [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 'Chưa tham gia', (int) ($summary['unparticipated'] ?? 0)]);
        $this->writeText($sheet, 'P5', 'Loại cảnh báo');
        $this->writeText($sheet, 'Q5', 'Số sự kiện');
        foreach (array_values($summary['alerts_by_type'] ?? []) as $index => $line) {
            $this->writeText($sheet, 'P'.($index + 6), $line['label']);
            $sheet->setCellValue('Q'.($index + 6), (int) $line['count']);
        }
    }

    private function overviewNoAlerts($sheet): void
    {
        $sheet->mergeCells('H20:N35');
        $this->writeText($sheet, 'H20', 'Chưa có dữ liệu cảnh báo');
        $sheet->getStyle('H20:N35')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => ['rgb' => self::MUTED]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PALE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
        ]);
    }

    private function addParticipationChart($sheet, array $summary): void
    {
        $title = $sheet->getTitle();
        $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$title}'!\$P\$2:\$P\$3", null, 2);
        $values = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$title}'!\$Q\$2:\$Q\$3", null, 2);
        $series = new DataSeries(DataSeries::TYPE_DOUGHNUTCHART, null, [0], [], [$categories], [$values]);
        $chart = new Chart('participation-ratio', new Title('Tình trạng tham gia'), new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$series]));
        $chart->setTopLeftPosition('A20')->setBottomRightPosition('G35')->setPlotVisibleOnly(false);
        $sheet->addChart($chart);
    }

    private function addAlertChart($sheet, int $firstRow, int $lastRow): void
    {
        if ($lastRow <= $firstRow) return;
        $title = $sheet->getTitle();
        $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$title}'!\$P\$6:\$P\${$lastRow}", null, $lastRow - $firstRow + 1);
        $values = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$title}'!\$Q\$6:\$Q\${$lastRow}", null, $lastRow - $firstRow + 1);
        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], [], [$categories], [$values], DataSeries::DIRECTION_BAR);
        $chart = new Chart('alert-type-counts', new Title('Số sự kiện theo loại cảnh báo'), new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$series]));
        $chart->setTopLeftPosition('H20')->setBottomRightPosition('N35')->setPlotVisibleOnly(false);
        $sheet->addChart($chart);
    }

    private function fillStudents($sheet, array $r): void
    {
        $this->tableTitle($sheet, 'DANH SÁCH SINH VIÊN', 'Tình trạng tham gia, nộp bài, điểm và cảnh báo', 'M');
        $headers = ['MSSV', 'Họ tên', 'Email', 'Tham gia', 'Bắt đầu lúc', 'Trạng thái nộp', 'Thời gian nộp', 'Trạng thái chấm', 'Điểm tổng', 'Tổng cảnh báo', 'Cảnh báo Camera AI', 'Cảnh báo bảo vệ bài thi', 'Trạng thái rà soát'];
        $this->writeRow($sheet, 4, $headers);
        foreach ($r['students'] as $i => $s) $this->writeRow($sheet, $i + 5, [$s['student_code'], $s['name'], $s['email'], $s['participation'], $this->date($s['started_at']), $s['submission_status'], $this->date($s['submitted_at']), $s['grading_status'], $s['score'] === null ? null : (float) $s['score'], $s['alert_count'], $s['camera_alert_count'], $s['exam_protection_alert_count'], $s['alert_review_summary']]);
        if (!$r['students']) $this->writeText($sheet, 'A5', 'Chưa có dữ liệu');
        $this->dateFormat($sheet, 'E', count($r['students']) + 4);
        $this->dateFormat($sheet, 'G', count($r['students']) + 4);
        $this->numberFormat($sheet, 'I', count($r['students']) + 4, '0.00');
    }

    private function fillResults($sheet, array $r): void
    {
        $this->tableTitle($sheet, 'KẾT QUẢ VÀ ĐIỂM', 'Nguồn báo cáo hiện cung cấp điểm tổng; điểm trắc nghiệm và tự luận chưa được tách riêng trong dữ liệu này.', 'J');
        $this->writeRow($sheet, 4, ['MSSV', 'Họ tên', 'Mã bài nộp', 'Trạng thái nộp', 'Thời gian nộp', 'Điểm trắc nghiệm*', 'Điểm tự luận*', 'Tổng điểm', 'Nhận xét', 'Chấm lúc']);
        foreach ($r['students'] as $i => $s) $this->writeRow($sheet, $i + 5, [$s['student_code'], $s['name'], $s['submission_id'], $s['submission_status'], $this->date($s['submitted_at']), 'Chưa được phân tách', 'Chưa được phân tách', $s['score'] === null ? null : (float) $s['score'], $s['comment'], $this->date($s['graded_at'])]);
        if (!$r['students']) $this->writeText($sheet, 'A5', 'Chưa có dữ liệu');
        $this->dateFormat($sheet, 'E', count($r['students']) + 4);
        $this->dateFormat($sheet, 'J', count($r['students']) + 4);
        $this->numberFormat($sheet, 'H', count($r['students']) + 4, '0.00');
    }

    private function fillAlerts($sheet, array $r): void
    {
        $this->tableTitle($sheet, 'CHI TIẾT CẢNH BÁO', 'Một dòng cho mỗi sự kiện duy nhất; tham chiếu ảnh không chứa URL hoặc đường dẫn lưu trữ.', 'N');
        $this->writeRow($sheet, 4, ['STT', 'MSSV', 'Họ tên Sinh viên', 'Lớp học', 'Bài kiểm tra', 'Phiên giám sát', 'Loại cảnh báo', 'Nguồn cảnh báo', 'Thời gian xảy ra', 'Mô tả cảnh báo', 'Trạng thái đã xem/xử lý', 'Thời gian xem', 'Tham chiếu bằng chứng', 'Mã sự kiện']);
        if (!$r['alerts']) {
            $this->writeText($sheet, 'A5', 'Chưa có dữ liệu');
            return;
        }
        foreach ($r['alerts'] as $i => $a) $this->writeRow($sheet, $i + 5, [$i + 1, $a['ma_sinh_vien'], $a['ho_ten'], $r['meta']['class']->ten_lop, $r['meta']['exam']->tieu_de, $a['session_name'] ?? null, $a['type_label'], $a['source_label'], $this->date($a['happened_at']), $a['description'], $a['review_status'], $this->date($a['reviewed_at'] ?? null), $a['evidence_reference'] ?? null, $a['event_id'] ?? null]);
        $this->dateFormat($sheet, 'I', count($r['alerts']) + 4);
        $this->dateFormat($sheet, 'L', count($r['alerts']) + 4);
    }

    private function fillReview($sheet, array $r): void
    {
        $this->tableTitle($sheet, 'RÀ SOÁT SỰ KIỆN', 'Trạng thái đã xem/đã xử lý không phải kết luận xác minh vi phạm.', 'H');
        $this->writeRow($sheet, 4, ['MSSV', 'Họ tên', 'Loại cảnh báo', 'Thời gian cảnh báo', 'Trạng thái xem/xử lý', 'Người xem', 'Thời gian xem', 'Kết quả xác minh']);
        if (!$r['alerts']) {
            $this->writeText($sheet, 'A5', 'Chưa có dữ liệu');
            return;
        }
        foreach ($r['alerts'] as $i => $a) $this->writeRow($sheet, $i + 5, [$a['ma_sinh_vien'], $a['ho_ten'], $a['type_label'], $this->date($a['happened_at']), $a['review_action'] ?? 'Không có trạng thái xem xét', $a['reviewer_name'] ?? null, $this->date($a['reviewed_at'] ?? null), 'Chưa có trường kết luận']);
        $this->dateFormat($sheet, 'D', count($r['alerts']) + 4);
        $this->dateFormat($sheet, 'G', count($r['alerts']) + 4);
    }

    private function fillStatistics($sheet, array $r): void
    {
        $this->writeRow($sheet, 1, ['Chỉ số kỳ thi', 'Giá trị', null, 'Phân bố điểm tổng', 'Số bài', null, 'Loại cảnh báo', 'Số sự kiện']);
        $this->styleHeader($sheet, 1, 'A', 'H');
        $summaryLabels = [
            'eligible' => 'Sinh viên đủ điều kiện', 'participated' => 'Đã tham gia', 'unparticipated' => 'Chưa tham gia',
            'in_progress' => 'Đang làm / chưa nộp', 'submitted' => 'Đã nộp', 'unsubmitted' => 'Chưa nộp',
            'graded' => 'Đã chấm', 'ungraded' => 'Chưa chấm', 'mean_score' => 'Điểm trung bình',
            'alert_total' => 'Tổng sự kiện cảnh báo', 'students_with_alert' => 'Sinh viên có cảnh báo',
        ];
        $row = 2;
        foreach ($summaryLabels as $key => $label) $this->writeRow($sheet, $row++, [$label, $r['summary'][$key] ?? null]);
        $scoreCounts = collect($r['students'])->filter(fn($student) => $student['score'] !== null)->groupBy(fn($student) => number_format((float) $student['score'], 2, '.', ''))->map->count()->sortKeys();
        if ($scoreCounts->isEmpty()) $this->writeRow($sheet, 2, [null, null, null, 'Chưa có dữ liệu', null]);
        else foreach ($scoreCounts as $score => $count) $this->writeRow($sheet, 2 + array_search($score, $scoreCounts->keys()->all(), true), [null, null, null, (float) $score, $count]);
        $alertCounts = $r['summary']['alerts_by_type'] ?? [];
        if (!$alertCounts) $this->writeRow($sheet, 2, [null, null, null, null, null, null, 'Chưa có dữ liệu', null]);
        else foreach ($alertCounts as $i => $line) $this->writeRow($sheet, $i + 2, [null, null, null, null, null, null, $line['label'], (int) $line['count']]);
    }

    private function tableTitle($sheet, string $title, string $subtitle, string $lastColumn): void
    {
        $sheet->mergeCells("A1:{$lastColumn}2");
        $this->writeText($sheet, 'A1', $title);
        $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => self::WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->mergeCells("A3:{$lastColumn}3");
        $this->writeText($sheet, 'A3', $subtitle);
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => self::MUTED]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(30);
    }

    private function styleTable($sheet, int $headerRow, int $lastRow, string $lastColumn, string $key): void
    {
        $this->styleHeader($sheet, $headerRow, 'A', $lastColumn);
        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(match ($key) {
                'alerts' => 54, 'results', 'review' => 40, 'students' => 32, default => 25,
            });
            $this->styleBody($sheet, $row, $row, 'A', $lastColumn);
        }
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB(self::BORDER);
    }

    private function styleHeader($sheet, int $row, string $firstColumn, string $lastColumn): void
    {
        $sheet->getStyle("{$firstColumn}{$row}:{$lastColumn}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BLUE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(38);
    }

    private function styleBody($sheet, int $firstRow, int $lastRow, string $firstColumn, string $lastColumn): void
    {
        for ($row = $firstRow; $row <= $lastRow; $row++) {
            if (($row - $firstRow) % 2 === 1) {
                $sheet->getStyle("{$firstColumn}{$row}:{$lastColumn}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PALE);
            }
        }
        $sheet->getStyle("{$firstColumn}{$firstRow}:{$lastColumn}{$lastRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    private function setColumnWidths($sheet, string $key): void
    {
        $widths = match ($key) {
            'overview' => ['A' => 12, 'B' => 12, 'C' => 12, 'D' => 12, 'E' => 3, 'F' => 12, 'G' => 12, 'H' => 12, 'I' => 12, 'J' => 3, 'K' => 12, 'L' => 12, 'M' => 12, 'N' => 12],
            'students' => ['A' => 15, 'B' => 26, 'C' => 30, 'D' => 16, 'E' => 21, 'F' => 24, 'G' => 21, 'H' => 18, 'I' => 14, 'J' => 15, 'K' => 18, 'L' => 22, 'M' => 30],
            'results' => ['A' => 15, 'B' => 25, 'C' => 15, 'D' => 24, 'E' => 21, 'F' => 24, 'G' => 22, 'H' => 14, 'I' => 38, 'J' => 21],
            'alerts' => ['A' => 8, 'B' => 15, 'C' => 24, 'D' => 20, 'E' => 28, 'F' => 24, 'G' => 29, 'H' => 24, 'I' => 21, 'J' => 48, 'K' => 31, 'L' => 21, 'M' => 23, 'N' => 38],
            'review' => ['A' => 15, 'B' => 26, 'C' => 30, 'D' => 21, 'E' => 32, 'F' => 25, 'G' => 21, 'H' => 31],
            default => ['A' => 31, 'B' => 17, 'C' => 4, 'D' => 26, 'E' => 16, 'F' => 4, 'G' => 34, 'H' => 17],
        };
        foreach ($widths as $column => $width) $sheet->getColumnDimension($column)->setWidth($width);
    }

    private function writeRow($sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            if (is_string($value)) {
                $this->writeText($sheet, "{$column}{$row}", $value);
            } else {
                $sheet->setCellValue("{$column}{$row}", $value);
            }
        }
    }

    private function writeText($sheet, string $cell, ?string $value): void
    {
        if ($value === null) {
            $sheet->setCellValue($cell, null);
            return;
        }
        $sheet->getCell($cell)->setValueExplicit($this->safeText($value), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }

    private function safeText(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+@\-]/u', $value) ? "'".$value : $value;
    }

    private function date($value): ?float
    {
        return $value ? ExcelDate::PHPToExcel(Carbon::parse($value)->timezone(config('app.timezone'))->toDateTime()) : null;
    }

    private function dateFormat($sheet, string $column, int $lastRow, int $firstRow = 5): void
    {
        if ($lastRow >= $firstRow) $sheet->getStyle("{$column}{$firstRow}:{$column}{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm:ss');
    }

    private function numberFormat($sheet, string $column, int $lastRow, string $format, int $firstRow = 5): void
    {
        if ($lastRow >= $firstRow) $sheet->getStyle("{$column}{$firstRow}:{$column}{$lastRow}")->getNumberFormat()->setFormatCode($format);
    }

    private function reportPeriod(array $meta): string
    {
        $from = $meta['filters']['from'] ?? $meta['exam']->thoi_gian_bat_dau ?? null;
        $to = $meta['filters']['to'] ?? $meta['exam']->thoi_gian_ket_thuc ?? null;
        if (!$from && !$to) return 'Chưa có dữ liệu';
        return trim(($from ? $this->localizedDate($from) : 'Không giới hạn'). ' — '.($to ? $this->localizedDate($to) : 'Không giới hạn'));
    }

    private function localizedDate($value): string
    {
        return Carbon::parse($value)->timezone(config('app.timezone'))->format(str_contains((string) $value, ':') ? 'd/m/Y H:i:s' : 'd/m/Y');
    }
}

