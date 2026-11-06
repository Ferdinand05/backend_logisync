<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanAuditItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'gudang';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'actual_qty' => ['required', 'integer', 'min:0'],
        ];
    }
}
