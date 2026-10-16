<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Money;
use DomainException;
use PDO;
use Throwable;

final class StatementImportService
{
    public function __construct(private readonly PDO $pdo, private readonly TransactionService $transactions) {}

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

    public function duplicate(int $user, int $account, array $row): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id WHERE t.usuario_id=? AND t.conta_id=? AND t.data_competencia=? AND t.descricao=? COLLATE NOCASE AND t.valor_centavos=? AND g.tipo=? AND t.excluida_em IS NULL LIMIT 1');
        $stmt->execute([$user, $account, $row['data'], $row['descricao'], abs($row['centavos']), $row['centavos'] < 0 ? 'despesa' : 'receita']);
        return $stmt->fetchColumn() !== false;
    }

    public function draft(int $user): ?array
    {
        $stmt = $this->pdo->prepare('SELECT conta_id,nonce,itens_json,atualizado_em FROM rascunhos_importacao WHERE usuario_id=?');
        $stmt->execute([$user]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) return null;
        return [
            'user' => $user,
            'account' => (int) $record['conta_id'],
            'nonce' => $record['nonce'],
            'rows' => json_decode($record['itens_json'], true, 512, JSON_THROW_ON_ERROR),
            'updated' => $record['atualizado_em'],
        ];
    }

    public function createDraft(int $user, int $account, array $rows): void
    {
        $nonce = bin2hex(random_bytes(16));
        $stmt = $this->pdo->prepare('INSERT INTO rascunhos_importacao(usuario_id,conta_id,nonce,itens_json)
            VALUES(?,?,?,?) ON CONFLICT(usuario_id) DO UPDATE SET conta_id=excluded.conta_id,
            nonce=excluded.nonce,itens_json=excluded.itens_json,atualizado_em=CURRENT_TIMESTAMP');
        $stmt->execute([$user, $account, $nonce, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    public function saveDraftChoices(int $user, string $nonce, array $selected, array $categories): void
    {
        $draft = $this->draft($user);
        if (!$draft || !hash_equals($draft['nonce'], $nonce)) throw new DomainException('Prévia alterada. Recarregue a página.');
        foreach ($draft['rows'] as $index => &$row) {
            $row['selected'] = isset($selected[$index]);
            $row['category_id'] = max(0, (int) ($categories[$index] ?? 0));
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

    public function import(int $user, int $account, array $rows, array $selected, array $categories, ?string $draftNonce = null): int
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
                $id = $this->transactions->create($user, $category, $account, $row['descricao'], $amount,
                    $row['data'], $row['data'], $row['data'], 'efetivada', 'outro', 'Importado de extrato');
                $this->pdo->prepare('INSERT INTO itens_importacao(lote_id,transacao_id) VALUES(?,?)')->execute([$batch, $id]);
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

    public function undo(int $user, int $batch): void
    {
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $this->pdo->prepare('SELECT t.id,t.versao,t.excluida_em,EXISTS(SELECT 1 FROM estornos e WHERE e.transacao_id=t.id) AS estornado FROM itens_importacao i JOIN lotes_importacao b ON b.id=i.lote_id JOIN transacoes t ON t.id=i.transacao_id WHERE b.id=? AND b.usuario_id=? AND b.desfeito_em IS NULL');
            $stmt->execute([$batch, $user]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) throw new DomainException('Lote não encontrado ou já desfeito.');
            foreach ($rows as $row) if ((int) $row['versao'] !== 1 || $row['excluida_em'] !== null || (int) $row['estornado'] !== 0)
                throw new DomainException('Há lançamentos do lote alterados. Revise-os antes de desfazer.');
            $this->pdo->prepare("UPDATE transacoes SET status_antes_exclusao=status,status='cancelada',excluida_em=CURRENT_TIMESTAMP,versao=versao+1 WHERE id IN (SELECT transacao_id FROM itens_importacao WHERE lote_id=?)")->execute([$batch]);
            $this->pdo->prepare('UPDATE lotes_importacao SET desfeito_em=CURRENT_TIMESTAMP WHERE id=? AND usuario_id=?')->execute([$batch, $user]);
            $this->pdo->exec('COMMIT');
        } catch (Throwable $error) {
            $this->pdo->exec('ROLLBACK');
            throw $error;
        }
    }
}
