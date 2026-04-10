<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'O motivo do cancelamento é obrigatório.',
            'motivo.min' => 'Motivo deve ter pelo menos 15 caracteres.',
        ];
    }
}
