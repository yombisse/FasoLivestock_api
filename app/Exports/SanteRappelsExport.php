<?php

namespace App\Exports;

use App\Services\SanteRappelService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;

class SanteRappelsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $farmId;
    protected $filters;
    private SanteRappelService $santeRappelService;

    public function __construct(string $farmId, array $filters = [], SanteRappelService $santeRappelService = null)
    {
        $this->farmId = $farmId;
        $this->filters = $filters;
        $this->santeRappelService = $santeRappelService ?? app(SanteRappelService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $filters = array_merge(['farm_id' => $this->farmId], $this->filters);
        return $this->santeRappelService->getAll($filters);
    }

    public function headings(): array
    {
        return [
            'Animal',
            'Type rappel',
            'Date prévue',
            'Date réalisée',
            'Statut',
            'Note',
        ];
    }

    public function map($rappel): array
    {
        return [
            $rappel->animal ? ($rappel->animal->nom ?? $rappel->animal->numero_identification) : 'N/A',
            $rappel->type_rappel,
            $rappel->date_prevue->format('d/m/Y'),
            $rappel->date_realisee ? $rappel->date_realisee->format('d/m/Y') : 'Non réalisé',
            $rappel->statut,
            $rappel->note,
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
}
