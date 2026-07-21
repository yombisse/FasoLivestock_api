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
    public function index()
    {
        $farmsResponse = $this->farmApiService->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];
        
        return view('admin.rapports.index', compact('farms'));
    }

    /**
     * Exporter les animaux en Excel via l'API
     */
    public function exportAnimaux(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->animaux($request);
    }

    /**
     * Exporter les transactions en Excel via l'API
     */
    public function exportTransactions(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->transactions($request);
    }

    /**
     * Exporter les rappels sanitaires en Excel via l'API
     */
    public function exportSanteRappels(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->santeRappels($request);
    }

    /**
     * Exporter les naissances en Excel via l'API
     */
    public function exportNaissances(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->naissances($request);
    }

    /**
     * Générer le rapport PDF cheptel via l'API
     */
    public function pdfCheptel(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->rapportCheptel($request);
    }

    /**
     * Générer le rapport PDF sanitaire via l'API
     */
    public function pdfSanitaire(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->rapportSanitaire($request);
    }

    /**
     * Générer le rapport PDF financier via l'API
     */
    public function pdfFinancier(Request $request)
    {
        $farmId = $request->query('farm_id') ?: session('current_farm_id');
        $request->merge(['current_farm_id' => $farmId]);
        return $this->exportController->rapportFinancier($request);
    }
}
