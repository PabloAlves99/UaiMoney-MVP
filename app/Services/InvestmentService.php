<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\FinancialDate;
use App\Core\Money;
use App\Repositories\InvestmentRepository;
use DomainException;

final class InvestmentService
{
    private const TYPES = ['renda_fixa', 'renda_variavel', 'fundo', 'previdencia', 'cripto', 'outro'];

    public function __construct(private readonly InvestmentRepository $investments)
    {
    }

    public function create(int $user, array $input): void
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $type = (string) ($input['tipo'] ?? '');
        $institution = trim((string) ($input['instituicao'] ?? ''));
        $goal = trim((string) ($input['objetivo'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            throw new DomainException('Informe o nome do investimento com até 100 caracteres.');
        }
        if (!in_array($type, self::TYPES, true)) {
            throw new DomainException('Selecione um tipo de investimento válido.');
        }
        if (mb_strlen($institution) > 100 || mb_strlen($goal) > 160) {
            throw new DomainException('Instituição e objetivo estão longos demais.');
        }
        $applied = Money::toCents((string) ($input['valor_aplicado'] ?? ''));
        $current = Money::toCents((string) ($input['valor_atual'] ?? ''));
        if ($applied <= 0 || $current < 0) {
            throw new DomainException('Informe valores válidos para o investimento.');
        }
        $monthly=Money::toCents((string)($input['aporte_mensal']??'0'));
        $rate=$this->referenceRate($input);
        $id=$this->investments->create($user, [$name, $type, $institution ?: null, $goal ?: null, $applied, $current, FinancialDate::date((string) ($input['data_inicio'] ?? ''))]);
        $this->investments->updateMonthlyPlan($user,$id,$monthly,$rate);
    }

    public function deactivate(int $user, int $id): void
    {
        $this->investments->deactivate($user, $id);
    }

    public function reactivate(int $user, int $id): void { $this->investments->reactivate($user,$id); }

    public function updateDetails(int $user, int $id, array $input): void
    {
        $name=trim((string)($input['nome']??''));$type=(string)($input['tipo']??'');
        $institution=trim((string)($input['instituicao']??''));$goal=trim((string)($input['objetivo']??''));
        if ($name==='' || mb_strlen($name)>100 || !in_array($type,self::TYPES,true) || mb_strlen($institution)>100 || mb_strlen($goal)>160) throw new DomainException('Confira os dados do investimento.');
        $current=$this->investments->find($user,$id);
        $monthly=Money::toCents((string)($input['aporte_mensal']??number_format((int)($current['aporte_mensal_centavos']??0)/100,2,',','')));
        $rate=$this->referenceRate($input,(int)($current['taxa_referencia_quatro_casas']??0));
        if (!$this->investments->updateDetails($user,$id,$name,$type,$institution?:null,$goal?:null)) throw new DomainException('Investimento ativo não encontrado.');
        $this->investments->updateMonthlyPlan($user,$id,$monthly,$rate);
    }

    public function updateMonthlyPlan(int $user,int $id,array $input): void
    {
        $investment=$this->investments->find($user,$id);
        if(!$investment || !(int)$investment['ativo']) throw new DomainException('Investimento ativo não encontrado.');
        $this->investments->updateMonthlyPlan($user,$id,Money::toCents((string)($input['aporte_mensal']??'')),$this->referenceRate($input));
    }

    private function referenceRate(array $input,int $existing=0): int
    {
        if(!isset($input['taxa_referencia'])) return $existing;
        $value=str_replace(',','.',trim((string)$input['taxa_referencia']));
        if(!preg_match('/^\d{1,3}(?:\.\d{1,4})?$/',$value)||(float)$value>100) throw new DomainException('A taxa de referência deve estar entre 0% e 100%.');
        return (int)round((float)$value*10000);
    }

    public function recordEvent(int $user, int $id, array $input): void
    {
        $type=(string)($input['tipo']??'');
        if (!in_array($type,['aporte','retirada','rendimento'],true)) throw new DomainException('Selecione aporte, retirada ou rendimento.');
        $date=FinancialDate::past((string)($input['data']??''));
        $description=trim((string)($input['descricao']??''));
        if (mb_strlen($description)>160) throw new DomainException('Descrição longa demais.');
        $mode=(string)($input['modo']??'valor');
        if ($type==='rendimento' && $this->investments->hasYieldInMonth($user,$id,substr($date,0,7))) throw new DomainException('Este mês já tem rendimento confirmado. Inative o registro anterior para corrigir.');
        if ($type==='rendimento' && $mode==='percentual') {
            $raw=str_replace(',','.',trim((string)($input['percentual']??'')));
            if (!preg_match('/^-?\d{1,3}(?:\.\d{1,4})?$/',$raw) || abs((float)$raw)>100) throw new DomainException('Informe um percentual entre -100% e 100%.');
            $amount=(int)round($this->investments->balanceAt($user,$id,$date)*(float)$raw/100);
        } elseif ($type==='rendimento' && $mode==='saldo') {
            $actual=Money::toCents((string)($input['valor']??''));
            $amount=$actual-$this->investments->balanceAt($user,$id,$date);
        } else {
            $amount=$type==='rendimento' ? Money::toSignedCents((string)($input['valor']??'')) : Money::toCents((string)($input['valor']??''));
        }
        if ($amount===0 && $type!=='rendimento') throw new DomainException('O evento precisa alterar o saldo.');
        $this->investments->saveEvent($user,$id,$type,$amount,$date,$description?:null);
    }

    public function deactivateEvent(int $user,int $id,int $eventId): void { $this->investments->deactivateEvent($user,$id,$eventId); }
    public function reactivateEvent(int $user,int $id,int $eventId): void { $this->investments->reactivateEvent($user,$id,$eventId); }

    public function updateCurrentValue(int $user, int $id, array $input): void
    {
        $amount = Money::toCents((string) ($input['valor_atual'] ?? ''));
        $current=$this->investments->find($user,$id);
        if (!$current || !(int)$current['ativo']) throw new DomainException('Investimento ativo não encontrado.');
        $delta=$amount-(int)$current['valor_atual_centavos'];
        if ($delta===0) return;
        $this->investments->saveEvent($user,$id,'ajuste',$delta,date('Y-m-d'),'Saldo confirmado pelo usuário');
    }
}
