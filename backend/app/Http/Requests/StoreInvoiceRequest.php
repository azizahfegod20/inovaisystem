<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'valor_servico' => ['required', 'numeric', 'min:0.01'],
            'valor_deducoes' => ['sometimes', 'numeric', 'min:0'],
            'valor_desconto' => ['sometimes', 'numeric', 'min:0'],
            'descricao_servico' => ['required', 'string', 'min:1', 'max:2000'],
            'aliquota_iss' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'iss_retido' => ['sometimes', 'boolean'],
            'valor_ir' => ['sometimes', 'numeric', 'min:0'],
            'valor_csll' => ['sometimes', 'numeric', 'min:0'],
            'valor_cofins' => ['sometimes', 'numeric', 'min:0'],
            'valor_pis' => ['sometimes', 'numeric', 'min:0'],
            'valor_inss' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
