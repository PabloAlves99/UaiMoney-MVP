<?php

declare(strict_types=1);

namespace App\Repositories;

final class InvestmentRepository extends FinanceRepository
{
    public function list(int $user): array
    {
        return $this->rows("SELECT i.*,(SELECT COALESCE(SUM(e.valor_centavos),0) FROM investimento_eventos e WHERE e.investimento_id=i.id AND e.usuario_id=i.usuario_id AND e.ativo=1 AND e.tipo IN ('saldo_inicial','aporte','retirada')) AS aporte_liquido_centavos FROM investimentos i WHERE i.usuario_id=? ORDER BY i.ativo DESC,i.valor_atual_centavos DESC,i.nome", [$user]);
    }

    public function summary(int $user): array
    {
        return $this->rows("SELECT COALESCE(SUM((SELECT COALESCE(SUM(e.valor_centavos),0) FROM investimento_eventos e WHERE e.investimento_id=i.id AND e.usuario_id=i.usuario_id AND e.ativo=1 AND e.tipo IN ('saldo_inicial','aporte','retirada'))),0) AS aplicado,
            COALESCE(SUM(i.valor_atual_centavos),0) AS atual FROM investimentos i WHERE i.usuario_id=? AND i.ativo=1", [$user])[0];
    }

    public function create(int $user, array $data): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->run('INSERT INTO investimentos(usuario_id,nome,tipo,instituicao,objetivo,valor_aplicado_centavos,valor_atual_centavos,data_inicio)
                VALUES(?,?,?,?,?,?,?,?)', [$user, ...$data]);
            $id = (int) $this->pdo->lastInsertId();
            [$name, $type, $institution, $goal, $applied, $current, $date] = $data;
            $this->run("INSERT INTO investimento_eventos(usuario_id,investimento_id,tipo,valor_centavos,data,descricao)
                VALUES(?,?,'saldo_inicial',?,?,'Saldo inicial informado')", [$user,$id,$applied,$date]);
            if ($current !== $applied) $this->run("INSERT INTO investimento_eventos(usuario_id,investimento_id,tipo,valor_centavos,data,descricao)
                VALUES(?,?,'ajuste',?,?,'Diferença inicial informada')", [$user,$id,$current-$applied,$date]);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function find(int $user, int $id): ?array
    {
        return $this->rows("SELECT i.*,(SELECT COALESCE(SUM(e.valor_centavos),0) FROM investimento_eventos e WHERE e.investimento_id=i.id AND e.usuario_id=i.usuario_id AND e.ativo=1 AND e.tipo IN ('saldo_inicial','aporte','retirada')) AS aporte_liquido_centavos FROM investimentos i WHERE i.usuario_id=? AND i.id=?", [$user,$id])[0] ?? null;
    }

    public function events(int $user, int $id): array
    {
        return $this->rows('SELECT * FROM investimento_eventos WHERE usuario_id=? AND investimento_id=? ORDER BY data DESC,id DESC', [$user,$id]);
    }

    public function balanceAt(int $user, int $id, string $date): int
    {
        return (int)($this->rows('SELECT COALESCE(SUM(valor_centavos),0) AS saldo FROM investimento_eventos WHERE usuario_id=? AND investimento_id=? AND ativo=1 AND data<=?',[$user,$id,$date])[0]['saldo'] ?? 0);
    }

    public function hasYieldInMonth(int $user, int $id, string $month): bool
    {
        return (int)($this->rows("SELECT COUNT(*) AS total FROM investimento_eventos WHERE usuario_id=? AND investimento_id=? AND tipo='rendimento' AND ativo=1 AND substr(data,1,7)=?",[$user,$id,$month])[0]['total'] ?? 0)>0;
    }

    public function saveEvent(int $user, int $id, string $type, int $amount, string $date, ?string $description): void
    {
        $this->pdo->beginTransaction();
        try {
            $investment=$this->find($user,$id);
            if (!$investment || !(int)$investment['ativo']) throw new \DomainException('Investimento ativo não encontrado.');
            $balance=(int)$investment['valor_atual_centavos'];
            $delta=$type==='retirada' ? -$amount : $amount;
            if ($type==='rendimento' || $type==='ajuste') $delta=$amount;
            if ($balance+$delta<0) throw new \DomainException('O saldo do investimento não pode ficar negativo.');
            $this->run('INSERT INTO investimento_eventos(usuario_id,investimento_id,tipo,valor_centavos,data,descricao) VALUES(?,?,?,?,?,?)',[$user,$id,$type,$delta,$date,$description]);
            $this->recalculate($user,$id);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deactivateEvent(int $user, int $investmentId, int $eventId): void
    {
        $this->pdo->beginTransaction();
        try {
            $event=$this->rows('SELECT * FROM investimento_eventos WHERE id=? AND usuario_id=? AND investimento_id=? AND ativo=1',[$eventId,$user,$investmentId])[0] ?? null;
            if (!$event || $event['tipo']==='saldo_inicial') throw new \DomainException('Evento não encontrado ou não pode ser inativado.');
            $this->run('UPDATE investimento_eventos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE id=?',[$eventId]);
            $this->recalculate($user,$investmentId);
            $this->pdo->commit();
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    public function reactivateEvent(int $user, int $investmentId, int $eventId): void
    {
        $this->pdo->beginTransaction();
        try {
            $event=$this->rows('SELECT e.* FROM investimento_eventos e JOIN investimentos i ON i.id=e.investimento_id WHERE e.id=? AND e.usuario_id=? AND e.investimento_id=? AND e.ativo=0 AND i.ativo=1',[$eventId,$user,$investmentId])[0] ?? null;
            if (!$event) throw new \DomainException('Evento inativo ou investimento ativo não encontrado.');
            if($event['tipo']==='rendimento' && $this->hasYieldInMonth($user,$investmentId,substr((string)$event['data'],0,7))) throw new \DomainException('Já existe outro rendimento confirmado neste mês. Inative-o antes de reativar este.');
            $this->run('UPDATE investimento_eventos SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE id=?',[$eventId]);
            $this->recalculate($user,$investmentId);
            $this->pdo->commit();
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    private function recalculate(int $user, int $id): void
    {
        $events=$this->rows('SELECT tipo,valor_centavos FROM investimento_eventos WHERE usuario_id=? AND investimento_id=? AND ativo=1 ORDER BY data,id',[$user,$id]);
        $balance=0;$applied=0;
        foreach ($events as $event) {
            $value=(int)$event['valor_centavos'];
            $balance+=$value;
            if ($balance<0) throw new \DomainException('A alteração deixaria o investimento negativo em uma data do histórico.');
            if ($event['tipo']==='aporte' || $event['tipo']==='saldo_inicial') $applied+=$value;
            if ($event['tipo']==='retirada') $applied=max(0,$applied+$value);
        }
        $this->run('UPDATE investimentos SET valor_aplicado_centavos=?,valor_atual_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$applied,$balance,$user,$id]);
    }

    public function deactivate(int $user, int $id): void
    {
        $this->run('UPDATE investimentos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?', [$user, $id]);
    }

    public function reactivate(int $user, int $id): void
    {
        $this->run('UPDATE investimentos SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$user,$id]);
    }

    public function updateDetails(int $user, int $id, string $name, string $type, ?string $institution, ?string $goal): bool
    {
        return $this->run('UPDATE investimentos SET nome=?,tipo=?,instituicao=?,objetivo=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1',[$name,$type,$institution,$goal,$user,$id])===1;
    }

    public function updateMonthlyPlan(int $user,int $id,int $amount,int $rate=0): void
    { $this->run('UPDATE investimentos SET aporte_mensal_centavos=?,taxa_referencia_quatro_casas=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$amount,$rate,$user,$id]); }

    public function updateCurrentValue(int $user, int $id, int $amount): bool
    {
        return $this->run('UPDATE investimentos SET valor_atual_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1', [$amount, $user, $id]) === 1;
    }
}
