<?php
declare(strict_types=1);

use App\Services\DefaultCategories;

return function (PDO $pdo): void {
    foreach ($pdo->query('SELECT id FROM usuarios')->fetchAll(PDO::FETCH_COLUMN) as $user) {
        DefaultCategories::add($pdo, (int) $user);
    }

    // Preserve the intermediate targets used by this historical migration.
    // The following migration separates these combined categories.
    $intermediate = $pdo->prepare('INSERT OR IGNORE INTO subgrupos(grupo_id,nome) SELECT id,? FROM grupos WHERE usuario_id=? AND tipo=? AND nome=? AND ativo=1');
    foreach ($pdo->query('SELECT id FROM usuarios')->fetchAll(PDO::FETCH_COLUMN) as $user) {
        foreach ([['Moradia', 'Aluguel e condomínio'], ['Alimentação', 'Restaurantes e delivery'], ['Transporte', 'Transporte público e aplicativos'], ['Lazer', 'Passeios e viagens'], ['Lazer', 'Assinaturas e entretenimento']] as [$group, $child]) {
            $intermediate->execute([$child, $user, 'despesa', $group]);
        }
    }

    // Only unambiguous legacy names are mapped. Custom and archived entries remain available.
    $mappings = [
        ['receita', 'Renda', 'Salário', 'Trabalho', 'Salário'],
        ['receita', 'Receitas', 'Salário', 'Trabalho', 'Salário'],
        ['receita', 'Receitas', 'Freelances', 'Trabalho', 'Trabalho extra'],
        ['receita', 'Renda', 'Outras receitas', 'Outras receitas', 'Outras entradas'],
        ['despesa', 'Moradia', 'Aluguel', 'Moradia', 'Aluguel e condomínio'],
        ['despesa', 'Alimentação', 'Supermercado', 'Alimentação', 'Mercado'],
        ['despesa', 'Alimentação', 'Restaurantes', 'Alimentação', 'Restaurantes e delivery'],
        ['despesa', 'Transporte', 'Transporte público', 'Transporte', 'Transporte público e aplicativos'],
        ['despesa', 'Moto', 'Gasolina', 'Transporte', 'Combustível'],
        ['despesa', 'Moto', 'Manutenção', 'Transporte', 'Manutenção do veículo'],
        ['despesa', 'Moto', 'Peças', 'Transporte', 'Manutenção do veículo'],
        ['despesa', 'Saúde', 'Remédio', 'Saúde', 'Medicamentos'],
        ['despesa', 'Cuidado pessoal', 'Corte de Cabelo', 'Pessoal', 'Cuidados pessoais'],
        ['despesa', 'Cuidado pessoal', 'Desodorante', 'Pessoal', 'Cuidados pessoais'],
        ['despesa', 'Cuidado pessoal', 'Produto de cabelo', 'Pessoal', 'Cuidados pessoais'],
        ['despesa', 'Lazer', 'Passeios', 'Lazer', 'Passeios e viagens'],
        ['despesa', 'Lazer', 'Assinaturas', 'Lazer', 'Assinaturas e entretenimento'],
    ];
    $find = $pdo->prepare('SELECT s.id,s.grupo_id FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? AND g.tipo=? AND g.nome=? AND s.nome=? AND g.ativo=1 AND s.ativo=1');
    $transactions = $pdo->prepare('SELECT * FROM transacoes WHERE usuario_id=? AND subgrupo_id=?');
    $history = $pdo->prepare('INSERT INTO historico_movimentacoes(usuario_id,transacao_id,acao,dados_anteriores) VALUES(?,?,?,?)');
    $move = $pdo->prepare('UPDATE transacoes SET subgrupo_id=?,versao=versao+1 WHERE usuario_id=? AND subgrupo_id=?');
    $recurrences = $pdo->prepare('UPDATE recorrencias SET subgrupo_id=? WHERE usuario_id=? AND subgrupo_id=?');
    $archiveChild = $pdo->prepare('UPDATE subgrupos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE id=?');
    $archiveGroup = $pdo->prepare('UPDATE grupos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND NOT EXISTS(SELECT 1 FROM subgrupos WHERE grupo_id=grupos.id AND ativo=1) AND NOT EXISTS(SELECT 1 FROM orcamentos WHERE grupo_id=grupos.id)');
    foreach ($pdo->query('SELECT id FROM usuarios')->fetchAll(PDO::FETCH_COLUMN) as $user) {
        foreach ($mappings as [$type, $oldGroup, $oldChild, $newGroup, $newChild]) {
            $find->execute([$user, $type, $oldGroup, $oldChild]);
            $source = $find->fetch(PDO::FETCH_ASSOC);
            $find->execute([$user, $type, $newGroup, $newChild]);
            $target = $find->fetch(PDO::FETCH_ASSOC);
            if (!$source || !$target || $source['id'] == $target['id']) continue;
            $transactions->execute([$user, $source['id']]);
            foreach ($transactions->fetchAll(PDO::FETCH_ASSOC) as $transaction) {
                $history->execute([$user, $transaction['id'], 'Categoria ajustada para o padrão', json_encode($transaction, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
            }
            $move->execute([$target['id'], $user, $source['id']]);
            $recurrences->execute([$target['id'], $user, $source['id']]);
            $archiveChild->execute([$source['id']]);
            if ($source['grupo_id'] != $target['grupo_id']) $archiveGroup->execute([$source['grupo_id']]);
        }
    }
};
