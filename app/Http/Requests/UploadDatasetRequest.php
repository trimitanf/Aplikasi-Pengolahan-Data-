<?php

namespace App\Http\Requests;

use App\Models\Dataset;
use Illuminate\Foundation\Http\FormRequest;

class UploadDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'komoditi' => ['required', 'string', 'in:OT,OK,SK'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'periode_bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'file' => [
                'required',
                'file',
                'max:25600', // 25 MB
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['xls', 'xlsx', 'html', 'htm'])) {
                        $fail('Format file harus berupa file export SIPT (.xls HTML atau .xlsx).');
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has(['komoditi', 'periode_tahun', 'periode_bulan'])) {
                $exists = Dataset::where('komoditi', $this->input('komoditi'))
                    ->where('periode_tahun', $this->input('periode_tahun'))
                    ->where('periode_bulan', $this->input('periode_bulan'))
                    ->where('status', 'Final')
                    ->exists();

                if ($exists) {
                    $bulanMap = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ];
                    $namaBulan = $bulanMap[(int)$this->input('periode_bulan')] ?? $this->input('periode_bulan');
                    $validator->errors()->add(
                        'komoditi',
                        "Dataset Final untuk komoditi {$this->input('komoditi')} periode {$namaBulan} {$this->input('periode_tahun')} sudah ada. Upload untuk periode yang sudah Final ditolak."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'komoditi.required' => 'Komoditi wajib dipilih.',
            'komoditi.in' => 'Pilihan komoditi tidak valid (hanya OT, OK, atau SK).',
            'periode_tahun.required' => 'Tahun periode wajib diisi.',
            'periode_tahun.integer' => 'Tahun harus berupa bilangan bulat.',
            'periode_bulan.required' => 'Bulan periode wajib dipilih.',
            'periode_bulan.between' => 'Bulan periode harus antara 1 sampai 12.',
            'file.required' => 'File ekspor SIPT wajib diunggah.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.max' => 'Ukuran file maksimal adalah 25 MB.',
        ];
    }
}
