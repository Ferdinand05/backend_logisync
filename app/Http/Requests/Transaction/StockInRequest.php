<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'reference_no' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'SKU wajib diisi.',
            'location_id.required' => 'Lokasi wajib dipilih.',
            'qty.required' => 'Jumlah wajib diisi.',
            'qty.min' => 'Jumlah minimal 1.',
            'reference_no.required' => 'Nomor referensi wajib diisi.',
        ];
    }
}
