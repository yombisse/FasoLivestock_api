<?php

namespace App\Exports;

use App\Services\NaissanceService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;

class NaissancesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $farmId;
    protected $filters;
    private NaissanceService $naissanceService;

    public function __construct(string $farmId, array $filters = [], NaissanceService $naissanceService = null)
    {
        $this->farmId = $farmId;
        $this->filters = $filters;
        $this->naissanceService = $naissanceService ?? app(NaissanceService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $filters = array_merge(['farm_id' => $this->farmId], $this->filters);
        return $this->naissanceService->getAll($filters);
    }

    public function headings(): array
    {
        return [
            'Date naissance',
            'Mère',
            'Nombre petits',
            'Poids naissance (kg)',
            'Date saillie',
            'Date mise bas prévue',
            'Observation',
        ];
    }

    public function map($naissance): array
    {
        return [
            $naissance->date_naissance->format('d/m/Y'),
            $naissance->mother ? ($naissance->mother->nom ?? $naissance->mother->numero_identification) : 'N/A',
            $naissance->nombre_petits,
            $naissance->poids_naissance,
            $naissance->date_saillie ? $naissance->date_saillie->format('d/m/Y') : 'Non défini',
            $naissance->date_mise_bas_prevue ? $naissance->date_mise_bas_prevue->format('d/m/Y') : 'Non défini',
            $naissance->observation,
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
