<?php

namespace App\Services\Nfse;

use DOMDocument;

class XsdValidator
{
    protected string $xsdPath;

    public function __construct(?string $xsdPath = null)
    {
        $this->xsdPath = $xsdPath ?? base_path(config('nfse.xsd_path', 'resources/xsd/nfse_v1.00.02.xsd'));
    }

    public function validate(string $xml): ValidationResult
    {
        $doc = new DOMDocument();

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $loaded = $doc->loadXML($xml);

        if (! $loaded) {
            $errors = $this->formatLibxmlErrors();

            return new ValidationResult(false, $errors ?: ['XML mal-formado.']);
        }

        $valid = $doc->schemaValidate($this->xsdPath);

        if (! $valid) {
            $errors = $this->formatLibxmlErrors();

            return new ValidationResult(false, $errors);
        }

        libxml_clear_errors();

        return new ValidationResult(true, []);
    }

    protected function formatLibxmlErrors(): array
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $message = trim($error->message);
            $line = $error->line;
            $level = match ($error->level) {
                LIBXML_ERR_WARNING => 'Aviso',
                LIBXML_ERR_ERROR => 'Erro',
                LIBXML_ERR_FATAL => 'Erro Fatal',
                default => 'Erro',
            };

            $errors[] = "[{$level}] Linha {$line}: {$message}";
        }

        libxml_clear_errors();

        return $errors;
    }
}

class ValidationResult
{
    public function __construct(
        protected bool $valid,
        protected array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
