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
            'gambar'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status'       => 'nullable|in:aktif,nonaktif',
            'deskripsi'    => 'nullable|string',
            'durasi'       => 'required|integer|min:1',
            'tarif'        => 'required|numeric|min:0',
        ];
    }
}
