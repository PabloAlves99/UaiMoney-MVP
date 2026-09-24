<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

final class AccountExportService
{
    private const ACCOUNT_TYPES = [
        'corrente' => 'Conta corrente',
        'poupanca' => 'Poupança',
        'dinheiro' => 'Dinheiro',
        'carteira_digital' => 'Carteira digital',
        'outro' => 'Outro',
    ];

    private const PAYMENT_METHODS = [
        'pix' => 'Pix',
        'dinheiro' => 'Dinheiro',
        'debito' => 'Débito',
        'credito' => 'Crédito',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferência',
        'outro' => 'Outro',
    ];

    public function generatePdf(
        array $conta,
        array $transacoes,
        array $filters,
        array $grupos
    ): string {
        $summary = $this->summary($transacoes);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml(
            $this->pdfHtml(
                $conta,
                $transacoes,
                $filters,
                $grupos,
                $summary
            ),
            'UTF-8'
        );

        /*
         * Landscape evita esmagar as colunas.
         *
         * O número de páginas é livre.
         * Não estamos tirando screenshot da tela.
         */
        $dompdf->setPaper('A4', 'landscape');

        $dompdf->render();

        return $dompdf->output();
    }


    public function generateExcel(
        array $conta,
        array $transacoes,
        array $filters,
        array $grupos
    ): string {
        $summary = $this->summary($transacoes);

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Extrato');


        /*
        |--------------------------------------------------------------------------
        | Cabeçalho
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A1',
            'UaiMoney - Extrato da conta'
        );

        $sheet->mergeCells('A1:O1');

        $sheet
            ->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);


        /*
        |--------------------------------------------------------------------------
        | Dados da conta
        |--------------------------------------------------------------------------
        */

        $this->setText($sheet, 'A3', 'Conta');
        $this->setText(
            $sheet,
            'B3',
            (string) $conta['nome']
        );

        $this->setText(
            $sheet,
            'D3',
            'Instituição'
        );

        $this->setText(
            $sheet,
            'E3',
            (string) (
                $conta['instituicao']
                ?: 'Não informada'
            )
        );

        $this->setText(
            $sheet,
            'G3',
            'Tipo'
        );

        $this->setText(
            $sheet,
            'H3',
            $this->accountTypeLabel(
                (string) $conta['tipo']
            )
        );

        $this->setText(
            $sheet,
            'J3',
            'Data inicial'
        );

        $this->setText(
            $sheet,
            'K3',
            $this->formatDate(
                (string) $conta['saldo_inicial_em']
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Resumo
        |--------------------------------------------------------------------------
        */

        $this->setText(
            $sheet,
            'A5',
            'Saldo inicial'
        );

        $sheet->setCellValue(
            'B5',
            ((int) $conta['saldo_inicial_centavos']) / 100
        );


        $this->setText(
            $sheet,
            'D5',
            'Saldo atual'
        );

        $sheet->setCellValue(
            'E5',
            ((int) $conta['saldo_atual_centavos']) / 100
        );


        $this->setText(
            $sheet,
            'G5',
            'Receitas realizadas'
        );

        $sheet->setCellValue(
            'H5',
            $summary['receitas'] / 100
        );


        $this->setText(
            $sheet,
            'J5',
            'Despesas realizadas'
        );

        $sheet->setCellValue(
            'K5',
            $summary['despesas'] / 100
        );


        $this->setText(
            $sheet,
            'M5',
            'Pendentes'
        );

        $sheet->setCellValue(
            'N5',
            $summary['pendentes']
        );


        foreach (
            [
                'B5',
                'E5',
                'H5',
                'K5'
            ] as $moneyCell
        ) {
            $sheet
                ->getStyle($moneyCell)
                ->getNumberFormat()
                ->setFormatCode(
                    'R$ #,##0.00;[Red]-R$ #,##0.00'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        $this->setText(
            $sheet,
            'A7',
            'Filtros'
        );

        $this->setText(
            $sheet,
            'B7',
            $this->filterDescription(
                $filters,
                $grupos
            )
        );

        $sheet->mergeCells('B7:O7');


        $this->setText(
            $sheet,
            'A8',
            'Gerado em'
        );

        $this->setText(
            $sheet,
            'B8',
            date('d/m/Y H:i')
        );


        /*
        |--------------------------------------------------------------------------
        | Cabeçalho das movimentações
        |--------------------------------------------------------------------------
        */

        $headerRow = 10;

        $headers = [
            'ID',
            'Data referência',
            'Descrição',
            'Tipo',
            'Categoria',
            'Subcategoria',
            'Competência',
            'Vencimento',
            'Efetivação',
            'Status',
            'Meio de pagamento',
            'Observação',
            'Parcela',
            'Recorrência',
            'Valor',
        ];


        foreach (
            $headers as $index => $header
        ) {
            $column =
                $this->columnLetter(
                    $index + 1
                );

            $this->setText(
                $sheet,
                $column . $headerRow,
                $header
            );
        }


        $sheet
            ->getStyle(
                "A{$headerRow}:O{$headerRow}"
            )
            ->getFont()
            ->setBold(true);


        $sheet
            ->getStyle(
                "A{$headerRow}:O{$headerRow}"
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FF0F172A');


        $sheet
            ->getStyle(
                "A{$headerRow}:O{$headerRow}"
            )
            ->getFont()
            ->getColor()
            ->setARGB('FFFFFFFF');


        /*
        |--------------------------------------------------------------------------
        | Movimentações
        |--------------------------------------------------------------------------
        */

        $row =
            $headerRow + 1;


        foreach (
            $transacoes as $transacao
        ) {
            $dataReferencia =
                $transacao['data_efetivacao']
                ?? $transacao['data_vencimento']
                ?? $transacao['data_competencia'];


            $tipo =
                (string) $transacao['tipo'];


            /*
             * No Excel:
             *
             * receita = positiva
             * despesa = negativa
             *
             * O valor permanece numérico de verdade.
             */

            $signedValue =
                ((int) $transacao['valor_centavos'])
                / 100;


            if ($tipo === 'despesa') {
                $signedValue *= -1;
            }


            $parcela = '';

            if (
                !empty($transacao['numero_parcela'])
            ) {
                $parcela =
                    (string)
                    $transacao['numero_parcela'];

                if (
                    !empty($transacao['total_parcelas'])
                ) {
                    $parcela .=
                        '/'
                        . $transacao['total_parcelas'];
                }
            }


            $this->setText(
                $sheet,
                'A' . $row,
                (string) $transacao['id']
            );

            $this->setText(
                $sheet,
                'B' . $row,
                $this->formatDate(
                    (string) $dataReferencia
                )
            );

            $this->setText(
                $sheet,
                'C' . $row,
                (string)
                $transacao['descricao']
            );

            $this->setText(
                $sheet,
                'D' . $row,
                $this->transactionTypeLabel(
                    $tipo
                )
            );

            $this->setText(
                $sheet,
                'E' . $row,
                (string)
                $transacao['grupo_nome']
            );

            $this->setText(
                $sheet,
                'F' . $row,
                (string)
                $transacao['subgrupo_nome']
            );

            $this->setText(
                $sheet,
                'G' . $row,
                $this->formatDate(
                    (string)
                    $transacao['data_competencia']
                )
            );

            $this->setText(
                $sheet,
                'H' . $row,
                $this->formatDate(
                    (string)
                    $transacao['data_vencimento']
                )
            );

            $this->setText(
                $sheet,
                'I' . $row,
                $this->formatDate(
                    $transacao['data_efetivacao']
                )
            );

            $this->setText(
                $sheet,
                'J' . $row,
                $this->statusLabel(
                    (string)
                    $transacao['status']
                )
            );

            $this->setText(
                $sheet,
                'K' . $row,
                $this->paymentMethodLabel(
                    $transacao['meio_pagamento']
                )
            );

            $this->setText(
                $sheet,
                'L' . $row,
                (string) (
                    $transacao['observacao']
                    ?? ''
                )
            );

            $this->setText(
                $sheet,
                'M' . $row,
                $parcela
            );

            $this->setText(
                $sheet,
                'N' . $row,
                !empty($transacao['recorrencia_id'])
                    ? 'Sim'
                    : 'Não'
            );


            $sheet->setCellValue(
                'O' . $row,
                $signedValue
            );

            $sheet
                ->getStyle('O' . $row)
                ->getNumberFormat()
                ->setFormatCode(
                    'R$ #,##0.00;[Red]-R$ #,##0.00'
                );


            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | Formatação
        |--------------------------------------------------------------------------
        */

        $lastRow =
            max(
                $headerRow,
                $row - 1
            );


        $sheet->setAutoFilter(
            "A{$headerRow}:O{$lastRow}"
        );


        $sheet->freezePane(
            'A' . ($headerRow + 1)
        );


        if (
            $lastRow > $headerRow
        ) {
            $sheet
                ->getStyle(
                    "A{$headerRow}:O{$lastRow}"
                )
                ->getAlignment()
                ->setVertical(
                    Alignment::VERTICAL_TOP
                );

            $sheet
                ->getStyle(
                    'C'
                        . ($headerRow + 1)
                        . ":L{$lastRow}"
                )
                ->getAlignment()
                ->setWrapText(true);
        }


        foreach (
            [
                'A',
                'B',
                'D',
                'G',
                'H',
                'I',
                'J',
                'K',
                'M',
                'N',
                'O'
            ] as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(true);
        }


        /*
         * Colunas textuais possuem largura máxima
         * para evitar um Excel absurdamente largo.
         */

        $sheet
            ->getColumnDimension('C')
            ->setWidth(34);

        $sheet
            ->getColumnDimension('E')
            ->setWidth(22);

        $sheet
            ->getColumnDimension('F')
            ->setWidth(22);

        $sheet
            ->getColumnDimension('L')
            ->setWidth(42);


        /*
         * Caso seja impresso pelo Excel,
         * todas as colunas cabem na largura.
         *
         * A quantidade de páginas na vertical
         * continua ilimitada.
         */

        $sheet
            ->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            )
            ->setFitToWidth(1)
            ->setFitToHeight(0);


        $sheet
            ->getPageSetup()
            ->setFitToPage(true);


        /*
        |--------------------------------------------------------------------------
        | Geração do XLSX
        |--------------------------------------------------------------------------
        */

        $writer =
            new Xlsx(
                $spreadsheet
            );


        $tempFile =
            tempnam(
                sys_get_temp_dir(),
                'uaimoney-xlsx-'
            );


        if ($tempFile === false) {
            throw new RuntimeException(
                'Não foi possível criar o arquivo temporário do Excel.'
            );
        }


        try {

            $writer->save(
                $tempFile
            );

            $content =
                file_get_contents(
                    $tempFile
                );
        } finally {

            @unlink(
                $tempFile
            );

            $spreadsheet
                ->disconnectWorksheets();
        }


        if ($content === false) {
            throw new RuntimeException(
                'Não foi possível gerar o arquivo Excel.'
            );
        }


        return $content;
    }


    public function filename(
        array $conta,
        string $extension
    ): string {
        return sprintf(
            'uaimoney-conta-%d-%s.%s',
            (int) $conta['id'],
            date('Y-m-d'),
            $extension
        );
    }


    private function summary(
        array $transacoes
    ): array {
        $receitas = 0;
        $despesas = 0;
        $pendentes = 0;


        /*
         * Mesma regra usada atualmente
         * na apresentação dos lançamentos da conta.
         */

        foreach (
            $transacoes as $transacao
        ) {
            if (
                ($transacao['status'] ?? '')
                === 'pendente'
            ) {
                $pendentes++;
            }


            if (
                ($transacao['status'] ?? '')
                !== 'efetivada'
            ) {
                continue;
            }


            if (
                ($transacao['tipo'] ?? '')
                === 'receita'
            ) {
                $receitas +=
                    (int)
                    $transacao['valor_centavos'];
            }


            if (
                ($transacao['tipo'] ?? '')
                === 'despesa'
            ) {
                $despesas +=
                    (int)
                    $transacao['valor_centavos'];
            }
        }


        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'pendentes' => $pendentes,
        ];
    }


    private function pdfHtml(
        array $conta,
        array $transacoes,
        array $filters,
        array $grupos,
        array $summary
    ): string {
        $rows = '';


        foreach (
            $transacoes as $transacao
        ) {
            $dataReferencia =
                $transacao['data_efetivacao']
                ?? $transacao['data_vencimento']
                ?? $transacao['data_competencia'];


            $tipo =
                (string)
                $transacao['tipo'];


            $sinal =
                $tipo === 'receita'
                ? '+'
                : '-';


            $parcela = '';


            if (
                !empty($transacao['numero_parcela'])
            ) {
                $parcela =
                    ' · Parcela '
                    . $this->e(
                        (string)
                        $transacao['numero_parcela']
                    );


                if (
                    !empty($transacao['total_parcelas'])
                ) {
                    $parcela .=
                        '/'
                        . $this->e(
                            (string)
                            $transacao['total_parcelas']
                        );
                }
            }


            $observacao =
                trim(
                    (string) (
                        $transacao['observacao']
                        ?? ''
                    )
                );


            $observacaoHtml =
                $observacao !== ''
                ? '<div class="muted detail"><strong>Obs.:</strong> '
                . nl2br(
                    $this->e(
                        $observacao
                    )
                )
                . '</div>'
                : '';


            $rows .=
                '<tr>'

                . '<td>'
                . '<strong>'
                . $this->e(
                    $this->formatDate(
                        (string)
                        $dataReferencia
                    )
                )
                . '</strong>'

                . '<div class="muted detail">'
                . 'Comp.: '
                . $this->e(
                    $this->formatDate(
                        (string)
                        $transacao['data_competencia']
                    )
                )
                . '</div>'

                . '<div class="muted detail">'
                . 'Venc.: '
                . $this->e(
                    $this->formatDate(
                        (string)
                        $transacao['data_vencimento']
                    )
                )
                . '</div>'

                . '<div class="muted detail">'
                . 'Efet.: '
                . $this->e(
                    $this->formatDate(
                        $transacao['data_efetivacao']
                    )
                )
                . '</div>'

                . '</td>'


                . '<td>'

                . '<strong>'
                . $this->e(
                    (string)
                    $transacao['descricao']
                )
                . '</strong>'

                . $observacaoHtml

                . (
                    !empty($transacao['recorrencia_id'])
                    ? '<div class="muted detail">Recorrente</div>'
                    : ''
                )

                . $parcela

                . '</td>'


                . '<td>'

                . $this->e(
                    (string)
                    $transacao['grupo_nome']
                )

                . '<div class="muted detail">'

                . $this->e(
                    (string)
                    $transacao['subgrupo_nome']
                )

                . '</div>'

                . '</td>'


                . '<td>'
                . $this->e(
                    $this->transactionTypeLabel(
                        $tipo
                    )
                )
                . '</td>'


                . '<td>'
                . $this->e(
                    $this->statusLabel(
                        (string)
                        $transacao['status']
                    )
                )
                . '</td>'


                . '<td>'
                . $this->e(
                    $this->paymentMethodLabel(
                        $transacao['meio_pagamento']
                    )
                )
                . '</td>'


                . '<td class="money">'
                . $sinal
                . ' '
                . $this->e(
                    Money::format(
                        (int)
                        $transacao['valor_centavos']
                    )
                )
                . '</td>'


                . '</tr>';
        }


        if ($rows === '') {
            $rows =
                '<tr>'
                . '<td colspan="7" class="empty">'
                . 'Nenhuma movimentação encontrada para os filtros selecionados.'
                . '</td>'
                . '</tr>';
        }


        $institution =
            $conta['instituicao']
            ?: 'Não informada';


        $filterDescription =
            $this->filterDescription(
                $filters,
                $grupos
            );


        return '
<!doctype html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 12mm;
}

* {
    box-sizing: border-box;
}

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 9px;
    color: #172033;
    margin: 0;
}

h1 {
    font-size: 20px;
    margin: 0 0 3px;
}

h2 {
    font-size: 12px;
    margin: 18px 0 6px;
}

.brand {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 10px;
}

.muted {
    color: #64748b;
}

.detail {
    font-size: 7.5px;
    margin-top: 2px;
    line-height: 1.3;
}


/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

.summary {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px 0;
    margin: 12px -6px 0;
}

.summary td {
    border: 1px solid #dbe3ee;
    padding: 8px;
    width: 20%;
    vertical-align: top;
}

.summary .label {
    color: #64748b;
    font-size: 7px;
    margin-bottom: 3px;
}

.summary .value {
    font-size: 11px;
    font-weight: bold;
}


/*
|--------------------------------------------------------------------------
| Dados
|--------------------------------------------------------------------------
*/

.info {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.info td {
    border: 1px solid #e2e8f0;
    padding: 6px 8px;
    vertical-align: top;
}


/*
|--------------------------------------------------------------------------
| Movimentações
|--------------------------------------------------------------------------
*/

.movements {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}


/*
 * O cabeçalho reaparece nas páginas seguintes.
 */
.movements thead {
    display: table-header-group;
}


.movements th {
    background: #0f172a;
    color: #ffffff;
    padding: 6px;
    text-align: left;
    font-size: 7px;
}


.movements td {
    border: 1px solid #dbe3ee;
    padding: 6px;
    vertical-align: top;

    /*
     * Textos longos não estouram
     * horizontalmente o PDF.
     */
    overflow-wrap: anywhere;
    word-wrap: break-word;

    line-height: 1.35;
}


.movements th:nth-child(1),
.movements td:nth-child(1) {
    width: 14%;
}

.movements th:nth-child(2),
.movements td:nth-child(2) {
    width: 26%;
}

.movements th:nth-child(3),
.movements td:nth-child(3) {
    width: 17%;
}

.movements th:nth-child(4),
.movements td:nth-child(4) {
    width: 9%;
}

.movements th:nth-child(5),
.movements td:nth-child(5) {
    width: 10%;
}

.movements th:nth-child(6),
.movements td:nth-child(6) {
    width: 11%;
}

.movements th:nth-child(7),
.movements td:nth-child(7) {
    width: 13%;
}


.money {
    text-align: right;
    white-space: nowrap;
    font-weight: bold;
}


.empty {
    text-align: center;
    padding: 18px !important;
}


.footer {
    margin-top: 10px;
    font-size: 7px;
    color: #64748b;
}

</style>

</head>

<body>

<div class="brand">
    UaiMoney
</div>


<h1>'
            . $this->e(
                (string) $conta['nome']
            )
            . '</h1>


<div class="muted">
    Extrato da conta · gerado em '
            . $this->e(
                date('d/m/Y H:i')
            )
            . '
</div>


<table class="summary">

<tr>

<td>
    <div class="label">
        Saldo atual
    </div>

    <div class="value">'
            . $this->e(
                Money::format(
                    (int)
                    $conta['saldo_atual_centavos']
                )
            )
            . '
    </div>
</td>


<td>
    <div class="label">
        Saldo inicial
    </div>

    <div class="value">'
            . $this->e(
                Money::format(
                    (int)
                    $conta['saldo_inicial_centavos']
                )
            )
            . '
    </div>
</td>


<td>
    <div class="label">
        Receitas realizadas
    </div>

    <div class="value">'
            . $this->e(
                Money::format(
                    $summary['receitas']
                )
            )
            . '
    </div>
</td>


<td>
    <div class="label">
        Despesas realizadas
    </div>

    <div class="value">'
            . $this->e(
                Money::format(
                    $summary['despesas']
                )
            )
            . '
    </div>
</td>


<td>
    <div class="label">
        Pendentes
    </div>

    <div class="value">'
            . $this->e(
                (string)
                $summary['pendentes']
            )
            . '
    </div>
</td>

</tr>

</table>


<table class="info">

<tr>

<td>
    <strong>Instituição:</strong>
    <br>'
            . $this->e(
                (string)
                $institution
            )
            . '
</td>


<td>
    <strong>Tipo:</strong>
    <br>'
            . $this->e(
                $this->accountTypeLabel(
                    (string)
                    $conta['tipo']
                )
            )
            . '
</td>


<td>
    <strong>Data inicial:</strong>
    <br>'
            . $this->e(
                $this->formatDate(
                    (string)
                    $conta['saldo_inicial_em']
                )
            )
            . '
</td>


<td>
    <strong>Filtros:</strong>
    <br>'
            . $this->e(
                $filterDescription
            )
            . '
</td>


<td>
    <strong>Movimentações:</strong>
    <br>'
            . count($transacoes)
            . '
</td>

</tr>

</table>


<h2>
    Movimentações
</h2>


<table class="movements">

<thead>

<tr>
    <th>Datas</th>
    <th>Descrição</th>
    <th>Categoria</th>
    <th>Tipo</th>
    <th>Status</th>
    <th>Meio</th>
    <th>Valor</th>
</tr>

</thead>


<tbody>'

            . $rows

            . '</tbody>

</table>


<div class="footer">
    Os totais de receitas e despesas consideram somente lançamentos
    efetivados dentro do filtro atual, seguindo a mesma regra da tela
    da conta.
</div>


</body>

</html>';
    }


    private function filterDescription(
        array $filters,
        array $grupos
    ): string {
        $parts = [];


        if (
            ($filters['data_inicio'] ?? '')
            !== ''
        ) {
            $parts[] =
                'de '
                . $this->formatDate(
                    (string)
                    $filters['data_inicio']
                );
        }


        if (
            ($filters['data_fim'] ?? '')
            !== ''
        ) {
            $parts[] =
                'até '
                . $this->formatDate(
                    (string)
                    $filters['data_fim']
                );
        }


        if (
            ($filters['tipo'] ?? '')
            !== ''
        ) {
            $parts[] =
                'tipo: '
                . $this->transactionTypeLabel(
                    (string)
                    $filters['tipo']
                );
        }


        if (
            ($filters['status'] ?? '')
            !== ''
        ) {
            $parts[] =
                'status: '
                . $this->statusLabel(
                    (string)
                    $filters['status']
                );
        }


        if (
            !empty($filters['grupo_id'])
        ) {
            $parts[] =
                'categoria: '
                . $this->groupName(
                    (int)
                    $filters['grupo_id'],
                    $grupos
                );
        }


        return $parts === []
            ? 'Nenhum filtro aplicado'
            : implode(
                ' · ',
                $parts
            );
    }


    private function groupName(
        int $groupId,
        array $grupos
    ): string {
        foreach (
            $grupos as $grupo
        ) {
            if (
                (int) (
                    $grupo['id']
                    ?? 0
                )
                === $groupId
            ) {
                return (string)
                $grupo['nome'];
            }
        }


        return '#' . $groupId;
    }


    private function accountTypeLabel(
        string $type
    ): string {
        return self::ACCOUNT_TYPES[$type]
            ?? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $type
                )
            );
    }


    private function transactionTypeLabel(
        string $type
    ): string {
        return match ($type) {

            'receita'
            => 'Receita',

            'despesa'
            => 'Despesa',

            default
            => ucfirst($type)
        };
    }


    private function statusLabel(
        string $status
    ): string {
        return match ($status) {

            'efetivada'
            => 'Efetivada',

            'pendente'
            => 'Pendente',

            'cancelada'
            => 'Cancelada',

            default
            => ucfirst($status)
        };
    }


    private function paymentMethodLabel(
        ?string $method
    ): string {
        if (
            $method === null
            ||
            $method === ''
        ) {
            return 'Não informado';
        }


        return self::PAYMENT_METHODS[$method]
            ?? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $method
                )
            );
    }


    private function formatDate(
        ?string $date
    ): string {
        if (
            $date === null
            ||
            trim($date) === ''
        ) {
            return '—';
        }


        $timestamp =
            strtotime(
                $date
            );


        return $timestamp === false
            ? $date
            : date(
                'd/m/Y',
                $timestamp
            );
    }


    private function setText(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $cell,
        string $value
    ): void {
        /*
         * TYPE_STRING também protege contra
         * fórmula maliciosa no Excel caso uma
         * descrição comece com "=".
         */

        $sheet->setCellValueExplicit(
            $cell,
            $value,
            DataType::TYPE_STRING
        );
    }


    private function columnLetter(
        int $number
    ): string {
        $letter = '';


        while ($number > 0) {

            $number--;

            $letter =
                chr(
                    65
                        + ($number % 26)
                )
                . $letter;

            $number =
                intdiv(
                    $number,
                    26
                );
        }


        return $letter;
    }


    private function e(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES
                | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
