<?php
declare(strict_types=1);
namespace App\Repositories;

final class ArchiveRepository extends FinanceRepository
{
    public function items(int $user): array
    {
        return [
            'Categorias'=>$this->rows("SELECT id,nome,'categoria' AS tipo FROM grupos WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Subcategorias'=>$this->rows("SELECT s.id,s.nome || ' · ' || g.nome AS nome,'subcategoria' AS tipo,g.ativo AS pai_ativo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? AND s.ativo=0 ORDER BY s.nome",[$user]),
            'Contas'=>$this->rows("SELECT id,nome,'conta' AS tipo FROM contas WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Cartões'=>$this->rows("SELECT id,nome,'cartao' AS tipo FROM cartoes WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Investimentos'=>$this->rows("SELECT id,nome,'investimento' AS tipo FROM investimentos WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Eventos de investimentos'=>$this->rows("SELECT e.id,e.investimento_id AS pai_id,i.nome || ' · ' || e.tipo || ' · ' || e.data AS nome,'evento' AS tipo,i.ativo AS pai_ativo FROM investimento_eventos e JOIN investimentos i ON i.id=e.investimento_id WHERE e.usuario_id=? AND e.ativo=0 ORDER BY e.data DESC",[$user]),
            'Objetivos'=>$this->rows("SELECT id,nome,'objetivo' AS tipo FROM metas_financeiras WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Reservas de objetivos'=>$this->rows("SELECT r.id,r.objetivo_id AS pai_id,m.nome || ' · ' || r.fonte_tipo AS nome,'reserva' AS tipo,m.ativo AS pai_ativo FROM objetivo_reservas r JOIN metas_financeiras m ON m.id=r.objetivo_id WHERE r.usuario_id=? AND r.ativo=0 ORDER BY r.id DESC",[$user]),
            'Projeções'=>$this->rows("SELECT id,nome,'projecao' AS tipo FROM projecoes WHERE usuario_id=? AND ativo=0 ORDER BY nome",[$user]),
            'Limites'=>$this->rows("SELECT b.id,g.nome || ' · ' || b.ano_mes AS nome,'limite' AS tipo FROM orcamentos b JOIN grupos g ON g.id=b.grupo_id WHERE b.usuario_id=? AND b.ativo=0 ORDER BY b.ano_mes DESC",[$user]),
            'Movimentações'=>$this->rows("SELECT id,descricao AS nome,versao,'movimentacao' AS tipo FROM transacoes WHERE usuario_id=? AND excluida_em IS NOT NULL ORDER BY excluida_em DESC LIMIT 100",[$user]),
        ];
    }
}
