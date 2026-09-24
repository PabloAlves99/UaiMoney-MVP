<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

final class PublicAccountExportService
{
    public function csv(array $entries): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) throw new RuntimeException('Não foi possível gerar o CSV.');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Data', 'Descrição', 'Tipo', 'Valor'], ';');
        foreach ($entries as $entry) {
            $value = (int) $entry['valor_centavos'];
            fputcsv($stream, [
                date('d/m/Y', strtotime((string) $entry['data'])),
                $this->safeSpreadsheetText((string) $entry['descricao']),
                $value > 0 ? 'Entrada' : 'Saída',
                number_format($value / 100, 2, ',', '.'),
            ], ';');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);
        if ($content === false) throw new RuntimeException('Não foi possível gerar o CSV.');
        return $content;
    }

    public function excel(string $accountName, array $entries, array $totals, string $filter): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Extrato compartilhado');
        $sheet->setCellValueExplicit('A1', 'UaiMoney - Extrato compartilhado', DataType::TYPE_STRING);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        foreach ([['A3','Conta'],['B3',$accountName],['A4','Filtros'],['B4',$filter],['A5','Gerado em'],['B5',date('d/m/Y H:i')]] as [$cell,$value]) {
            $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
        }
        $sheet->setCellValueExplicit('A7', 'Entradas', DataType::TYPE_STRING);
        $sheet->setCellValue('B7', (int) $totals['entradas'] / 100);
        $sheet->setCellValueExplicit('C7', 'Saídas', DataType::TYPE_STRING);
        $sheet->setCellValue('D7', (int) $totals['saidas'] / 100);
        $sheet->getStyle('B7:D7')->getNumberFormat()->setFormatCode('R$ #,##0.00;[Red]-R$ #,##0.00');
        $headers = ['Data', 'Descrição', 'Tipo', 'Valor'];
        foreach ($headers as $index => $header) $sheet->setCellValueExplicit(chr(65+$index).'9', $header, DataType::TYPE_STRING);
        $sheet->getStyle('A9:D9')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A9:D9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
        $row = 10;
        foreach ($entries as $entry) {
            $value = (int) $entry['valor_centavos'];
            $sheet->setCellValueExplicit('A'.$row, date('d/m/Y', strtotime((string)$entry['data'])), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, (string)$entry['descricao'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$row, $value > 0 ? 'Entrada' : 'Saída', DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$row, $value / 100);
            $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('R$ #,##0.00;[Red]-R$ #,##0.00');
            $row++;
        }
        $last = max(9, $row-1);
        $sheet->setAutoFilter("A9:D{$last}");
        $sheet->freezePane('A10');
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getStyle("B10:B{$last}")->getAlignment()->setWrapText(true);
        $temporary = tempnam(sys_get_temp_dir(), 'uaimoney-public-');
        if ($temporary === false) throw new RuntimeException('Não foi possível gerar o Excel.');
        try {
            (new Xlsx($spreadsheet))->save($temporary);
            $content = file_get_contents($temporary);
        } finally {
            @unlink($temporary);
            $spreadsheet->disconnectWorksheets();
        }
        if ($content === false) throw new RuntimeException('Não foi possível gerar o Excel.');
        return $content;
    }

    public function pdf(string $accountName, array $entries, array $totals, string $filter): string
    {
        $rows = '';
        foreach ($entries as $entry) {
            $value = (int) $entry['valor_centavos'];
            $rows .= '<tr><td>'.date('d/m/Y', strtotime((string)$entry['data'])).'</td><td>'.$this->e((string)$entry['descricao']).'</td><td>'.($value>0?'Entrada':'Saída').'</td><td class="money">'.$this->e(Money::format($value)).'</td></tr>';
        }
        if ($rows === '') $rows = '<tr><td colspan="4" class="empty">Nenhuma movimentação encontrada.</td></tr>';
        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><style>@page{margin:14mm}body{font-family:"DejaVu Sans",sans-serif;color:#172033;font-size:10px}h1{margin:0 0 4px}.muted{color:#64748b}.summary{display:block;margin:16px 0;font-size:12px}.summary strong{margin-right:24px}table{width:100%;border-collapse:collapse}th{background:#0f172a;color:#fff;text-align:left;padding:7px}td{border:1px solid #dbe3ee;padding:7px}.money{text-align:right;white-space:nowrap}.empty{text-align:center}</style></head><body><strong>UaiMoney</strong><h1>'.$this->e($accountName).'</h1><div class="muted">Extrato compartilhado · '.$this->e($filter).' · gerado em '.date('d/m/Y H:i').'</div><div class="summary"><strong>Entradas: '.$this->e(Money::format((int)$totals['entradas'])).'</strong><strong>Saídas: '.$this->e(Money::format((int)$totals['saidas'])).'</strong></div><table><thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th></tr></thead><tbody>'.$rows.'</tbody></table></body></html>';
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function safeSpreadsheetText(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
