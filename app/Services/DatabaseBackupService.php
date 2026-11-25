<?php

namespace App\Services;

use App\Models\BackupRecord;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function create(?int $userId = null): BackupRecord
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Automated backups currently support the MySQL connection only.');
        }

        $connection = config('database.connections.mysql');
        foreach (['host', 'port', 'username', 'database'] as $key) {
            if (blank($connection[$key] ?? null)) {
                throw new RuntimeException('MySQL backup configuration is incomplete: '.$key.' is missing.');
            }
        }

        $filename = 'it-inventory-'.now()->format('Ymd-His').'-'.str()->lower(str()->random(5)).'.sql';
        $relativePath = 'backups/'.$filename;
        $absoluteDirectory = storage_path('app/private/backups');

        if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0750, true) && ! is_dir($absoluteDirectory)) {
            throw new RuntimeException('Unable to create the private backup directory.');
        }

        $absolutePath = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;
        $handle = fopen($absolutePath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to create the backup file. Check storage permissions.');
        }

        $arguments = [
            config('inventory.mysqldump_binary', 'mysqldump'),
            '--single-transaction', '--quick', '--skip-lock-tables',
            '--host='.$connection['host'], '--port='.$connection['port'],
            '--user='.$connection['username'], $connection['database'],
        ];

        $process = new Process($arguments, base_path(), ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        $process->setTimeout(1800);
        $stderr = '';

        try {
            $process->run(function (string $type, string $buffer) use ($handle, &$stderr): void {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                } else {
                    $stderr = substr($stderr.$buffer, -4000);
                }
            });
        } finally {
            fclose($handle);
        }

        if (! $process->isSuccessful() || ! is_file($absolutePath) || filesize($absolutePath) < 32) {
            @unlink($absolutePath);
            logger()->error('MySQL backup process failed.', [
                'exit_code' => $process->getExitCode(),
                'stderr' => $stderr,
            ]);
            throw new RuntimeException('Database backup failed. Confirm the mysqldump executable, database access, and credentials.');
        }

        @chmod($absolutePath, 0640);

        return BackupRecord::create([
            'filename' => $filename,
            'disk_path' => $relativePath,
            'size_bytes' => filesize($absolutePath),
            'status' => 'completed',
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function prune(int $retentionDays): int
    {
        $cutoff = now()->subDays(max(1, min($retentionDays, 3650)));
        $deleted = 0;

        BackupRecord::where('created_at', '<', $cutoff)->orderBy('id')->chunkById(100, function ($records) use (&$deleted): void {
            foreach ($records as $record) {
                Storage::disk('local')->delete($record->disk_path);
                $record->delete();
                $deleted++;
            }
        });

        return $deleted;
    }
}
