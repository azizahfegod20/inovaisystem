<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->get('current_company_id');
        $customerId = $this->route('customer')?->id ?? $this->route('customer');

        $uniqueRule = 'unique:customers,documento,';
        $uniqueRule .= $customerId ? "{$customerId},id,company_id,{$companyId}" : "NULL,id,company_id,{$companyId}";

        return [
            'tipo_documento' => ['required', 'string', 'in:1,2'],
            'documento' => ['required', 'string', 'regex:/^[0-9]{11,14}$/', $uniqueRule],
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'inscricao_municipal' => ['nullable', 'string', 'max:20'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:100'],
            'codigo_ibge' => ['required', 'string', 'regex:/^[0-9]{7}$/'],
            'uf' => ['required', 'string', 'size:2'],
            'cep' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.in' => 'Tipo de documento deve ser 1 (CPF) ou 2 (CNPJ).',
            'documento.regex' => 'Documento deve conter 11 (CPF) ou 14 (CNPJ) dígitos.',
            'codigo_ibge.regex' => 'Código IBGE deve conter 7 dígitos.',
            'cep.regex' => 'CEP deve conter 8 dígitos.',
        ];
    }
}
