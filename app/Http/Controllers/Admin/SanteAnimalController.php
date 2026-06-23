<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SanteAnimalApiService;
use Illuminate\Http\Request;

class SanteAnimalController extends Controller
{
    public function __construct(
        private SanteAnimalApiService $santeAnimalApi
    ) {}

    /**
     * Historique médical d'un animal
     */
    public function historiqueMedical(string $animalId)
    {
        $response = $this->santeAnimalApi->historiqueMedical($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante.historique-medical', [
            'animal'     => $response->data['animal'] ?? [],
            'historique' => $response->data['historique'] ?? [],
        ]);
    }

    /**
     * Statistiques sanitaires d'un animal
     */
    public function statistiquesSanitaires(string $animalId)
    {
        $response = $this->santeAnimalApi->statistiquesSanitaires($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante.statistiques-sanitaires', [
            'animal'         => $response->data['animal'] ?? [],
            'statistiques'   => $response->data['statistiques'] ?? [],
        ]);
    }

    /**
     * Résumé sanitaire de la ferme
     */
    public function resumeFerme()
    {
        $response = $this->santeAnimalApi->resumeFerme();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante.resume-ferme', [
            'resume' => $response->data ?? [],
        ]);
    }

    /**
     * Alertes sanitaires de la ferme
     */
    public function alertesFerme()
    {
        $response = $this->santeAnimalApi->alertesFerme();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante.alertes-ferme', [
            'alertes' => $response->data['alertes'] ?? [],
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
