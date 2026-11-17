<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveNganHangCauHoiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentication and teacher role are enforced by the route middleware.
    }

    public function rules(): array
    {
        return [
            'noi_dung' => ['required', 'string', 'max:10000'],
            'loai_cau_hoi' => ['required', 'in:trac_nghiem,tu_luan'],
            'chu_de' => ['nullable', 'string', 'max:120'],
            'do_kho' => ['required', 'in:de,trung_binh,kho'],
            'diem_tu_luan' => ['nullable', 'numeric', 'gt:0', 'lte:10'],
            'dap_an_mau' => ['nullable', 'string', 'max:20000'],
            'dap_an' => ['required_if:loai_cau_hoi,trac_nghiem', 'array', 'min:2', 'max:6'],
            'dap_an.*' => ['required', 'string', 'max:5000'],
            'dap_an_dung_index' => ['required_if:loai_cau_hoi,trac_nghiem', 'integer', 'min:0', 'max:5'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('loai_cau_hoi') === 'tu_luan') {
                if (!$this->filled('dap_an_mau')) {
                    $validator->errors()->add('dap_an_mau', 'Câu tự luận cần đáp án mẫu hoặc hướng dẫn chấm.');
                }
                if (!$this->filled('diem_tu_luan')) {
                    $validator->errors()->add('diem_tu_luan', 'Vui lòng nhập điểm mặc định cho câu tự luận.');
                } elseif (!self::hasTwoDecimals($this->input('diem_tu_luan'))) {
                    $validator->errors()->add('diem_tu_luan', 'Điểm chỉ được có tối đa 2 chữ số thập phân.');
                }
            }

            if ($this->input('loai_cau_hoi') === 'trac_nghiem') {
                $answers = $this->input('dap_an', []);
                $index = $this->input('dap_an_dung_index');
                if (!is_array($answers) || !array_is_list($answers) || !is_numeric($index) || !array_key_exists((int) $index, $answers)) {
                    $validator->errors()->add('dap_an_dung_index', 'Hãy chọn đúng một đáp án chính xác trong danh sách.');
                }
            }
        });
    }

    public static function hasTwoDecimals(mixed $value): bool
    {
        return is_numeric($value) && abs(((float) $value * 100) - round((float) $value * 100)) < 0.000001;
    }
}
