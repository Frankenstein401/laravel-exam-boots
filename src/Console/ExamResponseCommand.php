<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate standard API response helper trait.
 *
 * Usage: php artisan exam:response
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamResponseCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:response 
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API response helper Trait (app/Traits/ApiResponse.php)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('📄 Laravel Exam Boots — API Response Trait Setup');
        $this->newLine();

        $targetPath = app_path('Traits/ApiResponse.php');
        $stubPath = __DIR__ . '/../stubs/api-response-trait.stub';

        if (! File::exists($stubPath)) {
            $this->components->error("Stub response trait tidak ditemukan di: {$stubPath}");
            return self::FAILURE;
        }

        if (File::exists($targetPath)) {
            if (! $this->confirmOverwrite($targetPath)) {
                $this->components->warn('Proses dibatalkan.');
                return self::SUCCESS;
            }
        }

        $content = File::get($stubPath);
        
        $created = false;
        if ($this->writeFile($targetPath, $content)) {
            $created = true;
        }

        // Persist operation log for exam:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog('exam:response');
        }

        // --- Summary Output ---
        $results = [
            [
                'Component' => 'ApiResponse Trait',
                'File'      => $targetPath,
                'Status'    => $created ? '✅ Created' : '⏭️ Preview / Skipped',
            ]
        ];

        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->info('🚀 API Response Trait setup complete!');
        $this->newLine();
        $this->components->warn('Cara Penggunaan:');
        $this->info('   1. Tambahkan ke Controller: use \\App\\Traits\\ApiResponse;');
        $this->info('   2. Di dalam class Controller: use ApiResponse;');
        $this->info('   3. Return sukses: return $this->successResponse($data, "Pesan sukses");');
        $this->info('   4. Return error: return $this->errorResponse("Pesan error", 400);');

        return self::SUCCESS;
    }
}
