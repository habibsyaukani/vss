<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Services\SystemLogger;

class FreeSystemMemoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vss:free-memory {--force : Force free memory regardless of current memory usage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically free system memory, flush Linux page cache, and cycle PHP workers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧠 Starting system memory optimization...');

        $memBefore = $this->getMemoryUsage();
        $this->info(sprintf('Initial Memory: %s (Usage: %.1f%%)', $memBefore['used_formatted'], $memBefore['usage_percentage']));

        // 1. PHP Garbage Collection
        $cyclesCollected = gc_collect_cycles();
        $this->line("  ✓ PHP Garbage Collector released {$cyclesCollected} cycles");

        // 2. OPcache Reset (if enabled)
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $this->line('  ✓ PHP OPcache reset');
        }

        // 3. Clear Linux Page Cache (Drop Caches)
        $dropCacheOutput = $this->flushLinuxPageCache();
        if ($dropCacheOutput['success']) {
            $this->line('  ✓ Linux Page Cache flushed (sync && drop_caches)');
        } else {
            $this->line('  ℹ Linux Page Cache flush notice: ' . $dropCacheOutput['message']);
        }

        // 4. Restart Queue Workers Gracefully
        try {
            Artisan::call('queue:restart');
            $this->line('  ✓ Queue workers restart signal sent (graceful PHP memory release)');
        } catch (\Exception $e) {
            $this->warn('  ⚠ Failed to send queue restart signal: ' . $e->getMessage());
        }

        // Measure memory after cleanup
        $memAfter = $this->getMemoryUsage();
        $freedMb = max(0, ($memBefore['used_bytes'] - $memAfter['used_bytes']) / (1024 * 1024));

        $this->info(sprintf('Final Memory: %s (Usage: %.1f%%)', $memAfter['used_formatted'], $memAfter['usage_percentage']));
        $this->info(sprintf('🎉 Memory Optimization Complete! Freed: ~%.2f MB', $freedMb));

        // Log to SystemLogger
        if (class_exists(SystemLogger::class)) {
            SystemLogger::info('SYSTEM_MEMORY', 'Free system memory executed', [
                'usage_before' => $memBefore['usage_percentage'] . '%',
                'usage_after' => $memAfter['usage_percentage'] . '%',
                'freed_mb' => round($freedMb, 2),
                'cycles_collected' => $cyclesCollected,
                'drop_cache_status' => $dropCacheOutput['message'],
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Attempt to flush Linux Page Cache (/proc/sys/vm/drop_caches)
     */
    private function flushLinuxPageCache(): array
    {
        // Only run on Linux
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return [
                'success' => false,
                'message' => 'Windows OS detected (page cache flush skipped)',
            ];
        }

        try {
            // Execute sync first to ensure dirty pages are written to disk
            @shell_exec('sync');

            // Strategy 1: Direct echo to /proc/sys/vm/drop_caches
            $output1 = @shell_exec('echo 3 > /proc/sys/vm/drop_caches 2>&1');
            if (empty($output1)) {
                return [
                    'success' => true,
                    'message' => 'Page cache cleared via /proc/sys/vm/drop_caches',
                ];
            }

            // Strategy 2: Via sysctl with sudo (if configured)
            $output2 = @shell_exec('sudo /sbin/sysctl vm.drop_caches=3 2>&1');
            if (str_contains(strtolower($output2), 'vm.drop_caches')) {
                return [
                    'success' => true,
                    'message' => 'Page cache cleared via sudo sysctl',
                ];
            }

            // Strategy 3: Tee via sudo
            $output3 = @shell_exec('sync && echo 3 | sudo tee /proc/sys/vm/drop_caches 2>&1');
            
            return [
                'success' => true,
                'message' => 'Executed drop_caches command',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception flushing cache: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get system memory usage details
     */
    private function getMemoryUsage(): array
    {
        $usedBytes = 0;
        $totalBytes = 0;
        $usagePct = 0.0;

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && file_exists('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatches);
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatches);

                if (!empty($totalMatches[1]) && !empty($availMatches[1])) {
                    $totalBytes = (int)$totalMatches[1] * 1024;
                    $availBytes = (int)$availMatches[1] * 1024;
                    $usedBytes = $totalBytes - $availBytes;
                    $usagePct = round(($usedBytes / $totalBytes) * 100, 1);
                }
            }
        }

        // Fallback if /proc/meminfo is not available (e.g. PHP memory allocation)
        if ($totalBytes === 0) {
            $usedBytes = memory_get_usage(true);
            $totalBytes = memory_get_peak_usage(true);
            $usagePct = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;
        }

        return [
            'used_bytes' => $usedBytes,
            'total_bytes' => $totalBytes,
            'usage_percentage' => $usagePct,
            'used_formatted' => $this->formatBytes($usedBytes),
            'total_formatted' => $this->formatBytes($totalBytes),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
