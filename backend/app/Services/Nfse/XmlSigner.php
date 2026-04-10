<?php

namespace App\Services\Nfse;

use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;

class XmlSigner
{
    public function sign(string $xml, string $certPem, string $keyPem): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        if (! $doc->loadXML($xml)) {
            throw new RuntimeException('XML inválido para assinatura.');
        }

        $infDPS = $doc->getElementsByTagNameNS(
            'http://www.sped.fazenda.gov.br/nfse',
            'infDPS'
        )->item(0);

        if (! $infDPS) {
            throw new RuntimeException('Elemento infDPS não encontrado no XML.');
        }

        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $objDSig->addReference(
            $infDPS,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'Id', 'overwrite' => false]
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($keyPem, false, false);

        $objDSig->sign($objKey, $infDPS);

        $objDSig->add509Cert($certPem, true, false, [
            'issuerSerial' => false,
            'subjectName' => false,
        ]);

        return $doc->saveXML();
    }
}
