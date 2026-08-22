<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupMysqlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vss:backup-mysql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup MySQL database to /data/backups (HDD) every 2 weeks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database   = config('database.connections.mysql.database', 'vss');
        $host       = config('database.connections.mysql.host', 'mysql');
        $port       = config('database.connections.mysql.port', '3306');
        $username   = config('database.connections.mysql.username', 'root');
        $password   = config('database.connections.mysql.password', '');

        // Backup directory on HDD (/data partition = /dev/sdb1)
        $backupDir  = '/data/backups/mysql';
        $timestamp  = now()->format('Y-m-d_H-i-s');
        $filename   = "backup_{$database}_{$timestamp}.sql.gz";
        $filepath   = "{$backupDir}/{$filename}";

        $this->info("=== MySQL Backup Started ===");
        $this->info("Database : {$database}");
        $this->info("Target   : {$filepath}");

        // Create backup directory if not exists
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
            $this->info("Created backup directory: {$backupDir}");
        }

        // Build mysqldump command (runs inside the container via env vars)
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump -h%s -P%s -u%s --single-transaction --quick --lock-tables=false %s | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
            $this->error("Backup FAILED! Return code: {$returnCode}");
            Log::error('[MySQLBackup] Backup failed', [
                'database'    => $database,
                'filepath'    => $filepath,
                'return_code' => $returnCode,
                'output'      => implode("\n", $output),
            ]);
            return Command::FAILURE;
        }

        $sizeMb = round(filesize($filepath) / 1024 / 1024, 2);
        $this->info("Backup SUCCESS! File size: {$sizeMb} MB");

        // Delete backups older than 3 months to prevent HDD getting full
        $this->deleteOldBackups($backupDir);

        Log::info('[MySQLBackup] Backup completed successfully', [
            'database' => $database,
            'filepath' => $filepath,
            'size_mb'  => $sizeMb,
        ]);

        $this->info("=== MySQL Backup Completed ===");
        return Command::SUCCESS;
    }

    /**
     * Delete backup files older than 3 months from the backup directory.
     */
    private function deleteOldBackups(string $backupDir): void
    {
        $files = glob("{$backupDir}/backup_*.sql.gz");
        $cutoff = now()->subMonths(3)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
                $this->line("Deleted old backup: " . basename($file));
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup(s) older than 3 months.");
        } else {
            $this->info("No old backups to clean.");
        }
    }
}
