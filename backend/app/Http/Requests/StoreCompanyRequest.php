<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id ?? $this->route('company');

        return [
            'cnpj' => [
                'required',
                'string',
                'regex:/^[0-9]{11,14}$/',
                'unique:companies,cnpj' . ($companyId ? ",{$companyId}" : ''),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (strlen($value) === 14 && ! $this->validateCnpj($value)) {
                        $fail('CNPJ inválido.');
                    }
                    if (strlen($value) === 11 && ! $this->validateCpf($value)) {
                        $fail('CPF inválido.');
                    }
                },
            ],
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'inscricao_municipal' => ['nullable', 'string', 'max:20'],
            'inscricao_estadual' => ['nullable', 'string', 'max:20'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:100'],
            'codigo_ibge' => ['required', 'string', 'regex:/^[0-9]{7}$/'],
            'uf' => ['required', 'string', 'size:2'],
            'cep' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'telefone' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'regime_tributario' => ['sometimes', 'integer', 'in:1,2,3'],
            'reg_esp_trib' => ['sometimes', 'integer', 'between:0,6'],
            'dps_serie' => ['sometimes', 'string', 'regex:/^[0-9]{5}$/'],
            'ambiente' => ['sometimes', 'integer', 'in:1,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.regex' => 'O CNPJ/CPF deve conter 11 ou 14 dígitos numéricos.',
            'codigo_ibge.regex' => 'O código IBGE deve conter 7 dígitos.',
            'cep.regex' => 'O CEP deve conter 8 dígitos numéricos.',
            'dps_serie.regex' => 'A série DPS deve conter 5 dígitos.',
        ];
    }

    private function validateCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        return (int) $cnpj[13] === $digit2;
    }

    private function validateCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        return (int) $cpf[10] === $digit2;
    }
}
