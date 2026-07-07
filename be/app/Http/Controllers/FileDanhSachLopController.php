<?php

namespace App\Http\Controllers;

use App\Models\FileDanhSachLop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\Table;

class FileDanhSachLopController extends Controller
{
    public function index()
    {
        $data = FileDanhSachLop::with(['lop', 'nguoiTai'])
            ->orderByDesc('id')
            ->get()
            ->map(function ($file) {
                try {
                    $students = $this->readStudentsFromStoredFile($file);
                    $file->setAttribute('so_sv', count($students));
                } catch (\Throwable $e) {
                    $file->setAttribute('so_sv', 0);
                }

                return $file;
            });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lop_id' => 'required|exists:lop,id',
            'nguoi_tai_id' => 'required|exists:nguoi_dung,id',
            'file' => 'required|file|max:10240',
        ], [
            'lop_id.required' => 'Vui lòng chọn lớp.',
            'lop_id.exists' => 'Lớp không tồn tại.',

            'nguoi_tai_id.required' => 'Không xác định được người tải file.',
            'nguoi_tai_id.exists' => 'Người tải file không tồn tại.',

            'file.required' => 'Vui lòng chọn file danh sách lớp.',
            'file.mimes' => 'Chỉ chấp nhận file Excel hoặc Word.',
            'file.max' => 'File không được vượt quá 10MB.',
        ]);

        $fileUpload = $request->file('file');

        $extension = strtolower($fileUpload->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls', 'doc', 'docx'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ chấp nhận file Excel hoặc Word.',
                'errors' => [
                    'file' => ['Chỉ chấp nhận file Excel hoặc Word.']
                ]
            ], 422);
        }

        $path = $fileUpload->store('danh-sach-lop', 'public');

        $file = FileDanhSachLop::create([
            'lop_id' => $request->lop_id,
            'nguoi_tai_id' => $request->nguoi_tai_id,
            'ten_file' => $fileUpload->getClientOriginalName(),
            'duong_dan' => $path,
            'trang_thai' => 'cho_xu_ly',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tải file danh sách lớp thành công.',
            'data' => $file,
        ], 201);
    }

    public function show($id)
    {
        $file = FileDanhSachLop::with(['lop', 'nguoiTai'])->find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $file,
        ]);
    }

    public function download($id)
    {
        $file = FileDanhSachLop::find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        $fullPath = storage_path('app/public/' . $file->duong_dan);

        if (!file_exists($fullPath)) {
            return response()->json([
                'status' => false,
                'message' => 'File không tồn tại trên server.',
            ], 404);
        }

        return response()->download($fullPath, $file->ten_file);
    }

    public function replace(Request $request, $id)
    {
        $file = FileDanhSachLop::find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        $request->validate([
            'lop_id' => 'required|exists:lop,id',
            'nguoi_tai_id' => 'required|exists:nguoi_dung,id',
            'file' => 'required|file|max:10240',
        ], [
            'lop_id.required' => 'Vui lòng chọn lớp.',
            'lop_id.exists' => 'Lớp không tồn tại.',

            'nguoi_tai_id.required' => 'Không xác định được người tải file.',
            'nguoi_tai_id.exists' => 'Người tải file không tồn tại.',

            'file.required' => 'Vui lòng chọn file danh sách lớp.',
            'file.mimes' => 'Chỉ chấp nhận file Excel hoặc Word.',
            'file.max' => 'File không được vượt quá 10MB.',
        ]);

        $fileUpload = $request->file('file');

        $extension = strtolower($fileUpload->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls', 'doc', 'docx'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ chấp nhận file Excel hoặc Word.',
                'errors' => [
                    'file' => ['Chỉ chấp nhận file Excel hoặc Word.']
                ]
            ], 422);
        }

        $oldPath = $file->duong_dan;
        $newPath = $fileUpload->store('danh-sach-lop', 'public');

        $file->update([
            'lop_id' => $request->lop_id,
            'nguoi_tai_id' => $request->nguoi_tai_id,
            'ten_file' => $fileUpload->getClientOriginalName(),
            'duong_dan' => $newPath,
            'trang_thai' => 'cho_xu_ly',
        ]);

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tải lại file danh sách lớp thành công.',
            'data' => $file,
        ]);
    }

    public function update(Request $request, $id)
    {
        $file = FileDanhSachLop::find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        $validated = $request->validate([
            'lop_id' => 'sometimes|required|exists:lop,id',
            'nguoi_tai_id' => 'sometimes|required|exists:nguoi_dung,id',
            'trang_thai' => 'sometimes|required|in:cho_xu_ly,da_xu_ly,loi',
        ]);

        $file->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật file danh sách lớp thành công.',
            'data' => $file,
        ]);
    }

    public function destroy($id)
    {
        $file = FileDanhSachLop::find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        if (Storage::disk('public')->exists($file->duong_dan)) {
            Storage::disk('public')->delete($file->duong_dan);
        }

        $file->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa file danh sách lớp thành công.',
        ]);
    }

    public function docDuLieu($id)
    {
        $file = FileDanhSachLop::with('lop')->find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        try {
            $students = $this->readStudentsFromStoredFile($file);

            return response()->json([
                'status' => true,
                'message' => 'Đọc dữ liệu sinh viên thành công.',
                'data' => $students,
                'so_sv' => count($students),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không đọc được dữ liệu trong file. Nếu là file Word .doc, hãy đổi sang .docx.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function readStudentsFromStoredFile(FileDanhSachLop $file): array
    {
        $fullPath = storage_path('app/public/' . $file->duong_dan);

        if (!file_exists($fullPath)) {
            throw new \Exception('File không tồn tại trên server.');
        }

        $extension = strtolower(pathinfo($file->ten_file, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readStudentsFromExcel($fullPath, $file);
        }

        if (in_array($extension, ['docx', 'doc'])) {
            return $this->readStudentsFromWord($fullPath, $file);
        }

        return [];
    }

    private function readStudentsFromExcel(string $fullPath, FileDanhSachLop $file): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();

        $rawRows = $sheet->toArray(null, true, true, true);

        $students = [];
        $headerMap = [];
        $headerFound = false;

        foreach ($rawRows as $row) {
            $values = array_map(function ($value) {
                return trim((string) $value);
            }, $row);

            $nonEmptyValues = array_filter($values, fn($value) => $value !== '');

            if (count($nonEmptyValues) === 0) {
                continue;
            }

            if (!$headerFound && $this->looksLikeHeaderRow($values)) {
                $headerMap = $this->makeHeaderMap($values);
                $headerFound = true;
                continue;
            }

            if (!$headerFound) {
                $student = $this->makeStudentRow([
                    'ma_sinh_vien' => $values['A'] ?? null,
                    'ho_ten' => $values['B'] ?? null,
                ], $file);

                if ($student) {
                    $students[] = $student;
                }

                continue;
            }

            $mapped = [];

            foreach ($headerMap as $column => $fieldName) {
                $mapped[$fieldName] = $values[$column] ?? null;
            }

            $student = $this->makeStudentRow($mapped, $file);

            if ($student) {
                $students[] = $student;
            }
        }

        return $this->uniqueStudents($students);
    }

    private function readStudentsFromWord(string $fullPath, FileDanhSachLop $file): array
    {
        $phpWord = WordIOFactory::load($fullPath);

        $students = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table) {
                    $students = array_merge($students, $this->readStudentsFromWordTable($element, $file));
                    continue;
                }

                $text = trim($this->extractWordText($element));

                if ($text === '') {
                    continue;
                }

                if (preg_match('/^([A-Za-z0-9_.-]+)\s+(.+)$/u', $text, $matches)) {
                    $student = $this->makeStudentRow([
                        'ma_sinh_vien' => $matches[1],
                        'ho_ten' => $matches[2],
                    ], $file);

                    if ($student) {
                        $students[] = $student;
                    }
                }
            }
        }

        return $this->uniqueStudents($students);
    }

    private function readStudentsFromWordTable(Table $table, FileDanhSachLop $file): array
    {
        $students = [];
        $headerMap = [];
        $headerFound = false;

        foreach ($table->getRows() as $row) {
            $cells = [];

            foreach ($row->getCells() as $cell) {
                $cellText = '';

                foreach ($cell->getElements() as $element) {
                    $cellText .= ' ' . $this->extractWordText($element);
                }

                $cells[] = trim($cellText);
            }

            $nonEmptyCells = array_filter($cells, fn($value) => trim((string) $value) !== '');

            if (count($nonEmptyCells) === 0) {
                continue;
            }

            if (!$headerFound && $this->looksLikeHeaderRow($cells)) {
                $headerMap = $this->makeHeaderMapFromNumericArray($cells);
                $headerFound = true;
                continue;
            }

            if (!$headerFound) {
                $student = $this->makeStudentRow([
                    'ma_sinh_vien' => $cells[0] ?? null,
                    'ho_ten' => $cells[1] ?? null,
                ], $file);

                if ($student) {
                    $students[] = $student;
                }

                continue;
            }

            $mapped = [];

            foreach ($headerMap as $index => $fieldName) {
                $mapped[$fieldName] = $cells[$index] ?? null;
            }

            $student = $this->makeStudentRow($mapped, $file);

            if ($student) {
                $students[] = $student;
            }
        }

        return $students;
    }

    private function extractWordText($element): string
    {
        if (method_exists($element, 'getText')) {
            return (string) $element->getText();
        }

        if (method_exists($element, 'getElements')) {
            $text = '';

            foreach ($element->getElements() as $child) {
                $text .= ' ' . $this->extractWordText($child);
            }

            return trim($text);
        }

        return '';
    }

    private function looksLikeHeaderRow(array $values): bool
    {
        $joined = $this->normalizeText(implode(' ', $values));

        return str_contains($joined, 'ma sinh vien')
            || str_contains($joined, 'mssv')
            || str_contains($joined, 'ma sv')
            || str_contains($joined, 'ho ten')
            || str_contains($joined, 'ten sinh vien')
            || str_contains($joined, 'email');
    }

    private function makeHeaderMap(array $values): array
    {
        $map = [];

        foreach ($values as $column => $label) {
            $fieldName = $this->detectFieldName($label);

            if ($fieldName) {
                $map[$column] = $fieldName;
            }
        }

        return $map;
    }

    private function makeHeaderMapFromNumericArray(array $values): array
    {
        $map = [];

        foreach ($values as $index => $label) {
            $fieldName = $this->detectFieldName($label);

            if ($fieldName) {
                $map[$index] = $fieldName;
            }
        }

        return $map;
    }

    private function detectFieldName($label): ?string
    {
        $label = $this->normalizeText($label);

        if (
            str_contains($label, 'ma sinh vien')
            || str_contains($label, 'mssv')
            || str_contains($label, 'ma sv')
        ) {
            return 'ma_sinh_vien';
        }

        if (
            str_contains($label, 'ho ten')
            || str_contains($label, 'ten sinh vien')
            || $label === 'ten'
        ) {
            return 'ho_ten';
        }

        if (str_contains($label, 'email')) {
            return 'email';
        }

        if (
            str_contains($label, 'so dien thoai')
            || str_contains($label, 'sdt')
        ) {
            return 'so_dien_thoai';
        }

        if (str_contains($label, 'ngay sinh')) {
            return 'ngay_sinh';
        }

        if (str_contains($label, 'nam trung tuyen')) {
            return 'nam_trung_tuyen';
        }

        return null;
    }

    private function normalizeText($value): string
    {
        $value = trim((string) $value);
        $value = Str::ascii($value);
        $value = strtolower($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function makeStudentRow(array $data, FileDanhSachLop $file): ?array
    {
        $maSinhVien = trim((string) ($data['ma_sinh_vien'] ?? ''));
        $hoTen = trim((string) ($data['ho_ten'] ?? ''));

        if ($maSinhVien === '' || $hoTen === '') {
            return null;
        }

        $normalizedMa = $this->normalizeText($maSinhVien);
        $normalizedTen = $this->normalizeText($hoTen);

        if (
            str_contains($normalizedMa, 'ma sinh vien')
            || str_contains($normalizedMa, 'mssv')
            || str_contains($normalizedTen, 'ho ten')
            || str_contains($normalizedTen, 'ten sinh vien')
        ) {
            return null;
        }

        return [
            'ma_sinh_vien' => $maSinhVien,
            'ho_ten' => $hoTen,
            'email' => trim((string) ($data['email'] ?? '')),
            'so_dien_thoai' => trim((string) ($data['so_dien_thoai'] ?? '')),
            'ngay_sinh' => trim((string) ($data['ngay_sinh'] ?? '')),
            'nam_trung_tuyen' => trim((string) ($data['nam_trung_tuyen'] ?? '')),
            'lop_id' => $file->lop_id,
            'ten_lop' => $file->lop?->ten_lop ?? '',
            'trang_thai' => 'hoat_dong',
            'tong_diem_sv' => null,
            'xep_loai_sv' => null,
            'tong_diem_lop' => null,
            'xep_loai_lop' => null,
        ];
    }

    private function uniqueStudents(array $students): array
    {
        $result = [];

        foreach ($students as $student) {
            $key = $student['ma_sinh_vien'];
            $result[$key] = $student;
        }

        return array_values($result);
    }
}
