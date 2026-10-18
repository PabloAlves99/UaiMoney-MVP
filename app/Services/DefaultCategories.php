<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class DefaultCategories
{
    private const DEFINITIONS = [
        'receita' => [
            'Trabalho' => ['Salário', 'Trabalho extra'],
            'Outras receitas' => ['Rendimentos', 'Reembolsos', 'Outras entradas'],
        ],
        'despesa' => [
            'Moradia' => ['Aluguel', 'Condomínio', 'Água', 'Energia elétrica', 'Internet residencial', 'Gás', 'Manutenção residencial', 'Utensílios domésticos'],
            'Alimentação' => ['Mercado', 'Restaurante', 'Delivery', 'Padaria', 'Lanches'],
            'Transporte' => ['Combustível', 'Transporte público', 'Aplicativo de transporte', 'Manutenção do veículo', 'Peças do veículo', 'Estacionamento', 'Equipamentos de proteção'],
            'Saúde' => ['Consulta', 'Exame', 'Medicamentos', 'Plano de saúde'],
            'Educação' => ['Cursos', 'Mensalidade escolar', 'Livros', 'Material escolar'],
            'Lazer' => ['Passeios', 'Viagens', 'Entretenimento'],
            'Assinaturas' => ['Filmes', 'Séries', 'Música', 'Jogos', 'Software', 'Clube de benefícios', 'Outras assinaturas'],
            'Pessoal' => ['Vestuário', 'Cuidados pessoais', 'Compras diversas'],
            'Financeiro' => ['Juros de cartão', 'Juros de empréstimo', 'Tarifas bancárias', 'Outros juros'],
            'Outras despesas' => ['Impostos', 'Taxas', 'Despesas diversas'],
        ],
    ];

    public static function add(PDO $pdo, int $user): void
    {
        $insertGroup = $pdo->prepare('INSERT OR IGNORE INTO grupos(usuario_id,nome,tipo) VALUES(?,?,?)');
        $findGroup = $pdo->prepare('SELECT id,ativo FROM grupos WHERE usuario_id=? AND nome=? AND tipo=?');
        $insertChild = $pdo->prepare('INSERT OR IGNORE INTO subgrupos(grupo_id,nome) VALUES(?,?)');
        foreach (self::DEFINITIONS as $type => $groups) {
            foreach ($groups as $name => $children) {
                $insertGroup->execute([$user, $name, $type]);
                $findGroup->execute([$user, $name, $type]);
                $group = $findGroup->fetch(PDO::FETCH_ASSOC);
                if (!(int) $group['ativo']) continue;
                foreach ($children as $child) $insertChild->execute([$group['id'], $child]);
            }
        }
    }
}
