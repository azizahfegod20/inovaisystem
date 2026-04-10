<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TaxCalculationTest extends TestCase
{
    private function calculateTax(array $params): array
    {
        $valorServico = (float) $params['valor_servico'];
        $valorDeducoes = (float) ($params['valor_deducoes'] ?? 0);
        $valorDesconto = (float) ($params['valor_desconto'] ?? 0);
        $aliquotaIss = (float) $params['aliquota_iss'];

        $baseCalculo = $valorServico - $valorDeducoes - $valorDesconto;
        $valorIss = round($baseCalculo * $aliquotaIss, 2);
        $valorLiquido = $valorServico - $valorDesconto;

        $valorIr = (float) ($params['valor_ir'] ?? 0);
        $valorCsll = (float) ($params['valor_csll'] ?? 0);
        $valorCofins = (float) ($params['valor_cofins'] ?? 0);
        $valorPis = (float) ($params['valor_pis'] ?? 0);
        $valorInss = (float) ($params['valor_inss'] ?? 0);

        $totalRetencoes = $valorIr + $valorCsll + $valorCofins + $valorPis + $valorInss;

        if ($params['iss_retido'] ?? false) {
            $totalRetencoes += $valorIss;
        }

        return [
            'base_calculo' => $baseCalculo,
            'valor_iss' => $valorIss,
            'valor_liquido' => $valorLiquido,
            'total_retencoes' => $totalRetencoes,
            'valor_receber' => $valorLiquido - $totalRetencoes,
        ];
    }

    public function test_basic_iss_calculation(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 5000.00,
            'aliquota_iss' => 0.0500,
            'iss_retido' => false,
        ]);

        $this->assertEquals(5000.00, $result['base_calculo']);
        $this->assertEquals(250.00, $result['valor_iss']);
        $this->assertEquals(5000.00, $result['valor_liquido']);
    }

    public function test_iss_with_deductions(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 10000.00,
            'valor_deducoes' => 2000.00,
            'aliquota_iss' => 0.0500,
            'iss_retido' => false,
        ]);

        $this->assertEquals(8000.00, $result['base_calculo']);
        $this->assertEquals(400.00, $result['valor_iss']);
    }

    public function test_iss_with_discount(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 10000.00,
            'valor_desconto' => 500.00,
            'aliquota_iss' => 0.0300,
            'iss_retido' => false,
        ]);

        $this->assertEquals(9500.00, $result['base_calculo']);
        $this->assertEquals(285.00, $result['valor_iss']);
        $this->assertEquals(9500.00, $result['valor_liquido']);
    }

    public function test_iss_retained_by_taker(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 5000.00,
            'aliquota_iss' => 0.0500,
            'iss_retido' => true,
        ]);

        $this->assertEquals(250.00, $result['valor_iss']);
        $this->assertEquals(250.00, $result['total_retencoes']);
        $this->assertEquals(4750.00, $result['valor_receber']);
    }

    public function test_federal_retentions(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 10000.00,
            'aliquota_iss' => 0.0500,
            'iss_retido' => false,
            'valor_ir' => 150.00,
            'valor_csll' => 100.00,
            'valor_cofins' => 300.00,
            'valor_pis' => 65.00,
            'valor_inss' => 1100.00,
        ]);

        $this->assertEquals(500.00, $result['valor_iss']);
        $this->assertEquals(1715.00, $result['total_retencoes']);
        $this->assertEquals(8285.00, $result['valor_receber']);
    }

    public function test_all_retentions_combined(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 20000.00,
            'aliquota_iss' => 0.0500,
            'iss_retido' => true,
            'valor_ir' => 300.00,
            'valor_csll' => 200.00,
            'valor_cofins' => 600.00,
            'valor_pis' => 130.00,
            'valor_inss' => 2200.00,
        ]);

        $expectedIss = 1000.00;
        $expectedRetencoes = $expectedIss + 300 + 200 + 600 + 130 + 2200;
        $this->assertEquals($expectedRetencoes, $result['total_retencoes']);
        $this->assertEquals(20000.00 - $expectedRetencoes, $result['valor_receber']);
    }

    public function test_minimum_aliquota(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 1000.00,
            'aliquota_iss' => 0.0200,
            'iss_retido' => false,
        ]);

        $this->assertEquals(20.00, $result['valor_iss']);
    }

    public function test_zero_service_value(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 0,
            'aliquota_iss' => 0.0500,
            'iss_retido' => false,
        ]);

        $this->assertEquals(0.00, $result['valor_iss']);
        $this->assertEquals(0.00, $result['valor_liquido']);
    }

    public function test_rounding_precision(): void
    {
        $result = $this->calculateTax([
            'valor_servico' => 333.33,
            'aliquota_iss' => 0.0500,
            'iss_retido' => false,
        ]);

        $this->assertEquals(16.67, $result['valor_iss']);
    }
}
