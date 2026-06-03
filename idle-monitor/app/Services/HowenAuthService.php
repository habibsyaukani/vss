<?php

namespace App\Services;

use App\Models\ApiToken;
use Illuminate\Support\Facades\Cache;

class HowenAuthService
{
    /**
     * Authenticate with Howen API
     */
    public function authenticate()
    {
        // TODO: Implement Howen authentication logic
        // Call Howen API login endpoint
    }

    /**
     * Refresh authentication token
     */
    public function refreshToken()
    {
        // Check if token still valid
        $token = Cache::get('howen_token');
        
        if (!$token || $this->isTokenExpired($token)) {
            // Call authenticate to get new token
            $newToken = $this->authenticate();
            Cache::put('howen_token', $newToken, now()->addMinutes(30));
            return $newToken;
        }
        
        return $token;
    }

    /**
     * Validate token
     */
    public function validateToken($token)
    {
        // TODO: Implement token validation logic
        $apiToken = ApiToken::where('token', $token)->first();
        
        if (!$apiToken) {
            return false;
        }
        
        return !($apiToken->expires_at && $apiToken->expires_at->isPast());
    }

    /**
     * Check if token is expired
     */
    private function isTokenExpired($token)
    {
        $apiToken = ApiToken::where('token', $token)->first();
        
        if (!$apiToken || !$apiToken->expires_at) {
            return true;
        }
        
        return $apiToken->expires_at->isPast();
    }
}
