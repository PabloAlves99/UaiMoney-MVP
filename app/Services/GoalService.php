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
        $target=Money::toCents((string)($input['valor_meta']??''));
        if($target<=0) throw new DomainException('A meta precisa ser maior que zero.');
        $date=trim((string)($input['data_alvo']??''));
        $purpose=trim((string)($input['finalidade']??''));$priority=(string)($input['prioridade']??'media');$horizon=(string)($input['horizonte']??'indefinido');
        if(mb_strlen($purpose)>300 || !in_array($priority,['alta','media','baixa'],true) || !in_array($horizon,['curto','medio','longo','indefinido'],true)) throw new DomainException('Confira finalidade, prioridade e horizonte.');
        $monthly=Money::toCents((string)($input['aporte_mensal']??'0'));
        $this->goals->createDetailed($user,[$name,$type,$target,$date===''?null:FinancialDate::date($date),$purpose?:null,$priority,$horizon,$monthly]);
    }
    public function update(int $user,int $id,array $input): void {
        $name=trim((string)($input['nome']??''));$type=(string)($input['tipo']??'');
        if($name===''||mb_strlen($name)>100||!in_array($type,self::TYPES,true)) throw new DomainException('Confira nome e tipo do objetivo.');
        $target=Money::toCents((string)($input['valor_meta']??''));if($target<=0) throw new DomainException('O valor alvo deve ser maior que zero.');
        $date=trim((string)($input['data_alvo']??''));$purpose=trim((string)($input['finalidade']??''));$priority=(string)($input['prioridade']??'media');$horizon=(string)($input['horizonte']??'indefinido');
        if(mb_strlen($purpose)>300||!in_array($priority,['alta','media','baixa'],true)||!in_array($horizon,['curto','medio','longo','indefinido'],true)) throw new DomainException('Confira finalidade, prioridade e horizonte.');
        $monthly=Money::toCents((string)($input['aporte_mensal']??'0'));
        if(!$this->goals->updateDetails($user,$id,[$name,$type,$target,$date===''?null:FinancialDate::date($date),$purpose?:null,$priority,$horizon,$monthly])) throw new DomainException('Objetivo ativo não encontrado.');
    }
    public function reserve(int $user,int $id,array $input): void {
        $parts=explode(':',(string)($input['fonte']??''),2);$source=filter_var($parts[1]??null,FILTER_VALIDATE_INT);
        if(!in_array($parts[0]??'',['conta','investimento'],true)||!$source||$source<1) throw new DomainException('Selecione uma fonte válida.');
        $amount=Money::toCents((string)($input['valor']??''));if($amount<=0) throw new DomainException('Informe um valor reservado maior que zero.');
        $this->goals->saveReservation($user,$id,$parts[0],(int)$source,$amount);
    }
    public function release(int $user,int $id,int $reservation): void { $this->goals->deactivateReservation($user,$id,$reservation); }
    public function reactivateReservation(int $user,int $id,int $reservation): void { $this->goals->reactivateReservation($user,$id,$reservation); }
    public function reactivate(int $user,int $id): void { $this->goals->reactivate($user,$id); }
    public function updateCurrent(int $user,int $id,array $input): void { if(!$this->goals->updateCurrent($user,$id,Money::toCents((string)($input['valor_atual']??'')))) throw new DomainException('Meta ativa não encontrada.'); }
    public function deactivate(int $user,int $id): void { $this->goals->deactivate($user,$id); }
}
