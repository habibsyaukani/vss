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
            return $this->loginWithRetry();
        });
    }

    /**
     * Paksa login ulang (misalnya setelah dapat error 10004 / not logged in).
     */
    public function refreshToken(): string
    {
        // Lock refresh selama 10 detik agar tidak spam request login saat error
        if (Cache::has('vss_auth_refresh_lock')) {
            $cached = Cache::get('vss_auth_data');
            if (!empty($cached['token'])) {
                return $cached['token'];
            }
        }
        
        Cache::put('vss_auth_refresh_lock', true, 10);
        Cache::forget('vss_auth_data');
        return $this->getToken();
    }

    private function loginWithRetry(): array
    {
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->login();
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'frequently') || str_contains($e->getMessage(), 'often')) {
                    Log::warning("[VSS Auth] Rate limit login detected, waiting 5 seconds before retry {$attempt}/{$maxRetries}...");
                    sleep(5);
                } elseif ($attempt < $maxRetries) {
                    sleep(2);
                } else {
                    throw $e;
                }
            }
        }
        return $this->login();
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
