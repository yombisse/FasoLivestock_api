<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ExportController;
use App\Services\Admin\FarmApiService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private ExportController $exportController;
    private FarmApiService $farmApiService;

    public function __construct(ExportController $exportController, FarmApiService $farmApiService)
    {
        $this->exportController = $exportController;
        $this->farmApiService = $farmApiService;
    }

    /**
     * Afficher la page d'index des rapports
     */
    public function index(Request $request, string $farm)
    {
        $farmsResponse = $this->farmApiService->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        // Récupérer les données de la ferme pour le banner
        $farmResponse = $this->farmApiService->find($farm);
        $farmData = $farmResponse->success ? $farmResponse->data : null;

        return view('admin.rapports.index', [
            'farms' => $farms,
            'farmId' => $farm,
            'farm' => $farmData,
        ]);
    }

    /**
     * Exporter les animaux en Excel via l'API
     */
    public function exportAnimaux(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->animaux($request);
    }

    /**
     * Exporter les transactions en Excel via l'API
     */
    public function exportTransactions(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->transactions($request);
    }

    /**
     * Exporter les rappels sanitaires en Excel via l'API
     */
    public function exportSanteRappels(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->santeRappels($request);
    }

    /**
     * Exporter les naissances en Excel via l'API
     */
    public function exportNaissances(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->naissances($request);
    }

    /**
     * Générer le rapport PDF cheptel via l'API
     */
    public function pdfCheptel(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->rapportCheptel($request);
    }

    /**
     * Générer le rapport PDF sanitaire via l'API
     */
    public function pdfSanitaire(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->rapportSanitaire($request);
    }

    /**
     * Générer le rapport PDF financier via l'API
     */
    public function pdfFinancier(Request $request, string $farm)
    {
        $request->merge(['current_farm_id' => $farm]);
        return $this->exportController->rapportFinancier($request);
    }
}
