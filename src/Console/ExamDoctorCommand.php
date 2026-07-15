<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Artisan command to check environment ready for exam.
 *
 * Usage: php artisan exam:doctor
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamDoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify development environment setup before starting exam';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('🏥 Laravel Exam Boots — Environment Doctor');
        $this->newLine();

        $results = [];
        $failedCount = 0;
        $warningCount = 0;
        $passedCount = 0;

        // 1. PHP Version
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        if ($phpOk) {
            $results[] = ['Check' => 'PHP Version', 'Status' => '✅ OK', 'Details' => $phpVersion];
            $passedCount++;
        } else {
            $results[] = ['Check' => 'PHP Version', 'Status' => '❌ Fail', 'Details' => "{$phpVersion} (Harus >= 8.2.0)"];
            $failedCount++;
        }

        // 2. Laravel Structure / Middleware Check
        $bootstrapAppExists = File::exists(base_path('bootstrap/app.php'));
        $kernelExists = File::exists(app_path('Http/Kernel.php'));

        if ($bootstrapAppExists && ! $kernelExists) {
            $results[] = ['Check' => 'Laravel Structure', 'Status' => '✅ OK', 'Details' => 'Laravel 11+ (bootstrap/app.php, no Kernel.php)'];
            $passedCount++;
        } elseif ($kernelExists) {
            $results[] = ['Check' => 'Laravel Structure', 'Status' => '⚠️ Warning', 'Details' => 'Laravel 10- (Traditional Kernel.php structure detected)'];
            $warningCount++;
        } else {
            $results[] = ['Check' => 'Laravel Structure', 'Status' => '❌ Fail', 'Details' => 'Gagal mendeteksi bootstrap/app.php atau Kernel.php'];
            $failedCount++;
        }

        // 3. .env File
        $envExists = File::exists(base_path('.env'));
        if ($envExists) {
            $results[] = ['Check' => '.env File', 'Status' => '✅ OK', 'Details' => 'Ditemukan'];
            $passedCount++;
        } else {
            $results[] = ['Check' => '.env File', 'Status' => '❌ Fail', 'Details' => 'Tidak ditemukan!'];
            $failedCount++;
        }

        // 4. APP_KEY
        if ($envExists) {
            $appKey = env('APP_KEY');
            if ($appKey) {
                $results[] = ['Check' => 'APP_KEY', 'Status' => '✅ OK', 'Details' => 'Sudah diset'];
                $passedCount++;
            } else {
                $results[] = ['Check' => 'APP_KEY', 'Status' => '❌ Fail', 'Details' => 'Belum diset! Jalankan: php artisan key:generate'];
                $failedCount++;
            }
        } else {
            $results[] = ['Check' => 'APP_KEY', 'Status' => '⏭️ N/A', 'Details' => 'Lewati (tidak ada .env)'];
        }

        // 5. APP_DEBUG
        if ($envExists) {
            $appDebug = env('APP_DEBUG');
            if ($appDebug === true || $appDebug === 'true' || env('APP_ENV') === 'local') {
                $results[] = ['Check' => 'APP_DEBUG', 'Status' => '✅ OK', 'Details' => 'Debug aktif (bagus untuk development)'];
                $passedCount++;
            } else {
                $results[] = ['Check' => 'APP_DEBUG', 'Status' => '⚠️ Warning', 'Details' => 'Debug mati. Dianjurkan aktif saat exam'];
                $warningCount++;
            }
        }

        // 6. Database Connection
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $results[] = ['Check' => 'Database Connection', 'Status' => '✅ OK', 'Details' => "Koneksi berhasil ({$driver})"];
            $passedCount++;
            $dbConnected = true;
        } catch (\Throwable $e) {
            $results[] = ['Check' => 'Database Connection', 'Status' => '❌ Fail', 'Details' => 'Gagal konek! Periksa .env DB_DATABASE/DB_PASSWORD'];
            $failedCount++;
        }

        // 7. Pending Migrations
        if ($dbConnected) {
            try {
                Artisan::call('migrate:status');
                $output = Artisan::output();
                $pending = substr_count(strtolower($output), 'pending');
                if ($pending > 0) {
                    $results[] = ['Check' => 'Pending Migrations', 'Status' => '⚠️ Warning', 'Details' => "Ada {$pending} migration belum dijalankan!"];
                    $warningCount++;
                } else {
                    $results[] = ['Check' => 'Pending Migrations', 'Status' => '✅ OK', 'Details' => 'Semua migration up-to-date'];
                    $passedCount++;
                }
            } catch (\Throwable $e) {
                $results[] = ['Check' => 'Pending Migrations', 'Status' => '⚠️ Warning', 'Details' => 'Gagal memeriksa status migration'];
                $warningCount++;
            }
        } else {
            $results[] = ['Check' => 'Pending Migrations', 'Status' => '⏭️ N/A', 'Details' => 'Lewati (database tidak terhubung)'];
        }

        // 8. JWT Secret if package installed
        $jwtInstalled = File::exists(config_path('jwt.php')) || class_exists('Tymon\JWTAuth\Providers\LaravelServiceProvider');
        if ($jwtInstalled) {
            $jwtSecret = env('JWT_SECRET');
            if ($jwtSecret) {
                $results[] = ['Check' => 'JWT Secret', 'Status' => '✅ OK', 'Details' => 'Sudah diset di .env'];
                $passedCount++;
            } else {
                $results[] = ['Check' => 'JWT Secret', 'Status' => '❌ Fail', 'Details' => 'JWT terpasang tapi JWT_SECRET kosong!'];
                $failedCount++;
            }
        } else {
            $results[] = ['Check' => 'JWT Secret', 'Status' => '⏭️ N/A', 'Details' => 'JWT tidak terinstall'];
        }

        // 9. Storage link
        $storageLinkExists = File::exists(public_path('storage'));
        if ($storageLinkExists) {
            $results[] = ['Check' => 'Storage Link', 'Status' => '✅ OK', 'Details' => 'Storage link aktif'];
            $passedCount++;
        } else {
            $results[] = ['Check' => 'Storage Link', 'Status' => '❌ Fail', 'Details' => 'Belum dilink! Jalankan: php artisan storage:link'];
            $failedCount++;
        }

        // 10. routes/api.php
        $apiRoutesExists = File::exists(base_path('routes/api.php'));
        if ($apiRoutesExists) {
            $results[] = ['Check' => 'routes/api.php', 'Status' => '✅ OK', 'Details' => 'Ditemukan'];
            $passedCount++;
        } else {
            $results[] = ['Check' => 'routes/api.php', 'Status' => '❌ Fail', 'Details' => 'Gak ada routes/api.php! Jalankan: php artisan install:api'];
            $failedCount++;
        }

        // Print table
        $this->table(['Check', 'Status', 'Details'], $results);
        $this->newLine();

        $this->info("📊 Hasil checklist: {$passedCount} passed, {$warningCount} warning, {$failedCount} failed.");

        if ($failedCount > 0) {
            $this->newLine();
            $this->components->error('⚠️ Beberpa komponen kritis terindikasi bermasalah. Selesaikan langkah di kolom Details sebelum memulai ujian.');
        } else {
            $this->newLine();
            $this->components->info('🎉 Semua siap untuk ujian! Let\'s build!');
        }

        return self::SUCCESS;
    }
}
