<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TracksolidApiService
{
    private string $apiUrl;
    private string $appKey;
    private string $appSecret;
    private string $username;
    private string $passwordMd5;

    public function __construct()
    {
        $this->apiUrl = trim(env('TRACKSOLID_API_URL', 'http://open.10000track.com/route/rest'));
        $this->appKey = trim(env('TRACKSOLID_APP_KEY', ''));
        $this->appSecret = trim(env('TRACKSOLID_APP_SECRET', ''));
        $this->username = trim(env('TRACKSOLID_USERNAME', ''));
        $this->passwordMd5 = trim(env('TRACKSOLID_PASSWORD_MD5', ''));
    }

    /**
     * Call any Tracksolid API endpoint automatically handling token and signatures.
     */
    public function callApi(string $method, array $params = []): array
    {
        // Don't inject access_token if we are currently requesting the token itself
        if ($method !== 'jimi.oauth.token.get') {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get access token'];
            }
            $params['access_token'] = $token;
        }

        // Build common parameters
        $commonParams = [
            'method'      => $method,
            'timestamp'   => Carbon::now('UTC')->format('Y-m-d H:i:s'),
            'app_key'     => $this->appKey,
            'sign_method' => 'md5',
            'v'           => '1.0',
            'format'      => 'json',
        ];

        // Merge common and private parameters
        $requestParams = array_merge($commonParams, $params);

        // Generate signature
        $requestParams['sign'] = $this->generateSignature($requestParams);

        // Send POST request
        try {
            $response = Http::asForm()->post($this->apiUrl, $requestParams);
            
            if ($response->failed()) {
                return [
                    'success' => false, 
                    'message' => 'HTTP Request Failed: ' . $response->status()
                ];
            }

            $body = $response->json();
            
            if (!isset($body['code']) || $body['code'] !== 0) {
                Log::warning("[Tracksolid API] Error on {$method}: " . ($body['message'] ?? 'Unknown Error'));
                return [
                    'success' => false, 
                    'code' => $body['code'] ?? -1,
                    'message' => $body['message'] ?? 'Unknown Error'
                ];
            }

            return [
                'success' => true,
                'result'  => $body['result'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error("[Tracksolid API] Exception on {$method}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get access token (from cache or API)
     */
    public function getAccessToken(): ?string
    {
        // Check if token exists in cache
        if (Cache::has('tracksolid_access_token')) {
            return Cache::get('tracksolid_access_token');
        }

        // If not, request a new one
        $response = $this->callApi('jimi.oauth.token.get', [
            'user_id' => $this->username,
            'user_pwd_md5' => strtolower($this->passwordMd5),
            'expires_in' => 7200,
        ]);

        if ($response['success'] && isset($response['result']['accessToken'])) {
            $token = $response['result']['accessToken'];
            $expiresIn = (int) ($response['result']['expiresIn'] ?? 7200);
            
            // Cache the token (subtract 60 seconds to be safe before expiration)
            $safeTtl = max(60, $expiresIn - 60);
            Cache::put('tracksolid_access_token', $token, $safeTtl);
            
            return $token;
        }

        Log::error("[Tracksolid API] Failed to obtain access token.");
        return null;
    }

    /**
     * Generate MD5 signature based on Tracksolid logic
     */
    private function generateSignature(array $params): string
    {
        // 1. Remove 'sign' parameter if exists
        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        // 2. Sort keys alphabetically
        ksort($params);

        // 3. Concatenate keyvalue without equal sign and comma
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . $value;
        }

        // 4. Append and prepend appSecret
        $fullStr = $this->appSecret . $str . $this->appSecret;

        // 5. Calculate MD5 and convert to uppercase
        $sign = strtoupper(md5($fullStr));
        
        Log::info("[Tracksolid API] Sign Payload: {$fullStr} -> {$sign}");
        
        return $sign;
    }
}
