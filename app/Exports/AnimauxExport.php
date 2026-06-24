<?php

namespace App\Exports;

use App\Services\AnimalService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;

class AnimauxExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $farmId;
    protected $filters;
    private AnimalService $animalService;

    public function __construct(string $farmId, array $filters = [], AnimalService $animalService = null)
    {
        $this->farmId = $farmId;
        $this->filters = $filters;
        $this->animalService = $animalService ?? app(AnimalService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $filters = array_merge(['farm_id' => $this->farmId], $this->filters);
        return $this->animalService->getAll($filters);
    }

    public function headings(): array
    {
        return [
            'Numéro identification',
            'Nom',
            'Espèce',
            'Race',
            'Sexe',
            'Date naissance',
            'Poids (kg)',
            'Statut',
            'Lot',
            'Date création',
        ];
    }

    public function map($animal): array
    {
        return [
            $animal->numero_identification,
            $animal->nom,
            $animal->espece->nom ?? 'Non défini',
            $animal->race,
            $animal->sexe,
            $animal->date_naissance ? $animal->date_naissance->format('d/m/Y') : 'Non défini',
            $animal->poids,
            $animal->statut,
            $animal->lot->nom_lot ?? 'Non assigné',
            $animal->created_at->format('d/m/Y'),
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
