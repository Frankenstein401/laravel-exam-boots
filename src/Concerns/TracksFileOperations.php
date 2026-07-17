<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait TracksFileOperations
{
    /**
     * Log of operations performed.
     *
     * @var array<int, array{action: string, path: string, backup?: string}>
     */
    protected array $operationLog = [];

    /**
     * Track file creation.
     */
    protected function trackCreate(string $path): void
    {
        $this->operationLog[] = ['action' => 'create', 'path' => $path];
    }

    /**
     * Track file modification and backup current state.
     */
    protected function trackModify(string $path): void
    {
        $backupPath = $this->backupFile($path);
        $this->operationLog[] = [
            'action' => 'modify',
            'path'   => $path,
            'backup' => $backupPath,
        ];
    }

    /**
     * Backup existing file content to storage directory.
     */
    protected function backupFile(string $path): ?string
    {
        if (! File::exists($path)) {
            return null;
        }

        $backupDir = storage_path('forge-boots/backups/' . now()->format('Ymd_His'));
        File::ensureDirectoryExists($backupDir);

        // Normalize path separators to avoid windows/unix path issues in filename
        $safeName = str_replace(['/', '\\', ':'], '_', $path);
        $backupPath = $backupDir . '/' . $safeName . '.bak';
        File::copy($path, $backupPath);

        return $backupPath;
    }

    /**
     * Save the action log history to history.json.
     */
    protected function persistOperationLog(string $command): void
    {
        if (empty($this->operationLog)) {
            return;
        }

        $logFile = storage_path('forge-boots/history.json');
        File::ensureDirectoryExists(dirname($logFile));

        $history = File::exists($logFile) ? json_decode(File::get($logFile), true) : [];
        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'id'         => (string) Str::uuid(),
            'command'    => $command,
            'timestamp'  => now()->toIso8601String(),
            'operations' => $this->operationLog,
        ];

        File::put($logFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    /**
     * Write file content checking for dry-run and overwrite confirmation.
     */
    protected function writeFile(string $path, string $content): bool
    {
        if ($this->hasOption('dry-run') && $this->option('dry-run')) {
            $this->line("<fg=yellow>[DRY-RUN]</> Akan membuat: {$path}");
            return false;
        }

        $existed = File::exists($path);

        if ($existed) {
            $this->trackModify($path);
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        if (! $existed) {
            $this->trackCreate($path);
            $this->components->info("Created: " . basename($path));
        } else {
            $this->components->info("Modified: " . basename($path) . " (Overwritten)");
        }
        return true;
    }

    /**
     * Modify existing file content checking for dry-run.
     */
    protected function modifyFile(string $path, string $newContent, string $description): bool
    {
        if ($this->hasOption('dry-run') && $this->option('dry-run')) {
            $this->line("<fg=yellow>[DRY-RUN]</> Akan mengubah: {$path} ({$description})");
            return false;
        }

        $this->trackModify($path);
        File::put($path, $newContent);
        $this->components->info("Modified: " . basename($path) . " ({$description})");
        return true;
    }

    /**
     * Confirm overwrite of existing files unless --force is specified.
     */
    protected function confirmOverwrite(string $path): bool
    {
        if ($this->hasOption('force') && $this->option('force')) {
            return true;
        }

        if (! File::exists($path)) {
            return true;
        }

        return $this->confirm("File " . basename($path) . " sudah ada. Timpa (overwrite)?", false);
    }
}
