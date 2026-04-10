<?php

namespace App\Services\Nfse;

use DOMDocument;

class DpsBuilder
{
    protected const NAMESPACE_URI = 'http://www.sped.fazenda.gov.br/nfse';
    protected const VERSION = '1.00.02';

    public function build(array $data): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;

        $dps = $dom->createElementNS(self::NAMESPACE_URI, 'DPS');
        $dps->setAttribute('versao', self::VERSION);
        $dom->appendChild($dps);

        $infDPS = $dom->createElementNS(self::NAMESPACE_URI, 'infDPS');
        $infDPS->setAttribute('Id', $data['id_dps']);
        $dps->appendChild($infDPS);

        $this->buildIde($dom, $infDPS, $data);
        $this->buildEmit($dom, $infDPS, $data['emitente']);
        $this->buildToma($dom, $infDPS, $data['tomador']);
        $this->buildServ($dom, $infDPS, $data['servico']);
        $this->buildValores($dom, $infDPS, $data['valores']);

        if (! empty($data['chave_substituida'])) {
            $this->buildSubstitution($dom, $infDPS, $data['chave_substituida']);
        }

        return $dom->saveXML();
    }

    protected function buildIde(DOMDocument $dom, \DOMElement $parent, array $data): void
    {
        $ide = $this->addElement($dom, $parent, 'ide');
        $this->addElement($dom, $ide, 'cLocEmi', $data['emitente']['endereco']['codigo_ibge']);
        $this->addElement($dom, $ide, 'dhEmi', $data['data_emissao']);
        $this->addElement($dom, $ide, 'serie', $data['serie']);
        $this->addElement($dom, $ide, 'nDPS', $data['numero_dps']);
        $this->addElement($dom, $ide, 'tpAmb', (string) $data['ambiente']);
    }

    protected function buildEmit(DOMDocument $dom, \DOMElement $parent, array $emit): void
    {
        $el = $this->addElement($dom, $parent, 'emit');
        $this->addElement($dom, $el, 'CNPJ', $emit['cnpj']);
        $this->addElement($dom, $el, 'xNome', $emit['razao_social']);

        if (! empty($emit['nome_fantasia'])) {
            $this->addElement($dom, $el, 'xFant', $emit['nome_fantasia']);
        }
        if (! empty($emit['inscricao_municipal'])) {
            $this->addElement($dom, $el, 'IM', $emit['inscricao_municipal']);
        }

        $this->addElement($dom, $el, 'regTrib', (string) $emit['regime_tributario']);

        if (! empty($emit['reg_esp_trib'])) {
            $this->addElement($dom, $el, 'regEspTrib', (string) $emit['reg_esp_trib']);
        }

        $end = $this->addElement($dom, $el, 'end');
        $endNac = $this->addElement($dom, $end, 'endNac');
        $this->addElement($dom, $endNac, 'cMun', $emit['endereco']['codigo_ibge']);
        $this->addElement($dom, $endNac, 'CEP', $emit['endereco']['cep']);

        if (! empty($emit['endereco']['logradouro'])) {
            $this->addElement($dom, $end, 'xLgr', $emit['endereco']['logradouro']);
        }
        if (! empty($emit['endereco']['numero'])) {
            $this->addElement($dom, $end, 'nro', $emit['endereco']['numero']);
        }
        if (! empty($emit['endereco']['complemento'])) {
            $this->addElement($dom, $end, 'xCpl', $emit['endereco']['complemento']);
        }
        if (! empty($emit['endereco']['bairro'])) {
            $this->addElement($dom, $end, 'xBairro', $emit['endereco']['bairro']);
        }

        if (! empty($emit['telefone'])) {
            $this->addElement($dom, $el, 'fone', $emit['telefone']);
        }
        if (! empty($emit['email'])) {
            $this->addElement($dom, $el, 'email', $emit['email']);
        }
    }

    protected function buildToma(DOMDocument $dom, \DOMElement $parent, array $toma): void
    {
        $el = $this->addElement($dom, $parent, 'toma');

        if ($toma['tipo_documento'] === '2') {
            $this->addElement($dom, $el, 'CNPJ', $toma['documento']);
        } else {
            $this->addElement($dom, $el, 'CPF', $toma['documento']);
        }

        $this->addElement($dom, $el, 'xNome', $toma['razao_social']);

        if (! empty($toma['inscricao_municipal'])) {
            $this->addElement($dom, $el, 'IM', $toma['inscricao_municipal']);
        }

        if (! empty($toma['endereco'])) {
            $end = $this->addElement($dom, $el, 'end');
            $endNac = $this->addElement($dom, $end, 'endNac');
            $this->addElement($dom, $endNac, 'cMun', $toma['endereco']['codigo_ibge']);
            $this->addElement($dom, $endNac, 'CEP', $toma['endereco']['cep']);

            if (! empty($toma['endereco']['logradouro'])) {
                $this->addElement($dom, $end, 'xLgr', $toma['endereco']['logradouro']);
            }
            if (! empty($toma['endereco']['numero'])) {
                $this->addElement($dom, $end, 'nro', $toma['endereco']['numero']);
            }
            if (! empty($toma['endereco']['complemento'])) {
                $this->addElement($dom, $end, 'xCpl', $toma['endereco']['complemento']);
            }
            if (! empty($toma['endereco']['bairro'])) {
                $this->addElement($dom, $end, 'xBairro', $toma['endereco']['bairro']);
            }
        }

        if (! empty($toma['telefone'])) {
            $this->addElement($dom, $el, 'fone', $toma['telefone']);
        }
        if (! empty($toma['email'])) {
            $this->addElement($dom, $el, 'email', $toma['email']);
        }
    }

    protected function buildServ(DOMDocument $dom, \DOMElement $parent, array $serv): void
    {
        $el = $this->addElement($dom, $parent, 'serv');
        $cServ = $this->addElement($dom, $el, 'cServ');
        $this->addElement($dom, $cServ, 'cTribNac', $serv['codigo_lc116']);

        if (! empty($serv['codigo_nbs'])) {
            $this->addElement($dom, $cServ, 'cNBS', $serv['codigo_nbs']);
        }

        $this->addElement($dom, $cServ, 'xDescServ', $serv['descricao']);
    }

    protected function buildValores(DOMDocument $dom, \DOMElement $parent, array $val): void
    {
        $valores = $this->addElement($dom, $parent, 'valores');

        $vServPrest = $this->addElement($dom, $valores, 'vServPrest');
        $this->addElement($dom, $vServPrest, 'vServ', number_format($val['valor_servico'], 2, '.', ''));

        if (($val['valor_deducoes'] ?? 0) > 0) {
            $this->addElement($dom, $vServPrest, 'vDeducao', number_format($val['valor_deducoes'], 2, '.', ''));
        }
        if (($val['valor_desconto'] ?? 0) > 0) {
            $this->addElement($dom, $vServPrest, 'vDescIncond', number_format($val['valor_desconto'], 2, '.', ''));
        }

        $trib = $this->addElement($dom, $valores, 'trib');
        $this->addElement($dom, $trib, 'totTrib');

        $iss = $this->addElement($dom, $trib, 'ISS');
        $this->addElement($dom, $iss, 'aliq', number_format($val['aliquota_iss'], 4, '.', ''));
        $this->addElement($dom, $iss, 'vISS', number_format($val['valor_iss'], 2, '.', ''));

        if ($val['iss_retido'] ?? false) {
            $this->addElement($dom, $iss, 'tpRetISS', '1');
        }

        if (($val['valor_ir'] ?? 0) > 0) {
            $this->addElement($dom, $trib, 'vRetIR', number_format($val['valor_ir'], 2, '.', ''));
        }
        if (($val['valor_csll'] ?? 0) > 0) {
            $this->addElement($dom, $trib, 'vRetCSLL', number_format($val['valor_csll'], 2, '.', ''));
        }
        if (($val['valor_cofins'] ?? 0) > 0) {
            $this->addElement($dom, $trib, 'vRetCOFINS', number_format($val['valor_cofins'], 2, '.', ''));
        }
        if (($val['valor_pis'] ?? 0) > 0) {
            $this->addElement($dom, $trib, 'vRetPIS', number_format($val['valor_pis'], 2, '.', ''));
        }
        if (($val['valor_inss'] ?? 0) > 0) {
            $this->addElement($dom, $trib, 'vRetINSS', number_format($val['valor_inss'], 2, '.', ''));
        }
    }

    protected function buildSubstitution(DOMDocument $dom, \DOMElement $parent, string $chaveSubstituida): void
    {
        $sub = $this->addElement($dom, $parent, 'infNFSeSub');
        $this->addElement($dom, $sub, 'chSubstda', $chaveSubstituida);
    }

    protected function addElement(DOMDocument $dom, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $element = $dom->createElementNS(self::NAMESPACE_URI, $name);

        if ($value !== null) {
            $element->appendChild($dom->createTextNode($value));
        }

        $parent->appendChild($element);

        return $element;
    }
}
