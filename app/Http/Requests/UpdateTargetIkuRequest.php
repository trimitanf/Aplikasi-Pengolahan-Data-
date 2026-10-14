<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTargetIkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'kelompok' => ['required', 'string', 'in:OBA,SK'],
            'target' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun target wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa bilangan bulat.',
            'kelompok.required' => 'Kelompok (OBA/SK) wajib dipilih.',
            'kelompok.in' => 'Kelompok hanya boleh OBA atau SK.',
            'target.required' => 'Nilai target wajib diisi.',
            'target.numeric' => 'Nilai target harus berupa angka persentase.',
            'target.min' => 'Nilai target minimal 0%.',
            'target.max' => 'Nilai target maksimal 100%.',
        ];
    }
}
