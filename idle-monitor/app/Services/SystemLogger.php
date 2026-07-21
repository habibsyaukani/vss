<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * SystemLogger - Enhanced logging for debugging and troubleshooting
 * 
 * Logs are written to: storage/logs/system-monitor.log
 * 
 * Categories:
 * - [AUTH] API authentication issues
 * - [DATA_PULL] Data pulling failures
 * - [PROCESSING] Data processing errors
 * - [DATABASE] Database connection/query issues
 * - [API] External API errors
 */
class SystemLogger
{
    /**
     * Log successful operation
     */
    public static function success(string $category, string $message, array $context = []): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        
        $logMessage = "[{$timestamp}] ✅ [{$category}] {$message}{$contextStr}";
        
        Log::channel('single')->info($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log informational message
     */
    public static function info(string $category, string $message, array $context = []): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        
        $logMessage = "[{$timestamp}] ℹ️  [{$category}] {$message}{$contextStr}";
        
        Log::channel('single')->info($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log warning (non-critical issue)
     */
    public static function warning(string $category, string $message, array $context = []): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        
        $logMessage = "[{$timestamp}] ⚠️  [{$category}] {$message}{$contextStr}";
        
        Log::channel('single')->warning($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log error with troubleshooting hints
     */
    public static function error(
        string $category,
        string $message,
        array $context = [],
        string $troubleshooting = '',
        ?\Throwable $exception = null
    ): void {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $exceptionStr = $exception ? ' | Exception: ' . $exception->getMessage() : '';
        $troubleshootingStr = $troubleshooting ? "\n   💡 TROUBLESHOOTING: {$troubleshooting}" : '';
        
        $logMessage = "[{$timestamp}] ❌ [{$category}] {$message}{$contextStr}{$exceptionStr}{$troubleshootingStr}";
        
        Log::channel('single')->error($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log job start
     */
    public static function jobStart(string $jobName, array $params = []): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $paramsStr = !empty($params) ? ' with params: ' . json_encode($params) : '';
        
        $logMessage = "[{$timestamp}] 🚀 [JOB_START] {$jobName}{$paramsStr}";
        
        Log::channel('single')->info($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log job completion
     */
    public static function jobComplete(string $jobName, array $stats = []): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $statsStr = !empty($stats) ? ' | Stats: ' . json_encode($stats) : '';
        
        $logMessage = "[{$timestamp}] ✅ [JOB_COMPLETE] {$jobName}{$statsStr}";
        
        Log::channel('single')->info($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Log job failure
     */
    public static function jobFailed(string $jobName, string $reason, ?\Throwable $exception = null): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $exceptionStr = $exception ? ' | Exception: ' . $exception->getMessage() : '';
        
        $logMessage = "[{$timestamp}] ❌ [JOB_FAILED] {$jobName} | Reason: {$reason}{$exceptionStr}";
        
        Log::channel('single')->error($logMessage);
        static::writeToCustomLog($logMessage);
    }

    /**
     * Write to custom log file for easier reading
     */
    private static function writeToCustomLog(string $message): void
    {
        $logPath = storage_path('logs/system-monitor.log');
        
        // Ensure log directory exists
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Append to log file
        file_put_contents($logPath, $message . "\n", FILE_APPEND);
    }

    /**
     * Common troubleshooting hints
     */
    public static function hints(): array
    {
        return [
            'auth_failed' => 'Check HOWEN_USERNAME and HOWEN_PASSWORD in .env file. Run: php artisan howen:test-auth',
            'api_timeout' => 'API server might be slow or down. Check internet connection. Try again later.',
            'api_rate_limit' => 'Too many requests to API. Wait a few minutes. Reduce concurrency in scheduler.',
            'database_connection' => 'Check DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env. Ensure MySQL is running.',
            'database_query' => 'Database query failed. Check storage/logs/laravel.log for SQL error details.',
            'no_data_pulled' => 'API returned empty result. Check date range. Data might not exist for that period.',
            'job_stuck' => 'Job is stuck. Stop queue worker and restart: php artisan queue:restart',
            'memory_limit' => 'Increase memory_limit in php.ini or add --memory=512M to command.',
        ];
    }
}
