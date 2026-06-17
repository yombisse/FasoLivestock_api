<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuthApiService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthApiService $api  // ← service dédié auth
    ) {}

    public function showLogin()
    {
        if (session('admin_token')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
        {
            $request->validate([
                'login'    => 'required|string',
                'password' => 'required|string',
            ]);

            $response = $this->api->login(
                $request->login,
                $request->password
            );

            if (!$response['success']) {
                $message = $response['data']['message'] ?? 'Identifiants incorrects.';
                return back()->with('error', $message)->withInput();
            }

            $data = $response['data']['data'];

            // ─── Plus de 2FA — token direct ───────────────────────
            session([
                'admin_token'            => $data['token'],
                'admin_token_expires_at' => now()->addHours(8),
                'admin_user'             => $data['user'],
            ]);

            return redirect()->route('admin.dashboard')
                ->with('success', 'Bienvenue, ' . $data['user']['name'] . ' !');
        }
    public function show2fa()
    {
        if (!session('2fa_verification_id')) {
            return redirect()->route('admin.login');
        }
        return view('admin.auth.verify-2fa');
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|string',
            'code'            => 'required|digits:6',
        ]);

        $response = $this->api->verify2fa(
            $request->verification_id,
            $request->code
        );

        if (!$response['success']) {
            return back()->with('error', $response['data']['message'] ?? 'Code invalide.');
        }

        $data = $response['data']['data'];

        session([
            'admin_token'            => $data['token'],
            'admin_token_expires_at' => now()->addHours(8),
            'admin_user'             => $data['user'],
        ]);

        session()->forget(['2fa_verification_id', '2fa_identifier']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Bienvenue, ' . $data['user']['name'] . ' !');
    }

    public function logout()
    {
        $this->api->logout();

        session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

        return redirect()->route('admin.login')
            ->with('success', 'Vous avez été déconnecté.');
    }
}