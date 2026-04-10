<?php

namespace Tests\Unit;

use App\Services\Nfse\XmlSigner;
use PHPUnit\Framework\TestCase;

class XmlSignerTest extends TestCase
{
    private string $testCertPath;
    private string $testKeyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generateTestCertificate();
    }

    protected function tearDown(): void
    {
        if (isset($this->testCertPath) && file_exists($this->testCertPath)) {
            unlink($this->testCertPath);
        }
        if (isset($this->testKeyPath) && file_exists($this->testKeyPath)) {
            unlink($this->testKeyPath);
        }

        parent::tearDown();
    }

    private function generateTestCertificate(): void
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = openssl_pkey_new($config);

        $csr = openssl_csr_new([
            'commonName' => 'TEST CERT:12345678000190',
            'organizationName' => 'Test Company',
            'countryName' => 'BR',
        ], $privateKey);

        $cert = openssl_csr_sign($csr, null, $privateKey, 365);

        $this->testCertPath = tempnam(sys_get_temp_dir(), 'cert_');
        $this->testKeyPath = tempnam(sys_get_temp_dir(), 'key_');

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($privateKey, $keyPem);

        file_put_contents($this->testCertPath, $certPem);
        file_put_contents($this->testKeyPath, $keyPem);
    }

    private function sampleXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00.02">'
            . '<infDPS Id="355030821234567800019000001000000000000001">'
            . '<ide><cLocEmi>3550308</cLocEmi><dhEmi>2026-04-10T14:30:00-03:00</dhEmi>'
            . '<serie>00001</serie><nDPS>1</nDPS><tpAmb>2</tpAmb></ide>'
            . '<emit><CNPJ>12345678000190</CNPJ><xNome>Test</xNome>'
            . '<regTrib>1</regTrib><end><endNac><cMun>3550308</cMun><CEP>01001000</CEP></endNac></end></emit>'
            . '<toma><CNPJ>98765432000190</CNPJ><xNome>Toma</xNome></toma>'
            . '<serv><cServ><cTribNac>01.01</cTribNac><xDescServ>Test</xDescServ></cServ></serv>'
            . '<valores><vServPrest><vServ>5000.00</vServ></vServPrest>'
            . '<trib><totTrib/><ISS><aliq>0.0500</aliq><vISS>250.00</vISS></ISS></trib></valores>'
            . '</infDPS></DPS>';
    }

    public function test_signs_xml_with_xmldsig(): void
    {
        $signer = new XmlSigner();
        $certPem = file_get_contents($this->testCertPath);
        $keyPem = file_get_contents($this->testKeyPath);

        $signedXml = $signer->sign($this->sampleXml(), $certPem, $keyPem);

        $this->assertStringContainsString('Signature', $signedXml);
        $this->assertStringContainsString('SignedInfo', $signedXml);
        $this->assertStringContainsString('SignatureValue', $signedXml);
        $this->assertStringContainsString('X509Certificate', $signedXml);
    }

    public function test_signature_uses_rsa_sha256(): void
    {
        $signer = new XmlSigner();
        $certPem = file_get_contents($this->testCertPath);
        $keyPem = file_get_contents($this->testKeyPath);

        $signedXml = $signer->sign($this->sampleXml(), $certPem, $keyPem);

        $this->assertStringContainsString('rsa-sha256', strtolower($signedXml));
    }

    public function test_signature_references_infdps_by_id(): void
    {
        $signer = new XmlSigner();
        $certPem = file_get_contents($this->testCertPath);
        $keyPem = file_get_contents($this->testKeyPath);

        $signedXml = $signer->sign($this->sampleXml(), $certPem, $keyPem);

        $this->assertStringContainsString('#355030821234567800019000001000000000000001', $signedXml);
    }

    public function test_signature_uses_c14n(): void
    {
        $signer = new XmlSigner();
        $certPem = file_get_contents($this->testCertPath);
        $keyPem = file_get_contents($this->testKeyPath);

        $signedXml = $signer->sign($this->sampleXml(), $certPem, $keyPem);

        $this->assertStringContainsString('c14n', strtolower($signedXml));
    }

    public function test_signed_xml_is_valid_xml(): void
    {
        $signer = new XmlSigner();
        $certPem = file_get_contents($this->testCertPath);
        $keyPem = file_get_contents($this->testKeyPath);

        $signedXml = $signer->sign($this->sampleXml(), $certPem, $keyPem);

        $doc = new \DOMDocument();
        $result = $doc->loadXML($signedXml);

        $this->assertTrue($result);
    }
}
