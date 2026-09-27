<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\FinancialDate;
use App\Core\Money;
use App\Repositories\GoalRepository;
use DomainException;

final class GoalService
{
    private const TYPES=['reserva','viagem','imovel','educacao','outro'];
    public function __construct(private readonly GoalRepository $goals) {}
    public function create(int $user,array $input): void {
        $name=trim((string)($input['nome']??'')); $type=(string)($input['tipo']??'');
        if($name===''||mb_strlen($name)>100) throw new DomainException('Informe uma meta de até 100 caracteres.');
        if(!in_array($type,self::TYPES,true)) throw new DomainException('Selecione um tipo de meta válido.');
        $target=Money::toCents((string)($input['valor_meta']??'')); $current=Money::toCents((string)($input['valor_atual']??'0'));
        if($target<=0) throw new DomainException('A meta precisa ser maior que zero.');
        $date=trim((string)($input['data_alvo']??''));
        $this->goals->create($user,[$name,$type,$target,$current,$date===''?null:FinancialDate::date($date)]);
    }
    public function updateCurrent(int $user,int $id,array $input): void { if(!$this->goals->updateCurrent($user,$id,Money::toCents((string)($input['valor_atual']??'')))) throw new DomainException('Meta ativa não encontrada.'); }
    public function deactivate(int $user,int $id): void { $this->goals->deactivate($user,$id); }
}
