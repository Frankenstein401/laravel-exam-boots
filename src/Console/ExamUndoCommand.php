<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Artisan command to undo generator file creations and modifications.
 *
 * Usage: php artisan exam:undo {id?}
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamUndoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:undo {id? : ID operasi spesifik, default: yang terakhir}
                            {--prune : Bersihkan backup kedaluwarsa sebelum undo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Undo generator operations (revert modified files and delete created files)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logFile = storage_path('exam-boots/history.json');

        if (! File::exists($logFile)) {
            $this->components->warn('Tidak ada riwayat operasi yang tercatat.');
            return self::SUCCESS;
        }

        $history = json_decode(File::get($logFile), true);

        if (empty($history) || ! is_array($history)) {
            $this->components->warn('Riwayat operasi kosong.');
            return self::SUCCESS;
        }

        $id = $this->argument('id');
        $entry = null;

        if ($id) {
            $entry = collect($history)->firstWhere('id', $id);
        } else {
            $entry = end($history);
        }

        if (! $entry) {
            $this->components->error('Operasi tidak ditemukan.');
            return self::FAILURE;
        }

        $this->components->info("Operasi Terdeteksi: {$entry['command']}");
        $this->newLine();

        $rows = [];
        foreach ($entry['operations'] as $op) {
            $rows[] = [$op['action'], basename($op['path']), $op['path']];
        }

        $this->table(['Action', 'Filename', 'Full Path'], $rows);
        $this->newLine();

        // --prune: Cleanup old backups before undo
        $pruneCount = 0;
        if ($this->option('prune')) {
            $backupDir = storage_path('exam-boots/backups');
            if (File::exists($backupDir)) {
                $backupDirs = File::directories($backupDir);
                $retentionDays = (int) config('exam-boots.backup.retention_days', 3);
                $cutoff = now()->subDays($retentionDays);
                $deleted = [];
                foreach ($backupDirs as $dir) {
                    $dirName = basename($dir);
                    $dirDate = \DateTime::createFromFormat('Ymd_His', $dirName);
                    if ($dirDate && $dirDate < $cutoff) {
                        File::deleteDirectory($dir);
                        $deleted[] = $dirName;
                    }
                }
                $pruneCount = count($deleted);
                if ($pruneCount > 0) {
                    $this->components->info("🧹 Dibersihkan {$pruneCount} backup kedaluwarsa (retensi {$retentionDays} hari).");
                } else {
                    $this->components->info("✅ Semua backup masih dalam masa retensi ({$retentionDays} hari). Tidak ada yang dibersihkan.");
                }
            } else {
                $this->components->info('📂 Tidak ada direktori backup untuk dibersihkan.');
            }
            $this->newLine();
        }

        if (! $this->confirm("Apakah Anda yakin ingin me-revert/undo operasi {$entry['command']} ({$entry['timestamp']})?", true)) {
            $this->components->warn('Undo dibatalkan.');
            return self::SUCCESS;
        }

        // Process operations in reverse order (e.g. reverse relationship injections before deleting model files)
        foreach (array_reverse($entry['operations']) as $op) {
            $action = $op['action'];
            $path = $op['path'];

            if ($action === 'create') {
                if (File::exists($path)) {
                    File::delete($path);
                    $this->components->info("Deleted: " . basename($path));
                }
            } elseif ($action === 'modify') {
                $backup = $op['backup'] ?? null;
                if ($backup && File::exists($backup)) {
                    File::copy($backup, $path);
                    $this->components->info("Restored: " . basename($path));
                } else {
                    $this->components->warn("Backup tidak ditemukan untuk: " . basename($path) . ". Skip.");
                }
            }
        }

        // Remove the entry from history list
        if ($id) {
            $history = array_filter($history, fn($h) => $h['id'] !== $id);
        } else {
            array_pop($history);
        }

        File::put($logFile, json_encode(array_values($history), JSON_PRETTY_PRINT));

        $this->newLine();
        $this->components->info('🎉 Revert/Undo operasi berhasil diselesaikan.');

        if (! $this->option('prune')) {
            $this->newLine();
            $this->components->warn('💡 Tips: Jalankan dengan --prune untuk membersihkan backup kedaluwarsa.');
        }

        return self::SUCCESS;
    }
}
