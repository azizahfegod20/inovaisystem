<?php

namespace Tests\Unit;

use App\Services\Nfse\XsdValidator;
use PHPUnit\Framework\TestCase;

class XsdValidatorTest extends TestCase
{
    private function xsdPath(): string
    {
        return __DIR__ . '/../../resources/xsd/nfse_v1.00.02.xsd';
    }

    private function validXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00.02">'
            . '<infDPS Id="355030821234567800019000001000000000000001">'
            . '<ide><cLocEmi>3550308</cLocEmi><dhEmi>2026-04-10T14:30:00-03:00</dhEmi>'
            . '<serie>00001</serie><nDPS>1</nDPS><tpAmb>2</tpAmb></ide>'
            . '<emit><CNPJ>12345678000190</CNPJ><xNome>Test Company</xNome>'
            . '<regTrib>1</regTrib>'
            . '<end><endNac><cMun>3550308</cMun><CEP>01001000</CEP></endNac></end></emit>'
            . '<toma><CNPJ>98765432000190</CNPJ><xNome>Customer</xNome></toma>'
            . '<serv><cServ><cTribNac>01.01</cTribNac><xDescServ>Service description</xDescServ></cServ></serv>'
            . '<valores><vServPrest><vServ>5000.00</vServ></vServPrest>'
            . '<trib><totTrib/><ISS><aliq>0.0500</aliq><vISS>250.00</vISS></ISS></trib></valores>'
            . '</infDPS></DPS>';
    }

    public function test_valid_xml_passes_validation(): void
    {
        $validator = new XsdValidator($this->xsdPath());
        $result = $validator->validate($this->validXml());

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function test_xml_missing_required_field_fails(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00.02">'
            . '<infDPS Id="355030821234567800019000001000000000000001">'
            . '</infDPS></DPS>';

        $validator = new XsdValidator($this->xsdPath());
        $result = $validator->validate($xml);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    public function test_xml_with_invalid_cnpj_pattern_fails(): void
    {
        $xml = str_replace(
            '<CNPJ>12345678000190</CNPJ>',
            '<CNPJ>123</CNPJ>',
            $this->validXml()
        );

        $validator = new XsdValidator($this->xsdPath());
        $result = $validator->validate($xml);

        $this->assertFalse($result->isValid());
    }

    public function test_xml_with_invalid_ambiente_fails(): void
    {
        $xml = str_replace(
            '<tpAmb>2</tpAmb>',
            '<tpAmb>9</tpAmb>',
            $this->validXml()
        );

        $validator = new XsdValidator($this->xsdPath());
        $result = $validator->validate($xml);

        $this->assertFalse($result->isValid());
    }

    public function test_errors_are_human_readable(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00.02"><broken/></DPS>';

        $validator = new XsdValidator($this->xsdPath());
        $result = $validator->validate($xml);

        $this->assertFalse($result->isValid());
        foreach ($result->getErrors() as $error) {
            $this->assertIsString($error);
            $this->assertNotEmpty($error);
        }
    }
}
