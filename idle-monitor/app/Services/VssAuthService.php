<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VssAuthService
{
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl  = config('vss.base_url', 'http://vss.ptdigital.co.id');
        $this->username = config('vss.username');
        $this->password = config('vss.password'); // sudah MD5 di config/env
    }

    /**
     * Ambil token VSS. Di-cache 25 menit (token VSS expire 30 menit).
     */
    public function getToken(): string
    {
        return $this->getAuthData()['token'] ?? '';
    }

    /**
     * Ambil data auth lengkap (token & pid) untuk WebSocket.
     */
    public function getAuthData(): array
    {
        return Cache::remember('vss_auth_data', now()->addMinutes(25), function () {
            return $this->login();
        });
    }

    /**
     * Paksa login ulang (misalnya setelah dapat error 10004 / not logged in).
     */
    public function refreshToken(): string
    {
        Cache::forget('vss_auth_data');
        return $this->getToken();
    }

    private function login(): array
    {
        // MD5 hash password before sending (VSS API requirement)
        $hashedPassword = md5($this->password);
        
        $response = Http::withOptions([
            'verify' => false, // Disable SSL verification for development
        ])->timeout(15)->post("{$this->baseUrl}/vss/user/apiLogin.action", [
            'username' => $this->username,
            'password' => $hashedPassword,
        ]);

        $body = $response->json();

        if (($body['status'] ?? 0) !== 10000) {
            throw new \RuntimeException('VSS login gagal: ' . ($body['msg'] ?? 'unknown'));
        }

        Log::info('[VSS Auth] Login berhasil, token baru di-cache.');

        return [
            'token' => $body['data']['token'],
            'pid' => $body['data']['pid'] ?? '',
        ];
    }
}
