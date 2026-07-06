<?php

namespace App\Services;

use App\Models\ApiToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HowenAuthService
{
    private $client;
    private $apiUrl;
    private $username;
    private $password;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = rtrim(config('vss.howen_api_url'), '/');
        $this->username = config('vss.howen_username');
        $this->password = config('vss.howen_password');
    }

    /**
     * Authenticate with Howen API
     * Endpoint: POST /user/login.action
     * Try multiple password encoding methods
     */
    public function authenticate()
    {
        try {
            // Try berbagai format password
            $passwordAttempts = [
                'plain' => $this->password,
                'md5' => md5($this->password),
                'md5_lower' => strtolower(md5($this->password)),
                'md5_upper' => strtoupper(md5($this->password)),
            ];

            foreach ($passwordAttempts as $method => $password) {
                Log::info("Howen Auth Attempt ({$method})", [
                    'url' => "{$this->apiUrl}/user/apiLogin.action",
                    'username' => $this->username,
                ]);
                
                try {
                    $response = $this->client->post("{$this->apiUrl}/user/apiLogin.action", [
                        'form_params' => [
                            'username' => $this->username,
                            'password' => $password,
                        ],
                        'timeout' => 10,
                        'verify' => false,
                    ]);

                    $responseBody = $response->getBody()->getContents();
                    $data = json_decode($responseBody, true);

                    Log::info("Response ({$method})", ['status' => $data['status'] ?? null]);

                    // Success condition
                    if (isset($data['status']) && ($data['status'] == 10000 || $data['status'] === 10000)) {
                        if (isset($data['data']['token']) && !empty($data['data']['token'])) {
                            $token = $data['data']['token'];
                            
                            $expiresAt = now()->addMinutes(30);
                            ApiToken::updateOrCreate(
                                ['token' => $token],
                                ['expires_at' => $expiresAt]
                            );

                            Cache::put('howen_token', $token, now()->addMinutes(28));
                            Log::info("Howen authentication SUCCESS with {$method}", ['token' => substr($token, 0, 10) . '...']);

                            return $token;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Method {$method} failed: " . $e->getMessage());
                    continue;
                }
            }

            // Semua method gagal
            Log::error('Howen authentication failed on all attempts', [
                'username' => $this->username,
                'last_response' => $data ?? null,
            ]);
            
            throw new \Exception('Authentication failed: Username or password may be incorrect. Please verify credentials.');

        } catch (\Exception $e) {
            Log::error('Howen auth exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Refresh authentication token
     * Check if token still valid, if not get new token
     */
    public function refreshToken()
    {
        $token = Cache::get('howen_token');
        
        if (!$token) {
            Log::info('Token not in cache, authenticating...');
            return $this->authenticate();
        }

        // Check if token in database is still valid
        $apiToken = ApiToken::where('token', $token)->first();
        
        if (!$apiToken || ($apiToken->expires_at && $apiToken->expires_at->isPast())) {
            Log::info('Token expired, refreshing...');
            return $this->authenticate();
        }

        Log::info('Token still valid, using cached token');
        return $token;
    }

    /**
     * Get valid token (use cached or refresh)
     */
    public function getToken()
    {
        try {
            return $this->refreshToken();
        } catch (\Exception $e) {
            Log::error('Failed to get token', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Test authentication
     */
    public function testAuth()
    {
        try {
            $token = $this->authenticate();
            return [
                'success' => true,
                'message' => 'Authentication successful',
                'token' => substr($token, 0, 20) . '...',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
