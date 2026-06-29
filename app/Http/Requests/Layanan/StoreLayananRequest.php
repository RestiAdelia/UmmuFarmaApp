<?php

namespace App\Http\Requests\Layanan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_layanan' => 'required|string|max:255',
            'status'       => 'nullable|in:aktif,nonaktif',
            'deskripsi'    => 'nullable|string',
            'tarif'        => 'required|numeric|min:0',
            'durasi'       => 'required|integer|min:1',
        ];
    }
}
