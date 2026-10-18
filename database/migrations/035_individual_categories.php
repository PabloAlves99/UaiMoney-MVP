<?php
declare(strict_types=1);

use App\Services\DefaultCategories;

return function (PDO $pdo): void {
    $users = $pdo->query('SELECT id FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($users as $user) DefaultCategories::add($pdo, (int) $user);

    // A null description maps the whole subgroup. A description maps only an
    // identified use of a former combined subgroup, leaving uncertain uses intact.
    $maps = [
        ['Casa', 'Utensilios', 'Moradia', 'Utensílios domésticos', null],
        ['Casa', 'Utensílios', 'Moradia', 'Utensílios domésticos', null],
        ['Moto', 'Estacionamento', 'Transporte', 'Estacionamento', null],
        ['Moto', 'Gasolina', 'Transporte', 'Combustível', null],
        ['Moto', 'Manutenção', 'Transporte', 'Manutenção do veículo', null],
        ['Moto', 'Peças', 'Transporte', 'Peças do veículo', null],
        ['Mercado', 'Compras', 'Alimentação', 'Mercado', null],
        ['Juros', 'Fatura', 'Financeiro', 'Juros de cartão', null],
        ['Alimentação', 'Lanche', 'Alimentação', 'Lanches', null],
        ['Alimentação', 'Restaurantes', 'Alimentação', 'Restaurante', null],
        ['Assinaturas', 'Club Esfera', 'Assinaturas', 'Clube de benefícios', null],
        ['Transporte', 'Manutenção do veículo', 'Transporte', 'Equipamentos de proteção', 'Bota de chuva'],
        ['Alimentação', 'Mercado', 'Alimentação', 'Lanches', 'Pastel'],
        ['Alimentação', 'Restaurantes e delivery', 'Alimentação', 'Restaurante', 'Almoço de sábado'],
        ['Assinaturas', 'Assinaturas', 'Moradia', 'Internet residencial', 'Internet residencial'],
        ['Assinaturas', 'Assinaturas', 'Assinaturas', 'Outras assinaturas', 'Serviço de streaming'],
        ['Assinaturas', 'Assinaturas', 'Assinaturas', 'Outras assinaturas', 'Revista digital pausada'],
    ];
    $find = $pdo->prepare("SELECT s.id,s.grupo_id,s.ativo,g.ativo grupo_ativo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? AND g.tipo='despesa' AND g.nome=? AND s.nome=?");
    $read = $pdo->prepare('SELECT * FROM transacoes WHERE usuario_id=? AND subgrupo_id=? AND (? IS NULL OR descricao=?)');
    $history = $pdo->prepare('INSERT INTO historico_movimentacoes(usuario_id,transacao_id,acao,dados_anteriores) VALUES(?,?,?,?)');
    $move = $pdo->prepare('UPDATE transacoes SET subgrupo_id=?,versao=versao+1 WHERE usuario_id=? AND subgrupo_id=? AND (? IS NULL OR descricao=?)');
    $recurrences = $pdo->prepare('UPDATE recorrencias SET subgrupo_id=? WHERE usuario_id=? AND subgrupo_id=? AND (? IS NULL OR descricao=?)');
    $archive = $pdo->prepare('UPDATE subgrupos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND NOT EXISTS(SELECT 1 FROM transacoes WHERE subgrupo_id=subgrupos.id) AND NOT EXISTS(SELECT 1 FROM recorrencias WHERE subgrupo_id=subgrupos.id)');
    $archiveGroup = $pdo->prepare('UPDATE grupos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND NOT EXISTS(SELECT 1 FROM subgrupos WHERE grupo_id=grupos.id AND ativo=1) AND NOT EXISTS(SELECT 1 FROM orcamentos WHERE grupo_id=grupos.id)');
    foreach ($users as $user) {
        foreach ($maps as [$fromGroup, $from, $toGroup, $to, $description]) {
            $find->execute([$user, $fromGroup, $from]);
            $source = $find->fetch(PDO::FETCH_ASSOC);
            $find->execute([$user, $toGroup, $to]);
            $target = $find->fetch(PDO::FETCH_ASSOC);
            if (!$source || !$target || !$target['ativo'] || !$target['grupo_ativo'] || $source['id'] == $target['id']) continue;
            $read->execute([$user, $source['id'], $description, $description]);
            foreach ($read->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $history->execute([$user, $row['id'], 'Categoria ajustada para finalidade individual', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
            }
            $move->execute([$target['id'], $user, $source['id'], $description, $description]);
            $recurrences->execute([$target['id'], $user, $source['id'], $description, $description]);
            if ($description === null) $archive->execute([$source['id']]);
            if ($source['grupo_id'] != $target['grupo_id']) $archiveGroup->execute([$source['grupo_id']]);
        }
        // Remove unused combined defaults from selection without guessing where
        // transactions or recurring entries with ambiguous descriptions belong.
        foreach ([['Moradia','Aluguel e condomínio'], ['Moradia','Contas da casa'], ['Moradia','Manutenção'], ['Alimentação','Restaurantes e delivery'], ['Transporte','Transporte público e aplicativos'], ['Educação','Cursos e mensalidades'], ['Educação','Livros e materiais'], ['Lazer','Passeios e viagens'], ['Lazer','Assinaturas e entretenimento'], ['Outras despesas','Impostos e taxas'], ['Outras despesas','Serviços financeiros']] as [$group, $child]) {
            $find->execute([$user, $group, $child]);
            $source = $find->fetch(PDO::FETCH_ASSOC);
            if ($source) $archive->execute([$source['id']]);
        }
    }
};
