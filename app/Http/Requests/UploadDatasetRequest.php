<?php

namespace App\Http\Requests;

use App\Models\Dataset;
use Illuminate\Foundation\Http\FormRequest;

class UploadDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('komoditi')) {
            $kom = strtoupper(trim((string) $this->input('komoditi')));
            $this->merge(['komoditi' => $kom]);
        }
    }

    public function rules(): array
    {
        return [
            'komoditi' => ['required', 'string', 'in:OBA,OK,SK,OT'],
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
            if ($this->hasFile('file') && $this->has(['komoditi', 'periode_tahun'])) {
                $file = $this->file('file');
                $namaFile = $file?->getClientOriginalName();
                $komoditiInput = $this->input('komoditi');

                if ($namaFile) {
                    // Cegah pengunggahan berkas dengan nama identik yang sudah berstatus Final
                    $existsSameFile = Dataset::where(function ($q) use ($komoditiInput) {
                            if (in_array($komoditiInput, ['OBA', 'OT'])) {
                                $q->whereIn('komoditi', ['OBA', 'OT']);
                            } else {
                                $q->where('komoditi', $komoditiInput);
                            }
                        })
                        ->where('periode_tahun', $this->input('periode_tahun'))
                        ->where('nama_file_asli', $namaFile)
                        ->where('status', 'Final')
                        ->exists();

                    if ($existsSameFile) {
                        $validator->errors()->add(
                            'file',
                            "Berkas dengan nama '{$namaFile}' sudah pernah diunggah dan berstatus Final. Upload ulang berkas yang sama ditolak untuk menghindari duplikasi data."
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'komoditi.required' => 'Komoditi wajib dipilih.',
            'komoditi.in' => 'Pilihan komoditi tidak valid (hanya Obat Bahan Alam [OBA], Obat Kuasi [OK], atau Suplemen Kesehatan [SK]).',
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
