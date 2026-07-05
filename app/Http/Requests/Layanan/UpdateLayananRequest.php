<?php

namespace App\Http\Requests\Layanan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_layanan' => 'sometimes|required|string|max:255',
            'gambar'       => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'status'       => 'sometimes|required|in:aktif,nonaktif',
            'deskripsi'    => 'nullable|string',
            'durasi'       => 'sometimes|required|integer|min:1',
        ];
    }
}
