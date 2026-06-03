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
        $this->apiUrl = rtrim(env('HOWEN_API_URL'), '/');
        $this->username = env('HOWEN_USERNAME');
        $this->password = env('HOWEN_PASSWORD');
    }

    /**
     * Authenticate with Howen API
     * Endpoint: POST /user/login.action
     * Response: {"status":10000, "msg":"Success", "data":{"token":"xxxxxxxxxxxx"}}
     */
    public function authenticate()
    {
        try {
            // Password harus di-MD5 untuk login
            $passwordMd5 = md5($this->password);
            
            $response = $this->client->post("{$this->apiUrl}/user/login.action", [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $passwordMd5,
                ],
                'timeout' => 10,
                'verify' => false,
            ]);

            $data = json_decode($response->getBody(), true);

            \Log::info('Howen API Response', ['data' => $data]);

            if (isset($data['status']) && $data['status'] == 10000 && isset($data['data']['token'])) {
                $token = $data['data']['token'];
                
                // Simpan token ke database
                $expiresAt = now()->addMinutes(30);
                
                ApiToken::updateOrCreate(
                    ['token' => $token],
                    ['expires_at' => $expiresAt]
                );

                // Cache token
                Cache::put('howen_token', $token, now()->addMinutes(28));

                Log::info('Howen authentication successful', ['token' => substr($token, 0, 10) . '...']);

                return $token;
            } else {
                Log::error('Howen authentication failed', $data);
                throw new \Exception('Authentication failed: ' . ($data['msg'] ?? 'Unknown error'));
            }

        } catch (GuzzleException $e) {
            Log::error('Howen API request failed', ['error' => $e->getMessage()]);
            throw $e;
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
