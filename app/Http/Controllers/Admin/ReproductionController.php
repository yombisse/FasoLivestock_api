<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ReproductionApiService;
use Illuminate\Http\Request;

class ReproductionController extends Controller
{
    public function __construct(
        private ReproductionApiService $reproductionApi
    ) {}

    /**
     * Dashboard de reproduction
     */
    public function dashboard()
    {
        $response = $this->reproductionApi->dashboard();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.reproduction.dashboard', [
            'dashboard' => $response->data ?? [],
        ]);
    }

    /**
     * Prévisions de reproduction
     */
    public function forecast()
    {
        $response = $this->reproductionApi->forecast();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.reproduction.forecast', [
            'forecast' => $response->data ?? [],
        ]);
    }

    /**
     * Historique reproductif d'un animal
     */
    public function historiqueAnimal(string $animalId)
    {
        $response = $this->reproductionApi->historiqueAnimal($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.reproduction.historique-animal', [
            'animal'      => $response->data['animal'] ?? [],
            'historique'  => $response->data['historique'] ?? [],
        ]);
    }

    /**
     * Statistiques reproductives d'un animal
     */
    public function statistiquesAnimal(string $animalId)
    {
        $response = $this->reproductionApi->statistiquesAnimal($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.reproduction.statistiques-animal', [
            'animal'         => $response->data['animal'] ?? [],
            'statistiques'   => $response->data['statistiques'] ?? [],
        ]);
    }

    /**
     * Gestion des erreurs API
     */
    private function handleApiError($response)
    {
        if (($response->status ?? 500) === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Session expirée.');
        }

        return redirect()->back()->with(
            'error',
            $response->message ?? 'Erreur serveur.'
        );
    }
}
