<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pfx_file' => ['required', 'file', 'max:10240'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'pfx_file.required' => 'O arquivo do certificado (.pfx) é obrigatório.',
            'pfx_file.file' => 'O certificado deve ser um arquivo válido.',
            'pfx_file.max' => 'O arquivo do certificado não pode exceder 10MB.',
            'password.required' => 'A senha do certificado é obrigatória.',
        ];
    }
}
