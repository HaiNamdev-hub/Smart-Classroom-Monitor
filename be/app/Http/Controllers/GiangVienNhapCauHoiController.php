<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetFactory;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use Smalot\PdfParser\Parser as PdfParser;

class GiangVienNhapCauHoiController extends Controller
{
    /**
     * Kiểm tra giảng viên có quyền quản lý bài tập.
     */
    private function authorizeAssignment(
        Request $request,
        int $baiTapId
    ): void {
        abort_unless(
            $request->user(),
            401,
            'Bạn chưa đăng nhập.'
        );

        $exists = DB::table('bai_tap')
            ->where('id', $baiTapId)
            ->where(
                'nguoi_tao_id',
                $request->user()->id
            )
            ->exists();

        abort_unless(
            $exists,
            403,
            'Bạn không có quyền quản lý bài kiểm tra này.'
        );
    }

    /**
     * Tải file và xem trước câu hỏi.
     * Chưa lưu vào Database.
     */
    public function preview(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $this->authorizeAssignment(
            $request,
            $baiTapId
        );

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:docx,xlsx,pdf',
            ],
        ]);

        $file = $request->file('file');

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $path = $file->getRealPath();

        try {
            if ($extension === 'xlsx') {
                $questions = $this->readExcel($path);
            } elseif ($extension === 'docx') {
                 $text = $this->readDocxText($path);
                $questions = $this->parseText($text);
            } else {
                $text = (new PdfParser())
                    ->parseFile($path)
                    ->getText();

                $questions = $this->parseText($text);
            }
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                'Không đọc được tài liệu. '
                    . 'Kiểm tra định dạng hoặc nội dung file.',
            ], 422);
        }

        if (empty($questions)) {
            return response()->json([
                'status' => false,
                'message' =>
                'Không nhận diện được câu hỏi. '
                    . 'Hãy kiểm tra mẫu Word, Excel, PDF. '
                    . 'PDF scan ảnh chưa được hỗ trợ.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' =>
            'Đã đọc tài liệu thành công.',
            'data' => array_slice(
                $questions,
                0,
                200
            ),
        ]);
    }

    
/**
 * Đọc văn bản từ DOCX và giữ ranh giới đoạn văn.
 *
 * Không dùng getText() kết hợp getElements()
 * vì một số tài liệu bị lấy văn bản lặp.
 */
