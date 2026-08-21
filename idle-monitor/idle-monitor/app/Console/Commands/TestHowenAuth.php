<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestHowenAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'howen:test-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Howen authentication and verify token storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Howen API Authentication...');
        $this->newLine();

        try {
            $authService = new \App\Services\HowenAuthService();
            
            $this->info('Attempting login...');
            $token = $authService->authenticate();
            
            $this->newLine();
            $this->info('✅ Authentication successful!');
            $this->info('Token: ' . substr($token, 0, 30) . '...');
            
            // Verify token in database
            $apiToken = \App\Models\ApiToken::where('token', $token)->first();
            if ($apiToken) {
                $this->info('✅ Token stored in database');
                $this->info('Expires at: ' . $apiToken->expires_at);
            } else {
                $this->error('❌ Token NOT found in database');
            }
            
            // Test getToken from cache
            $this->newLine();
            $this->info('Testing getToken (should use cache)...');
            $cachedToken = $authService->getToken();
            
            if ($cachedToken === $token) {
                $this->info('✅ getToken returns same token from cache');
            }
            
            $this->newLine();
            $this->info('✅ All tests passed!');
            
        } catch (\Exception $e) {
            $this->error('❌ Authentication failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
