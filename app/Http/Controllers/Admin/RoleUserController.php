<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoleUserApiService;
use Illuminate\Http\Request;

class RoleUserController extends Controller
{
    public function __construct(
        private RoleUserApiService $roleUserApi
    ) {}

    /**
     * Retourne la liste des users assignés à un rôle.
     * Sert de proxy côté web (AJAX/Fetch).
     */
    public function users(string $roleId)
    {
        $response = $this->roleUserApi->getUsers($roleId);

        return response()->json($response);
    }

    /**
     * Assigner un user à un rôle.
     * body: { user_id }
     */
    public function attach(Request $request, string $roleId)
    {
        $data = $request->validate([
            'user_id' => ['required'],
        ]);

        $response = $this->roleUserApi->attachUser($roleId, (string) $data['user_id']);

        return response()->json($response);
    }

    /**
     * Retirer un user du rôle.
     */
    public function detach(string $roleId, string $userId)
    {
        $response = $this->roleUserApi->detachUser($roleId, $userId);

        return response()->json($response);
    }
}
