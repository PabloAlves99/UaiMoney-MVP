<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Money;
use DomainException;
use PDO;
use Throwable;

final class StatementImportService
{
    public function __construct(private readonly PDO $pdo, private readonly TransactionService $transactions,
        private readonly CardService $cards) {}

    public function parse(string $filename, string $content): array
    {
        if (strlen($content) > 2_000_000) throw new DomainException('Arquivo acima do limite de 2 MB.');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $rows = match ($extension) {
            'csv' => $this->csv($content),
            'ofx' => $this->ofx($content),
            default => throw new DomainException('Envie um arquivo CSV ou OFX.'),
        };
        if (!$rows || count($rows) > 200) throw new DomainException('O arquivo deve conter de 1 a 200 movimentações. Divida extratos maiores.');
        $seen = [];
        foreach ($rows as &$row) {
            $row['data'] = $this->date($row['data']);
            $row['descricao'] = trim(mb_substr($row['descricao'], 0, 160));
            $amount = preg_replace('/\s|R\$/iu', '', $row['valor']);
            if (str_starts_with($amount, '+')) $amount = substr($amount, 1);
            if (preg_match('/^\((.+)\)$/', $amount, $match)) $amount = '-' . $match[1];
            $row['centavos'] = Money::toSignedCents($amount);
            if ($row['descricao'] === '' || $row['centavos'] === 0 || $row['data'] > date('Y-m-d'))
                throw new DomainException('Confira descrição, valor e data do extrato. Datas futuras não são aceitas.');
            $fingerprint = implode('|', [$row['data'], mb_strtolower($row['descricao']), $row['centavos']]);
            $row['repetida'] = isset($seen[$fingerprint]);
            $seen[$fingerprint] = true;
            unset($row['valor']);
        }
        return $rows;
    }

