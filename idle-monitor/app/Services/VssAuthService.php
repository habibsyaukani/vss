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
        $this->password = config('vss.password', '');
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
            // 1. Cek DB ApiToken lebih dulu jika masih valid
            try {
                $latestToken = \App\Models\ApiToken::where('expires_at', '>', now())
                    ->orderBy('expires_at', 'desc')
                    ->first();
                if ($latestToken && !empty($latestToken->token)) {
                    Log::info('[VSS Auth] Menggunakan token valid dari DB ApiToken.');
                    return [
                        'token' => $latestToken->token,
                        'pid' => '',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore DB error
            }

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
            if ($cached && !empty($cached['token'])) {
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
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->login();
            } catch (\Exception $e) {
                $lastException = $e;
                $msg = $e->getMessage();
                if (str_contains($msg, 'frequently') || str_contains($msg, 'often') || str_contains($msg, 'fast')) {
                    Log::warning("[VSS Auth] Rate limit login detected, waiting 5 seconds before retry {$attempt}/{$maxRetries}...");
                    if ($attempt < $maxRetries) {
                        sleep(5);
                    }
                } elseif ($attempt < $maxRetries) {
                    sleep(2);
                }
            }
        }

        // Fallback: Jika login rate limited atau gagal, gunakan token terbaru dari DB jika ada
        try {
            $fallbackToken = \App\Models\ApiToken::orderBy('created_at', 'desc')->first();
            if ($fallbackToken && !empty($fallbackToken->token)) {
                Log::warning('[VSS Auth] Rate-limit login reached. Menggunakan fallback token terbaru dari DB.');
                $authData = [
                    'token' => $fallbackToken->token,
                    'pid' => '',
                ];
                // Cache fallback token selama 5 menit agar tidak spam login request
                Cache::put('vss_auth_data', $authData, now()->addMinutes(5));
                return $authData;
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        throw $lastException ?? new \RuntimeException('VSS login gagal setelah retry.');
    }

    private function login(): array
    {
        // Check if password is already MD5 hashed (32 hex characters)
        $hashedPassword = preg_match('/^[a-f0-9]{32}$/i', $this->password) 
            ? $this->password 
            : md5($this->password);
        
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

        $token = $body['data']['token'];
        $pid = $body['data']['pid'] ?? '';

        // Simpan ke DB ApiToken agar bisa dipakai lintas service
        try {
            \App\Models\ApiToken::updateOrCreate(
                ['token' => $token],
                ['expires_at' => now()->addMinutes(25)]
            );
        } catch (\Throwable $e) {
            // Ignore
        }

        Log::info('[VSS Auth] Login berhasil, token baru di-cache.');

        return [
            'token' => $token,
            'pid' => $pid,
        ];
    }
}

