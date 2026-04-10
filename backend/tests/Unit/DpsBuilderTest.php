<?php

namespace Tests\Unit;

use App\Services\Nfse\DpsBuilder;
use PHPUnit\Framework\TestCase;

class DpsBuilderTest extends TestCase
{
    private function makeBuilder(): DpsBuilder
    {
        return new DpsBuilder();
    }

    private function sampleData(): array
    {
        return [
            'id_dps' => '355030821234567800019000001000000000000001',
            'ambiente' => 2,
            'data_emissao' => '2026-04-10T14:30:00-03:00',
            'serie' => '00001',
            'numero_dps' => '1',
            'emitente' => [
                'cnpj' => '12345678000190',
                'razao_social' => 'Tech Solutions Ltda',
                'nome_fantasia' => 'Tech Solutions',
                'inscricao_municipal' => '12345',
                'regime_tributario' => 1,
                'reg_esp_trib' => 0,
                'endereco' => [
                    'codigo_ibge' => '3550308',
                    'cep' => '01001000',
                    'logradouro' => 'Rua Exemplo',
                    'numero' => '100',
                    'complemento' => null,
                    'bairro' => 'Centro',
                ],
                'telefone' => '1199999999',
                'email' => 'contato@tech.com',
            ],
            'tomador' => [
                'tipo_documento' => '2',
                'documento' => '98765432000190',
                'razao_social' => 'Cliente ABC Ltda',
                'inscricao_municipal' => null,
                'endereco' => [
                    'codigo_ibge' => '3550308',
                    'cep' => '04567000',
                    'logradouro' => 'Av Principal',
                    'numero' => '500',
                    'complemento' => null,
                    'bairro' => 'Jardins',
                ],
                'telefone' => null,
                'email' => 'contato@cliente.com',
            ],
            'servico' => [
                'codigo_lc116' => '01.01',
                'codigo_nbs' => '1.0101',
                'descricao' => 'Consultoria em desenvolvimento de sistemas',
            ],
            'valores' => [
                'valor_servico' => 5000.00,
                'valor_deducoes' => 0,
                'valor_desconto' => 0,
                'aliquota_iss' => 0.0500,
                'valor_iss' => 250.00,
                'iss_retido' => false,
                'valor_ir' => 0,
                'valor_csll' => 0,
                'valor_cofins' => 0,
                'valor_pis' => 0,
                'valor_inss' => 0,
            ],
        ];
    }

    public function test_builds_valid_xml_with_required_fields(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('DPS', $xml);
        $this->assertStringContainsString('infDPS', $xml);
    }

    public function test_xml_contains_correct_namespace(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('http://www.sped.fazenda.gov.br/nfse', $xml);
    }

    public function test_id_dps_has_42_positions(): void
    {
        $data = $this->sampleData();
        $this->assertSame(42, strlen($data['id_dps']));

        $builder = $this->makeBuilder();
        $xml = $builder->build($data);

        $this->assertStringContainsString($data['id_dps'], $xml);
    }

    public function test_xml_contains_emitente_data(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('12345678000190', $xml);
        $this->assertStringContainsString('Tech Solutions Ltda', $xml);
    }

    public function test_xml_contains_tomador_data(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('98765432000190', $xml);
        $this->assertStringContainsString('Cliente ABC Ltda', $xml);
    }

    public function test_xml_contains_service_and_values(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('01.01', $xml);
        $this->assertStringContainsString('5000.00', $xml);
        $this->assertStringContainsString('250.00', $xml);
    }

    public function test_xml_has_correct_version_attribute(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('versao="1.00.02"', $xml);
    }

    public function test_xml_uses_utf8_encoding(): void
    {
        $builder = $this->makeBuilder();
        $xml = $builder->build($this->sampleData());

        $this->assertStringContainsString('encoding="UTF-8"', $xml);
    }

    public function test_tomador_cpf_uses_cpf_element(): void
    {
        $data = $this->sampleData();
        $data['tomador']['tipo_documento'] = '1';
        $data['tomador']['documento'] = '12345678901';

        $builder = $this->makeBuilder();
        $xml = $builder->build($data);

        $this->assertStringContainsString('<CPF>12345678901</CPF>', $xml);
        $this->assertStringNotContainsString('<CNPJ>12345678901</CNPJ>', $xml);
    }

    public function test_substitution_reference_included_when_provided(): void
    {
        $data = $this->sampleData();
        $data['chave_substituida'] = 'NFSe35503082123456780001900000100000000000000001';

        $builder = $this->makeBuilder();
        $xml = $builder->build($data);

        $this->assertStringContainsString('infNFSeSub', $xml);
        $this->assertStringContainsString('chSubstda', $xml);
    }
}