    private function csv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $content);
        rewind($stream);
        $first = (string) fgets($stream);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        rewind($stream);
        $headers = array_map(static fn($v) => mb_strtolower(trim((string) $v)), fgetcsv($stream, 0, $delimiter) ?: []);
        $lookup = static function (array $names) use ($headers): int {
            foreach ($names as $name) { $index = array_search($name, $headers, true); if ($index !== false) return $index; }
            throw new DomainException('CSV precisa das colunas Data, Descrição e Valor.');
        };
        $date = $lookup(['data', 'date']);
        $description = $lookup(['descrição', 'descricao', 'description', 'histórico', 'historico', 'merchant']);
        $amount = $lookup(['valor', 'amount', 'value']);
        $rows = [];
        while (($fields = fgetcsv($stream, 0, $delimiter)) !== false) {
            if ($fields === [null]) continue;
            $rows[] = ['data' => (string) ($fields[$date] ?? ''), 'descricao' => (string) ($fields[$description] ?? ''), 'valor' => (string) ($fields[$amount] ?? '')];
        }
        fclose($stream);
        return $rows;
    }

    private function ofx(string $content): array
    {
        if (!mb_check_encoding($content, 'UTF-8')) $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>)|$)/is', $content, $matches);
        $field = static function (string $block, string $tag): string {
            return preg_match('/<' . $tag . '>([^<\r\n]+)/i', $block, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8')) : '';
        };
        $rows = [];
        foreach ($matches[1] as $block) {
            $rows[] = ['data' => substr($field($block, 'DTPOSTED'), 0, 8),
                'descricao' => $field($block, 'NAME') ?: $field($block, 'MEMO'), 'valor' => $field($block, 'TRNAMT')];
        }
        return $rows;
    }

    private function date(string $date): string
    {
        $date = trim($date);
        if (preg_match('/^\d{8}$/', $date)) $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) $date = substr($date, 6, 4) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new DomainException('Data inválida no extrato.');
        return $date;
    }

    public function duplicate(int $user, ?int $account, array $row): bool
    {
        $card = ($row['destino_tipo'] ?? 'conta') === 'cartao';
        $id = $card ? (int) ($row['cartao_id'] ?? 0) : (int) ($row['conta_id'] ?? $account);
        if ($id <= 0) return false;
        $column = $card ? 'cartao_id' : 'conta_id';
        $stmt = $this->pdo->prepare("SELECT 1 FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id WHERE t.usuario_id=? AND t.$column=? AND t.data_competencia=? AND t.descricao=? COLLATE NOCASE AND t.valor_centavos=? AND g.tipo=? AND t.excluida_em IS NULL LIMIT 1");
        $stmt->execute([$user, $id, $row['data'], $row['descricao'], abs($row['centavos']), $row['centavos'] < 0 ? 'despesa' : 'receita']);
        return $stmt->fetchColumn() !== false;
    }

    public function applyDefaults(array $rows, string $type, ?int $account, ?int $card): array
    {
        if (!in_array($type, ['outro', 'pix', 'dinheiro', 'debito', 'boleto', 'transferencia', 'cartao'], true))
            throw new DomainException('Escolha um tipo padrão válido.');
        if ($type === 'cartao' ? !$card : !$account)
            throw new DomainException($type === 'cartao' ? 'Escolha o cartão padrão.' : 'Escolha a conta padrão.');
        foreach ($rows as &$row) {
            $row['destino_tipo'] = $type === 'cartao' ? 'cartao' : 'conta';
            $row['conta_id'] = $account ?: 0;
            $row['cartao_id'] = $card ?: 0;
            $row['meio_pagamento'] = $type === 'cartao' ? 'outro' : $type;
        }
        unset($row);
        return $rows;
    }

    public function draft(int $user): ?array
    {
        $stmt = $this->pdo->prepare('SELECT conta_id,nonce,itens_json,atualizado_em FROM rascunhos_importacao WHERE usuario_id=?');
        $stmt->execute([$user]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) return null;
        return [
            'user' => $user,
            'account' => $record['conta_id'] === null ? null : (int) $record['conta_id'],
            'nonce' => $record['nonce'],
            'rows' => json_decode($record['itens_json'], true, 512, JSON_THROW_ON_ERROR),
            'updated' => $record['atualizado_em'],
        ];
    }

    public function createDraft(int $user, ?int $account, array $rows): void
    {
        $nonce = bin2hex(random_bytes(16));
        $stmt = $this->pdo->prepare('INSERT INTO rascunhos_importacao(usuario_id,conta_id,nonce,itens_json)
            VALUES(?,?,?,?) ON CONFLICT(usuario_id) DO UPDATE SET conta_id=excluded.conta_id,
            nonce=excluded.nonce,itens_json=excluded.itens_json,atualizado_em=CURRENT_TIMESTAMP');
        $stmt->execute([$user, $account, $nonce, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    public function saveDraftChoices(int $user, string $nonce, array $selected, array $categories,
        array $targets = [], array $accounts = [], array $cards = [], array $methods = []): void
    {
        $draft = $this->draft($user);
        if (!$draft || !hash_equals($draft['nonce'], $nonce)) throw new DomainException('Prévia alterada. Recarregue a página.');
        foreach ($draft['rows'] as $index => &$row) {
            $previousTarget = [$row['destino_tipo'] ?? 'conta', (int) ($row['conta_id'] ?? $draft['account']), (int) ($row['cartao_id'] ?? 0)];
            $row['selected'] = isset($selected[$index]);
            $row['category_id'] = max(0, (int) ($categories[$index] ?? 0));
            if (isset($targets[$index])) $row['destino_tipo'] = (string) $targets[$index];
            if (isset($accounts[$index])) $row['conta_id'] = (int) $accounts[$index];
            if (isset($cards[$index])) $row['cartao_id'] = (int) $cards[$index];
            if (isset($methods[$index])) $row['meio_pagamento'] = (string) $methods[$index];
            $currentTarget = [$row['destino_tipo'] ?? 'conta', (int) ($row['conta_id'] ?? $draft['account']), (int) ($row['cartao_id'] ?? 0)];
            if ($previousTarget !== $currentTarget) $row['existente'] = $this->duplicate($user, $draft['account'], $row);
        }
        unset($row);
        $stmt = $this->pdo->prepare('UPDATE rascunhos_importacao SET itens_json=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND nonce=?');
        $stmt->execute([json_encode($draft['rows'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user, $nonce]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Prévia alterada. Recarregue a página.');
    }

    public function discardDraft(int $user, string $nonce): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rascunhos_importacao WHERE usuario_id=? AND nonce=?');
        $stmt->execute([$user, $nonce]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Prévia alterada. Recarregue a página.');
    }

    public function removeDraftRow(int $user, string $nonce, int $index): void
    {
        $draft = $this->draft($user);
        if (!$draft || !hash_equals($draft['nonce'], $nonce) || !array_key_exists($index, $draft['rows']))
            throw new DomainException('Linha não encontrada. Recarregue a página.');
        array_splice($draft['rows'], $index, 1);
        if (!$draft['rows']) {
            $this->discardDraft($user, $nonce);
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE rascunhos_importacao SET nonce=?,itens_json=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND nonce=?');
        $stmt->execute([bin2hex(random_bytes(16)), json_encode($draft['rows'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user, $nonce]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Prévia alterada. Recarregue a página.');
    }

    public function import(int $user, ?int $account, array $rows, array $selected, array $categories, ?string $draftNonce = null): int
    {
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            if ($draftNonce !== null) {
                $draft = $this->draft($user);
                if (!$draft || !hash_equals($draft['nonce'], $draftNonce) || $draft['account'] !== $account || $draft['rows'] !== $rows)
                    throw new DomainException('Prévia alterada. Recarregue a página.');
            }
            $stmt = $this->pdo->prepare('INSERT INTO lotes_importacao(usuario_id) VALUES(?)');
            $stmt->execute([$user]);
            $batch = (int) $this->pdo->lastInsertId();
            $count = 0;
            foreach ($rows as $index => $row) {
                if (!isset($selected[$index])) continue;
                $category = (int) ($categories[$index] ?? 0);
                $amount = number_format(abs($row['centavos']) / 100, 2, '.', '');
                $categoryStmt = $this->pdo->prepare('SELECT g.tipo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE s.id=? AND g.usuario_id=? AND s.ativo=1 AND g.ativo=1');
                $categoryStmt->execute([$category, $user]);
                if ($categoryStmt->fetchColumn() !== ($row['centavos'] < 0 ? 'despesa' : 'receita'))
                    throw new DomainException('Escolha uma categoria compatível com o valor da linha.');
                $target = $row['destino_tipo'] ?? 'conta';
                if ($target === 'cartao') {
                    if ($row['centavos'] >= 0) throw new DomainException('Compra no cartão precisa ter valor negativo.');
                    $ids = $this->cards->recordPurchase($user, [
                        'cartao_id' => (int) ($row['cartao_id'] ?? 0), 'subgrupo_id' => $category,
                        'data' => $row['data'], 'descricao' => $row['descricao'], 'valor' => $amount,
                        'parcelas' => 1, 'observacao' => 'Importado de extrato',
                    ]);
                } elseif ($target === 'conta') {
                    $method = (string) ($row['meio_pagamento'] ?? 'outro');
                    $id = $this->transactions->create($user, $category, (int) ($row['conta_id'] ?? $account), $row['descricao'], $amount,
                        $row['data'], $row['data'], $row['data'], 'efetivada', $method, 'Importado de extrato');
                    $ids = [$id];
                } else {
                    throw new DomainException('Escolha conta ou cartão para a linha.');
                }
                foreach ($ids as $id) $this->pdo->prepare('INSERT INTO itens_importacao(lote_id,transacao_id) VALUES(?,?)')->execute([$batch, $id]);
                $count++;
            }
            if ($count === 0) throw new DomainException('Selecione ao menos um lançamento novo para importar.');
            if ($draftNonce !== null) {
                $remaining = [];
                foreach ($rows as $index => $row) {
                    if (isset($selected[$index])) continue;
                    $row['category_id'] = max(0, (int) ($categories[$index] ?? ($row['category_id'] ?? 0)));
                    $remaining[] = $row;
                }
                if ($remaining) {
                    $this->pdo->prepare('UPDATE rascunhos_importacao SET nonce=?,itens_json=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND nonce=?')
                        ->execute([bin2hex(random_bytes(16)), json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user, $draftNonce]);
                } else {
                    $this->pdo->prepare('DELETE FROM rascunhos_importacao WHERE usuario_id=? AND nonce=?')->execute([$user, $draftNonce]);
                }
            }
            $this->pdo->exec('COMMIT');
            return $batch;
        } catch (Throwable $error) {
            $this->pdo->exec('ROLLBACK');
            throw $error;
        }
    }

    public function lastBatch(int $user): ?array
    {
        $stmt = $this->pdo->prepare('SELECT b.id,b.criado_em,COUNT(i.transacao_id) quantidade FROM lotes_importacao b JOIN itens_importacao i ON i.lote_id=b.id WHERE b.usuario_id=? AND b.desfeito_em IS NULL GROUP BY b.id ORDER BY b.id DESC LIMIT 1');
        $stmt->execute([$user]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function batches(int $user): array
    {
        $stmt = $this->pdo->prepare('SELECT b.id,b.criado_em,b.desfeito_em,COUNT(i.transacao_id) quantidade
            FROM lotes_importacao b JOIN itens_importacao i ON i.lote_id=b.id
            WHERE b.usuario_id=? GROUP BY b.id ORDER BY b.id DESC');
        $stmt->execute([$user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteBatch(int $user, int $batch): void
    {
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $this->pdo->prepare('SELECT t.id,t.versao,t.excluida_em,t.fatura_id,b.desfeito_em,
                EXISTS(SELECT 1 FROM estornos e WHERE e.transacao_id=t.id) AS estornado,
                EXISTS(SELECT 1 FROM historico_movimentacoes h WHERE h.transacao_id=t.id) AS alterado
                FROM itens_importacao i JOIN lotes_importacao b ON b.id=i.lote_id
                JOIN transacoes t ON t.id=i.transacao_id WHERE b.id=? AND b.usuario_id=?');
            $stmt->execute([$batch, $user]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) throw new DomainException('Lote não encontrado.');
            foreach ($rows as $row) {
                $original = (int) $row['versao'] === 1 && $row['excluida_em'] === null;
                $previouslyUndone = $row['desfeito_em'] !== null && (int) $row['versao'] === 2 && $row['excluida_em'] !== null;
                if ((!$original && !$previouslyUndone) || (int) $row['estornado'] !== 0 || (int) $row['alterado'] !== 0)
                    throw new DomainException('Há lançamentos do lote alterados ou estornados. Revise-os antes de excluir permanentemente.');
            }
            $blocked = $this->pdo->prepare("SELECT 1 FROM itens_importacao i JOIN transacoes t ON t.id=i.transacao_id JOIN faturas f ON f.id=t.fatura_id
                WHERE i.lote_id=? AND (f.status<>'aberta' OR EXISTS(SELECT 1 FROM pagamentos_fatura p WHERE p.fatura_id=f.id)
                OR EXISTS(SELECT 1 FROM creditos_fatura c WHERE c.origem_id=f.id OR c.destino_id=f.id)) LIMIT 1");
            $blocked->execute([$batch]);
            if ($blocked->fetchColumn() !== false) throw new DomainException('Há compras em fatura fechada, paga ou com crédito. Revise a fatura antes de excluir.');
            $invoiceIds = array_unique(array_filter(array_column($rows, 'fatura_id')));
            $this->pdo->prepare('DELETE FROM itens_importacao WHERE lote_id=?')->execute([$batch]);
            $deleteTransaction = $this->pdo->prepare('DELETE FROM transacoes WHERE id=? AND usuario_id=?');
            foreach ($rows as $row) {
                $deleteTransaction->execute([(int) $row['id'], $user]);
                if ($deleteTransaction->rowCount() !== 1) throw new DomainException('Não foi possível excluir todos os lançamentos do lote.');
            }
            $deleteInvoice = $this->pdo->prepare("DELETE FROM faturas WHERE id=? AND usuario_id=? AND status='aberta'
                AND NOT EXISTS(SELECT 1 FROM transacoes t WHERE t.fatura_id=faturas.id)
                AND NOT EXISTS(SELECT 1 FROM pagamentos_fatura p WHERE p.fatura_id=faturas.id)
                AND NOT EXISTS(SELECT 1 FROM creditos_fatura c WHERE c.origem_id=faturas.id OR c.destino_id=faturas.id)
                AND NOT EXISTS(SELECT 1 FROM estornos e WHERE e.fatura_id=faturas.id)");
            foreach ($invoiceIds as $invoiceId) $deleteInvoice->execute([(int) $invoiceId, $user]);
            $this->pdo->prepare('DELETE FROM lotes_importacao WHERE id=? AND usuario_id=?')->execute([$batch, $user]);
            $this->pdo->exec('COMMIT');
        } catch (Throwable $error) {
            $this->pdo->exec('ROLLBACK');
            throw $error;
        }
    }
}