private function readDocxText(string $path): string
{
    $zip = new \ZipArchive();

    if ($zip->open($path) !== true) {
        throw new \RuntimeException(
            'Không thể mở file Word.'
        );
    }

    try {
        $xml = $zip->getFromName(
            'word/document.xml'
        );
    } finally {
        $zip->close();
    }

    if ($xml === false) {
        throw new \RuntimeException(
            'File Word không có document.xml.'
        );
    }

    $document = new \DOMDocument();

    $previous = libxml_use_internal_errors(true);

    try {
        if (!$document->loadXML(
            $xml,
            LIBXML_NONET
        )) {
            throw new \RuntimeException(
                'Cấu trúc XML của Word không hợp lệ.'
            );
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }

    $xpath = new \DOMXPath($document);

    $namespace =
        'http://schemas.openxmlformats.org/'
        . 'wordprocessingml/2006/main';

    $xpath->registerNamespace('w', $namespace);

    $paragraphs = $xpath->query(
        '//w:body//w:p'
    );

    $lines = [];

    foreach ($paragraphs as $paragraph) {
        $text = '';

        $nodes = $xpath->query(
            './/w:t | .//w:tab | .//w:br | .//w:cr',
            $paragraph
        );

        foreach ($nodes as $node) {
            if (
                $node->localName === 'br' ||
                $node->localName === 'cr'
            ) {
                $text .= "\n";

            } elseif (
                $node->localName === 'tab'
            ) {
                $text .= ' ';

            } else {
                $text .= $node->textContent;
            }
        }

        $text = trim($text);

        if ($text !== '') {
            $lines[] = $text;
        }
    }

    return implode("\n", $lines);
}


    /**
     * Đọc các đoạn văn bản trong Word.
     */

    private function wordLines(array $elements): array
    {
        $result = [];

        foreach ($elements as $element) {

            // Đoạn văn có nhiều TextRun:
            // đọc các phần tử con, không đọc trùng cả đoạn.
            if (method_exists($element, 'getElements')) {
                $children = $element->getElements();

                $parts = [];

                foreach ($children as $child) {
                    if (method_exists($child, 'getText')) {
                        $value = $child->getText();

                        if (is_string($value)) {
                            $parts[] = $value;
                        }
                    }
                }

                if (!empty($parts)) {
                    $result[] = implode('', $parts);
                } else {
                    $result = array_merge(
                        $result,
                        $this->wordLines($children)
                    );
                }

                continue;
            }

            // Đoạn Text bình thường.
            if (method_exists($element, 'getText')) {
                $value = $element->getText();

                if (is_string($value) && trim($value) !== '') {
                    $result[] = $value;
                }

                continue;
            }

            // Bảng trong Word.
            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $result = array_merge(
                            $result,
                            $this->wordLines(
                                $cell->getElements()
                            )
                        );
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Đọc file Excel.
     *
     * Các cột:
     * loai_cau_hoi, noi_dung,
     * A, B, C, D, E, F,
     * dap_an_dung, dap_an_mau, diem
     */
    private function readExcel(string $path): array
    {
        $book = SpreadsheetFactory::load($path);

        $rows = $book
            ->getActiveSheet()
            ->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return [];
        }

        $header = array_map(
            fn($value) =>
            mb_strtolower(
                trim((string) $value)
            ),
            array_shift($rows)
        );

        foreach (
            ['loai_cau_hoi', 'noi_dung', 'diem']
            as $requiredColumn
        ) {
            if (!in_array(
                $requiredColumn,
                $header,
                true
            )) {
                return [];
            }
        }

        $result = [];

        foreach ($rows as $row) {
            if (count($result) >= 200) {
                break;
            }

            $mapped = [];

            foreach ($header as $index => $column) {
                $mapped[$column] = trim(
                    (string) ($row[$index] ?? '')
                );
            }

            if ($mapped['noi_dung'] === '') {
                continue;
            }

            $type = mb_strtolower(
                $mapped['loai_cau_hoi']
            );

            $isEssay = in_array(
                $type,
                ['tu_luan', 'tự luận'],
                true
            );

            $options = [];
            $letters = [];

            if (!$isEssay) {
                foreach (
                    ['a', 'b', 'c', 'd', 'e', 'f']
                    as $letter
                ) {
                    if (
                        ($mapped[$letter] ?? '') !== ''
                    ) {
                        $letters[] = strtoupper($letter);
                        $options[] = $mapped[$letter];
                    }
                }
            }

            $correct = strtoupper(
                $mapped['dap_an_dung'] ?? ''
            );

            $correctIndex = $isEssay
                ? null
                : array_search(
                    $correct,
                    $letters,
                    true
                );

            $result[] = [
                'loai_cau_hoi' =>
                $isEssay
                    ? 'tu_luan'
                    : 'trac_nghiem',

                'noi_dung' =>
                $mapped['noi_dung'],

                'diem' =>
                (float) (
                    $mapped['diem'] ?: 1
                ),

                'dap_an' => $options,

                'dap_an_dung_index' =>
                $correctIndex === false
                    ? null
                    : $correctIndex,

                'dap_an_mau' =>
                $isEssay
                    ? ($mapped['dap_an_mau'] ?? '')
                    : '',
            ];
        }

        return $result;
    }

    /**
     * Tách câu hỏi từ Word/PDF theo mẫu:
     *
     * Câu 1: Nội dung...
     * A. ...
     * B. ...
     * Đáp án: A
     * Điểm: 1
     */
    
private function parseText(string $text): array
{
    // Chuẩn hóa xuống dòng và khoảng trắng.
    $text = str_replace(
        ["\r\n", "\r", "\xc2\xa0"],
        ["\n", "\n", " "],
        $text
    );

    // Giải mã các ký tự HTML nếu tài liệu chứa
    // &lt;, &gt;, &amp; hoặc mã HTML tương tự.
    for ($i = 0; $i < 2; $i++) {
        $decoded = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if ($decoded === $text) {
            break;
        }

        $text = $decoded;
    }

    // Nếu Word có các thẻ HTML được lưu thành
    // văn bản thì vẫn giữ nguyên nội dung câu hỏi.
    // Không strip_tags() để tránh mất các ví dụ <a>, <p>.

    // Khôi phục dấu xuống dòng trước "Câu 1", "Câu 2"...
    // Kể cả trường hợp: "Điểm: 1Câu 2:"
    $text = preg_replace(
        '/\s*(Câu\s*\d+\s*[:.\-])/iu',
        "\n$1",
        $text
    );

    // Khôi phục xuống dòng trước metadata.
    $text = preg_replace(
        '/\s*(Đáp\s*án\s*mẫu\s*:)/iu',
        "\n$1",
        $text
    );

    $text = preg_replace(
        '/\s*(Đáp\s*án\s*:)/iu',
        "\n$1",
        $text
    );

    $text = preg_replace(
        '/\s*(Loại\s*:)/iu',
        "\n$1",
        $text
    );

    $text = preg_replace(
        '/\s*(Điểm\s*:)/iu',
        "\n$1",
        $text
    );

    // Khôi phục các lựa chọn bị dính liền:
    // "...nào?A. ..." hoặc "...LanguageB. ..."
    $text = preg_replace(
        '/\s*([A-F])\s*([.)])\s*/u',
        "\n$1$2 ",
        $text
    );

    $text = trim($text);

    // Tách văn bản thành từng khối câu hỏi.
    preg_match_all(
        '/(?:^|\n)\s*Câu\s*\d+\s*[:.\-]\s*'
        . '(.*?)'
        . '(?=\n\s*Câu\s*\d+\s*[:.\-]|\z)/uis',
        $text,
        $matches
    );

    $questions = [];

    foreach (
        array_slice($matches[1], 0, 200)
        as $block
    ) {
        $lines = preg_split(
            '/\n/u',
            trim($block)
        );

        $statement = [];
        $options = [];

        $correctLetter = null;
        $points = 1;
        $isEssay = false;
        $sampleAnswer = '';

        $section = 'statement';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // =========================
            // LOẠI CÂU HỎI
            // =========================
            if (preg_match(
                '/^Loại\s*:\s*(.*)$/iu',
                $line,
                $match
            )) {
                $type = mb_strtolower(
                    trim($match[1])
                );

                $isEssay =
                    str_contains($type, 'tự luận') ||
                    str_contains($type, 'tu_luan');

                $section = 'metadata';
                continue;
            }

            // =========================
            // ĐIỂM
            // =========================
            if (preg_match(
                '/^Điểm\s*:\s*([0-9]+(?:[.,][0-9]+)?)/iu',
                $line,
                $match
            )) {
                $points = (float) str_replace(
                    ',',
                    '.',
                    $match[1]
                );

                $section = 'metadata';
                continue;
            }

            // =========================
            // ĐÁP ÁN MẪU TỰ LUẬN
            // =========================
            if (preg_match(
                '/^Đáp\s*án\s*mẫu\s*:\s*(.*)$/iu',
                $line,
                $match
            )) {
                $isEssay = true;
                $sampleAnswer = trim($match[1]);
                $section = 'sample';
                continue;
            }

            // =========================
            // ĐÁP ÁN ĐÚNG TRẮC NGHIỆM
            // =========================
            if (preg_match(
                '/^Đáp\s*án\s*:\s*([A-F])\s*$/iu',
                $line,
                $match
            )) {
                $correctLetter = strtoupper(
                    $match[1]
                );

                $section = 'metadata';
                continue;
            }

            // =========================
            // LỰA CHỌN A, B, C, D...
            // =========================
            if (preg_match(
                '/^([A-F])\s*[.)]\s*(.+)$/u',
                $line,
                $match
            )) {
                $letter = strtoupper($match[1]);

                $options[$letter] = trim(
                    $match[2]
                );

                $section = 'options';
                continue;
            }

            // =========================
            // NỘI DUNG CÂU HỎI
            // =========================
            if ($section === 'statement') {
                $statement[] = $line;
                continue;
            }

            // Đáp án mẫu nhiều dòng.
            if ($section === 'sample') {
                $sampleAnswer .=
                    ($sampleAnswer === '' ? '' : "\n")
                    . $line;

                continue;
            }

            // Một đáp án trắc nghiệm xuống nhiều dòng.
            if (
                $section === 'options' &&
                !empty($options)
            ) {
                $lastKey = array_key_last(
                    $options
                );

                $options[$lastKey] .= ' ' . $line;
            }
        }

        $content = trim(
            implode("\n", $statement)
        );

        if ($content === '') {
            continue;
        }

        $correctIndex = null;

        if ($correctLetter !== null) {
            $found = array_search(
                $correctLetter,
                array_keys($options),
                true
            );

            if ($found !== false) {
                $correctIndex = (int) $found;
            }
        }

        // Có lựa chọn thì là trắc nghiệm,
        // trừ khi được khai báo là tự luận.
        $type = $isEssay
            ? 'tu_luan'
            : (
                count($options) >= 2
                    ? 'trac_nghiem'
                    : 'tu_luan'
            );

        $questions[] = [
            'loai_cau_hoi' => $type,
            'noi_dung' => $content,
            'diem' => $points,

            'dap_an' =>
                $type === 'trac_nghiem'
                    ? array_values($options)
                    : [],

            'dap_an_dung_index' =>
                $type === 'trac_nghiem'
                    ? $correctIndex
                    : null,

            'dap_an_mau' =>
                $type === 'tu_luan'
                    ? trim($sampleAnswer)
                    : '',
        ];
    }

    return $questions;
}


    /**
     * Lưu danh sách câu hỏi đã xem trước.
     */
    public function save(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $this->authorizeAssignment(
            $request,
            $baiTapId
        );

        $data = Validator::make(
            $request->all(),
            [
                'questions' => [
                    'required',
                    'array',
                    'min:1',
                    'max:200',
                ],

                'questions.*.loai_cau_hoi' => [
                    'required',
                    'in:trac_nghiem,tu_luan',
                ],

                'questions.*.noi_dung' => [
                    'required',
                    'string',
                    'max:10000',
                ],

                'questions.*.diem' => [
                    'required',
                    'numeric',
                    'gt:0',
                    'lte:100',
                ],

                'questions.*.dap_an' => [
                    'array',
                ],

                'questions.*.dap_an.*' => [
                    'required',
                    'string',
                    'max:5000',
                ],

                'questions.*.dap_an_dung_index' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:5',
                ],

                'questions.*.dap_an_mau' => [
                    'nullable',
                    'string',
                    'max:20000',
                ],
            ]
        )->validate();

        // Kiểm tra toàn bộ câu trước khi lưu.
        foreach (
            $data['questions']
            as $index => $question
        ) {
            if (
                $question['loai_cau_hoi'] ===
                'trac_nghiem'
            ) {
                $options = $question['dap_an'] ?? [];

                $correctIndex =
                    $question['dap_an_dung_index']
                    ?? null;

                if (
                    count($options) < 2 ||
                    count($options) > 6 ||
                    $correctIndex === null ||
                    !array_key_exists(
                        $correctIndex,
                        $options
                    )
                ) {
                    throw ValidationException::withMessages([
                        "questions.$index.dap_an" =>
                        'Câu trắc nghiệm phải có '
                            . '2–6 đáp án và một đáp án đúng.',
                    ]);
                }
            } elseif (
                trim(
                    $question['dap_an_mau'] ?? ''
                ) === ''
            ) {
                throw ValidationException::withMessages([
                    "questions.$index.dap_an_mau" =>
                    'Câu tự luận phải có đáp án mẫu.',
                ]);
            }
        }

        // Lưu tất cả câu trong một transaction.
        DB::transaction(function () use (
            $baiTapId,
            $data
        ) {
            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            // Không sửa đề khi đã có bài nộp.
            abort_if(
                DB::table('bai_nop')
                    ->where(
                        'bai_tap_id',
                        $baiTapId
                    )
                    ->exists(),
                409,
                'Bài kiểm tra đã có sinh viên nộp. '
                    . 'Không thể thay đổi đề.'
            );

            $order = (int) DB::table('cau_hoi')
                ->where(
                    'bai_tap_id',
                    $baiTapId
                )
                ->max('thu_tu');

            foreach ($data['questions'] as $question) {
                $order++;

                $questionId = DB::table('cau_hoi')
                    ->insertGetId([
                        'bai_tap_id' => $baiTapId,

                        'noi_dung' =>
                        trim(
                            $question['noi_dung']
                        ),

                        'loai_cau_hoi' =>
                        $question['loai_cau_hoi'],

                        'diem' =>
                        $question['diem'],

                        'thu_tu' => $order,

                        'dap_an_mau' =>
                        $question['loai_cau_hoi'] === 'tu_luan'
                            ? trim(
                                $question['dap_an_mau']
                            )
                            : null,
                    ]);

                if (
                    $question['loai_cau_hoi'] ===
                    'trac_nghiem'
                ) {
                    foreach (
                        $question['dap_an']
                        as $index => $option
                    ) {
                        DB::table('dap_an')->insert([
                            'cau_hoi_id' =>
                            $questionId,

                            'noi_dung' =>
                            trim($option),

                            'la_dap_an_dung' =>
                            $index ===
                                $question['dap_an_dung_index'],

                            'thu_tu' =>
                            $index + 1,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'status' => true,
            'message' =>
            'Đã lưu danh sách câu hỏi thành công.',
            'data' => [
                'count' =>
                count($data['questions']),
            ],
        ], 201);
    }
}
