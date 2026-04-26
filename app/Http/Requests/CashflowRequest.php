<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CashflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.numeric' => 'Jumlah harus berupa angka.',
            'jumlah.min' => 'Jumlah tidak boleh kurang dari 0.',
        ];
    }
}
