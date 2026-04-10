<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['01.01', 'Análise e desenvolvimento de sistemas'],
            ['01.02', 'Programação'],
            ['01.03', 'Processamento, armazenamento ou hospedagem de dados, textos, imagens, vídeos, páginas eletrônicas, aplicativos e sistemas de informação'],
            ['01.04', 'Elaboração de programas de computadores, inclusive de jogos eletrônicos'],
            ['01.05', 'Licenciamento ou cessão de direito de uso de programas de computação'],
            ['01.06', 'Assessoria e consultoria em informática'],
            ['01.07', 'Suporte técnico em informática, inclusive instalação, configuração e manutenção de programas de computação e bancos de dados'],
            ['01.08', 'Planejamento, confecção, manutenção e atualização de páginas eletrônicas'],
            ['02.01', 'Serviços de pesquisas e desenvolvimento de qualquer natureza'],
            ['03.02', 'Cessão de direito de uso de marcas e de sinais de propaganda'],
            ['03.03', 'Exploração de salões de festas, centro de convenções, escritórios virtuais, stands, quadras esportivas, estádios, ginásios, auditórios'],
            ['04.01', 'Medicina e biomedicina. Serviços de saúde, assistência médica e congêneres'],
            ['07.02', 'Execução, por administração, empreitada ou subempreitada, de obras de construção civil'],
            ['07.05', 'Reparação, conservação e reforma de edifícios, estradas, pontes, portos e congêneres'],
            ['08.02', 'Instrução, treinamento, orientação pedagógica e educacional, avaliação de conhecimentos'],
            ['09.02', 'Hospedagem de qualquer natureza em hotéis, apart-service condominiais, flat, apart-hotéis, hotéis residência'],
            ['10.05', 'Agenciamento, corretagem ou intermediação de bens móveis ou imóveis'],
            ['11.01', 'Guarda e estacionamento de veículos terrestres automotores, de aeronaves e de embarcações'],
            ['14.01', 'Lubrificação, limpeza, lustração, revisão, carga e recarga, conserto, restauração, blindagem, manutenção e conservação de máquinas, veículos'],
            ['17.01', 'Assessoria ou consultoria de qualquer natureza, não contida em outros itens desta lista'],
            ['17.02', 'Análise, exame, pesquisa, coleta, compilação e fornecimento de dados e informações de qualquer natureza'],
            ['17.06', 'Propaganda e publicidade, inclusive promoção de vendas, planejamento de campanhas ou sistemas de publicidade'],
            ['17.12', 'Administração em geral, inclusive de bens e negócios de terceiros'],
            ['17.19', 'Contabilidade, inclusive serviços técnicos e auxiliares'],
            ['17.20', 'Consultoria e assessoria econômica ou financeira'],
            ['25.01', 'Funerais, inclusive fornecimento de caixão, urna ou esquife'],
            ['25.02', 'Translado intramunicipal e cremação de corpos e partes de corpos cadavéricos'],
        ];

        foreach ($services as [$codigo, $descricao]) {
            DB::table('services')->updateOrInsert(
                ['codigo_lc116' => $codigo, 'company_id' => 0],
                [
                    'company_id' => 0,
                    'codigo_lc116' => $codigo,
                    'descricao' => $descricao,
                    'aliquota_iss' => 0.0500,
                    'is_favorite' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
