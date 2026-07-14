<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Artisan command to generate the ApiResponse trait.
 *
 * Generates app/Traits/ApiResponse.php to handle standardized API response structures.
 *
 * Usage: php artisan exam:response
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamResponseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:response';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API response trait helper for success and error JSON responses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('📄 Laravel Exam Boots — Response Trait Generator');
        $this->newLine();

        $targetPath = app_path('Traits/ApiResponse.php');
        $stubPath = __DIR__ . '/../stubs/api-response-trait.stub';

        if (! File::exists($stubPath)) {
            $this->components->error('Stub untuk ApiResponse trait tidak ditemukan!');
            return self::FAILURE;
        }

        if (File::exists($targetPath)) {
            $overwrite = $this->confirm(
                'File app/Traits/ApiResponse.php sudah ada, apakah ingin menimpa (overwrite)?',
                false
            );

            if (! $overwrite) {
                $this->components->warn('Pembuatan trait dibatalkan.');
                return self::SUCCESS;
            }
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($targetPath));

        // Copy stub to target path
        File::put($targetPath, File::get($stubPath));

        $this->components->info('ApiResponse trait berhasil dibuat di: app/Traits/ApiResponse.php');
        $this->newLine();

        $this->info('Cara penggunaan:');
        $this->info('   1. Import trait di Controller Anda:');
        $this->info('      use App\Traits\ApiResponse;');
        $this->newLine();
        $this->info('   2. Gunakan trait di dalam kelas Controller:');
        $this->info('      use ApiResponse;');
        $this->newLine();
        $this->info('   3. Panggil method helper:');
        $this->info('      // Success Response (data, message, status code)');
        $this->info('      return $this->successResponse($products, "Data fetched successfully", 200);');
        $this->info('      return $this->successResponse(); // Semua parameter opsional/bisa dikosongi');
        $this->newLine();
        $this->info('      // Error Response (message, status code, data/errors)');
        $this->info('      return $this->errorResponse("Unauthorized access", 401);');
        $this->info('      return $this->errorResponse(); // Menggunakan default message ("Terjadi kesalahan") & code 400');

        return self::SUCCESS;
    }
}
