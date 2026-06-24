<?php

namespace App\Exports;

use App\Services\FinanceTransactionService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $farmId;
    protected $filters;
    protected $totalMontant = 0;
    private FinanceTransactionService $financeTransactionService;

    public function __construct(string $farmId, array $filters = [], FinanceTransactionService $financeTransactionService = null)
    {
        $this->farmId = $farmId;
        $this->filters = $filters;
        $this->financeTransactionService = $financeTransactionService ?? app(FinanceTransactionService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $filters = array_merge(['farm_id' => $this->farmId], $this->filters);
        $transactions = $this->financeTransactionService->getAll($filters);
        
        // Calculate total for the summary row
        $this->totalMontant = $transactions->sum('montant');
        
        return $transactions;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Montant (FCFA)',
            'Catégorie',
            'Animal concerné',
            'Tiers',
            'Description',
        ];
    }

    public function map($transaction): array
    {
        $typeLabel = $transaction->type_transaction === 'ENTREE' ? 'Recette' : 'Dépense';
        $montantFormate = number_format($transaction->montant, 0, '', ' ') . ' FCFA';
        
        return [
            $transaction->date_transaction->format('d/m/Y'),
            $typeLabel,
            $montantFormate,
            $transaction->categorie->nom_categorie ?? 'Non catégorisé',
            $transaction->animal ? ($transaction->animal->nom ?? $transaction->animal->numero_identification) : 'N/A',
            $transaction->tiers ?? 'N/A',
            $transaction->description,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $sheet->getHighestDataRow();
                
                // Add total row
                $totalRow = $rowCount + 1;
                $sheet->setCellValue('C' . $totalRow, 'Total :');
                $sheet->setCellValue('D' . $totalRow, number_format($this->totalMontant, 0, '', ' ') . ' FCFA');
                
                // Style total row
                $sheet->getStyle('C' . $totalRow . ':D' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ]);
            },
        ];
    }
}
