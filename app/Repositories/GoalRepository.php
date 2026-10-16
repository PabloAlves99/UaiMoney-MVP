<?php
declare(strict_types=1);
namespace App\Repositories;

final class GoalRepository extends FinanceRepository
{
    public function list(int $user): array { return $this->rows("SELECT * FROM metas_financeiras WHERE usuario_id=? AND ativo=1 ORDER BY CASE prioridade WHEN 'alta' THEN 0 WHEN 'media' THEN 1 ELSE 2 END,data_alvo IS NULL,data_alvo,nome", [$user]); }
    public function all(int $user): array { return $this->rows('SELECT * FROM metas_financeiras WHERE usuario_id=? ORDER BY ativo DESC,prioridade,nome',[$user]); }
    public function find(int $user,int $id): ?array { return $this->rows('SELECT * FROM metas_financeiras WHERE usuario_id=? AND id=?',[$user,$id])[0]??null; }
    public function create(int $user, array $data): void { $this->run('INSERT INTO metas_financeiras(usuario_id,nome,tipo,valor_meta_centavos,valor_atual_centavos,data_alvo,valor_legado_centavos) VALUES(?,?,?,?,?,?,?)', [$user, ...$data,$data[3]]); }
    public function createDetailed(int $user,array $data): void { $this->run('INSERT INTO metas_financeiras(usuario_id,nome,tipo,valor_meta_centavos,valor_atual_centavos,data_alvo,finalidade,prioridade,horizonte,aporte_mensal_centavos) VALUES(?,?,?,?,0,?,?,?,?,?)',[$user,...$data]); }
    public function updateDetails(int $user,int $id,array $data): bool { return $this->run('UPDATE metas_financeiras SET nome=?,tipo=?,valor_meta_centavos=?,data_alvo=?,finalidade=?,prioridade=?,horizonte=?,aporte_mensal_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1',[...$data,$user,$id])===1; }
    public function updateCurrent(int $user, int $id, int $amount): bool { return $this->run('UPDATE metas_financeiras SET valor_atual_centavos=valor_atual_centavos-valor_legado_centavos+?,valor_legado_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1', [$amount,$amount,$user,$id]) === 1; }
    public function deactivate(int $user, int $id): void { $this->run('UPDATE metas_financeiras SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$user,$id]); }
    public function reactivate(int $user,int $id): void
    {
        $goal=$this->find($user,$id);
        if(!$goal || (int)$goal['ativo']) throw new \DomainException('Objetivo arquivado não encontrado.');
        $sources=$this->sources($user);
        foreach($this->reservations($user,$id) as $reservation) {
            if(!(int)$reservation['ativo']) continue;
            $available=null;
            foreach($sources as $source) if($source['tipo']===$reservation['fonte_tipo'] && (int)$source['id']===(int)$reservation['fonte_id']) $available=(int)$source['saldo'];
            $others=(int)($this->rows('SELECT COALESCE(SUM(r.valor_centavos),0) AS total FROM objetivo_reservas r JOIN metas_financeiras m ON m.id=r.objetivo_id WHERE r.usuario_id=? AND r.fonte_tipo=? AND r.fonte_id=? AND r.ativo=1 AND m.ativo=1',[$user,$reservation['fonte_tipo'],$reservation['fonte_id']])[0]['total']??0);
            if($available===null || $others+(int)$reservation['valor_centavos']>$available) throw new \DomainException('A fonte de uma reserva não tem saldo disponível. Ajuste as reservas antes de reativar o objetivo.');
        }
        $this->run('UPDATE metas_financeiras SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$user,$id]);
    }

    public function sources(int $user): array
    {
        $accounts=$this->rows("SELECT c.id,c.nome,'conta' AS tipo,
            CASE WHEN c.saldo_inicial_em<=? THEN c.saldo_inicial_centavos ELSE 0 END+
            COALESCE((SELECT SUM(m.valor_centavos) FROM movimentos_caixa m WHERE m.usuario_id=c.usuario_id AND m.conta_id=c.id AND m.data>=c.saldo_inicial_em AND m.data<=?),0) AS saldo
            FROM contas c WHERE c.usuario_id=? AND c.ativo=1 ORDER BY c.nome",[date('Y-m-d'),date('Y-m-d'),$user]);
        $investments=$this->rows("SELECT id,nome,'investimento' AS tipo,valor_atual_centavos AS saldo FROM investimentos WHERE usuario_id=? AND ativo=1 ORDER BY nome",[$user]);
        return array_merge($accounts,$investments);
    }

    public function reservations(int $user,int $goal): array
    {
        return $this->rows("SELECT r.*,CASE r.fonte_tipo WHEN 'conta' THEN CASE WHEN c.ativo=1 THEN c.nome ELSE 'Conta arquivada' END ELSE CASE WHEN i.ativo=1 THEN i.nome ELSE 'Investimento arquivado' END END AS fonte_nome FROM objetivo_reservas r
            LEFT JOIN contas c ON c.id=r.fonte_id AND r.fonte_tipo='conta' LEFT JOIN investimentos i ON i.id=r.fonte_id AND r.fonte_tipo='investimento'
            WHERE r.usuario_id=? AND r.objetivo_id=? AND r.ativo=1 ORDER BY r.id",[$user,$goal]);
    }

    public function reservationWarnings(int $user,int $goal): array
    {
        $sources=[];foreach($this->sources($user) as $source)$sources[$source['tipo'].':'.$source['id']]=$source;
        $warnings=[];
        foreach($this->reservations($user,$goal) as $reservation){
            if(!(int)$reservation['ativo'])continue;
            $key=$reservation['fonte_tipo'].':'.$reservation['fonte_id'];
            $source=$sources[$key]??null;
            $total=(int)($this->rows('SELECT COALESCE(SUM(r.valor_centavos),0) AS total FROM objetivo_reservas r JOIN metas_financeiras m ON m.id=r.objetivo_id WHERE r.usuario_id=? AND r.fonte_tipo=? AND r.fonte_id=? AND r.ativo=1 AND m.ativo=1',[$user,$reservation['fonte_tipo'],$reservation['fonte_id']])[0]['total']??0);
            if(!$source || $total>(int)$source['saldo'])$warnings[]='Confira a reserva em '.($reservation['fonte_nome']??'fonte indisponível').': o saldo atual não cobre todas as reservas vinculadas.';
        }
        return array_unique($warnings);
    }

    public function saveReservation(int $user,int $goal,string $type,int $source,int $amount): void
    {
        $this->pdo->beginTransaction();
        try {
            $target=$this->find($user,$goal);
            if (!$target || !(int)$target['ativo']) throw new \DomainException('Objetivo ativo não encontrado.');
            $available=null;
            foreach($this->sources($user) as $row) if($row['tipo']===$type && (int)$row['id']===$source) $available=(int)$row['saldo'];
            if($available===null) throw new \DomainException('Fonte ativa não encontrada.');
            $already=(int)($this->rows("SELECT COALESCE(SUM(r.valor_centavos),0) AS total FROM objetivo_reservas r JOIN metas_financeiras m ON m.id=r.objetivo_id
                WHERE r.usuario_id=? AND r.fonte_tipo=? AND r.fonte_id=? AND r.ativo=1 AND m.ativo=1 AND r.objetivo_id<>?",[$user,$type,$source,$goal])[0]['total']??0);
            if($amount+$already>$available) throw new \DomainException('A soma reservada em objetivos ultrapassa o saldo disponível desta fonte.');
            $this->run('INSERT INTO objetivo_reservas(usuario_id,objetivo_id,fonte_tipo,fonte_id,valor_centavos) VALUES(?,?,?,?,?) ON CONFLICT(objetivo_id,fonte_tipo,fonte_id) DO UPDATE SET valor_centavos=excluded.valor_centavos,ativo=1',[$user,$goal,$type,$source,$amount]);
            $this->syncCurrent($user,$goal);
            $this->pdo->commit();
        } catch(\Throwable $e){$this->pdo->rollBack();throw $e;}
    }

    public function deactivateReservation(int $user,int $goal,int $id): void
    {
        $this->run('UPDATE objetivo_reservas SET ativo=0 WHERE usuario_id=? AND objetivo_id=? AND id=?',[$user,$goal,$id]);
        $this->syncCurrent($user,$goal);
    }

    public function reactivateReservation(int $user,int $goal,int $id): void
    {
        $row=$this->rows('SELECT * FROM objetivo_reservas WHERE usuario_id=? AND objetivo_id=? AND id=? AND ativo=0',[$user,$goal,$id])[0]??null;
        if(!$row) throw new \DomainException('Reserva inativa não encontrada.');
        $this->saveReservation($user,$goal,(string)$row['fonte_tipo'],(int)$row['fonte_id'],(int)$row['valor_centavos']);
    }

    private function syncCurrent(int $user,int $goal): void
    {
        $this->run('UPDATE metas_financeiras SET valor_atual_centavos=valor_legado_centavos+(SELECT COALESCE(SUM(valor_centavos),0) FROM objetivo_reservas WHERE usuario_id=? AND objetivo_id=? AND ativo=1),atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$user,$goal,$user,$goal]);
    }
}
