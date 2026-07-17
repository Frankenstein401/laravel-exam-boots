<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to run full exam setup in one shot.
 *
 * Orchestrates exam:doctor, exam:auth, exam:seed-admin, and migrate
 * in a single command with a summary at the end.
 *
 * Usage: php artisan exam:install
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamInstallCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:install
                            {--dry-run : Preview operations}
                            {--force : Force overwrite}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-shot setup: verify environment, setup authentication, seed admin user';

    /**
     * Track results for summary table.
     *
     * @var array<int, array{Step: string, Status: string}>
     */
    private array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('🚀 Laravel Exam Boots — One-Shot Installer');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->line('Perintah ini akan menjalankan beberapa langkah sekaligus:');
        $this->line('  ' . ($isDryRun ? '⏭️' : '1') . '. Verifikasi environment (exam:doctor)');
        $this->line('  ' . ($isDryRun ? '⏭️' : '2') . '. Setup autentikasi (exam:auth)');
        $this->line('  ' . ($isDryRun ? '⏭️' : '3') . '. Buat seeder admin (exam:seed-admin)');
        $this->line('  ' . ($isDryRun ? '⏭️' : '4') . '. Jalankan migrasi database (migrate)');
        $this->newLine();

        // =============================================
        // Step 1: Environment Check
        // =============================================
        $this->components->info('Step 1/4 — Environment Check');
        $this->newLine();

        if ($isDryRun) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menjalankan: php artisan exam:doctor');
            $this->line('  Memeriksa PHP version, .env, APP_KEY, database connection, dll.');
            $this->addResult('Environment Check (exam:doctor)', '⏭️ Dry-run preview');
        } else {
            $exitCode = $this->call('exam:doctor');
            $this->addResult(
                'Environment Check (exam:doctor)',
                $exitCode === self::SUCCESS ? '✅ Passed' : '❌ Failed',
            );
        }

        $this->newLine();

        // =============================================
        // Step 2: Authentication Setup
        // =============================================
        $this->components->info('Step 2/4 — Authentication Setup');
        $this->newLine();

        if ($isDryRun) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menjalankan: php artisan exam:auth');
            $this->line('  Install API scaffolding, pilih JWT/Sanctum, generate AuthController, routes.');
            $this->addResult('Authentication Setup (exam:auth)', '⏭️ Dry-run preview');
        } else {
            $this->call('exam:auth', [
                '--force'   => $force,
                '--dry-run' => $isDryRun,
            ]);
            $this->addResult('Authentication Setup (exam:auth)', '✅ Complete');
        }

        $this->newLine();

        // =============================================
        // Step 3: Admin Seeder
        // =============================================
        $this->components->info('Step 3/4 — Admin Seeder');
        $this->newLine();

        if ($isDryRun) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menjalankan: php artisan exam:seed-admin');
            $this->line('  Generate AdminUserSeeder dengan email default dan register di DatabaseSeeder.');
            $this->addResult('Admin Seeder (exam:seed-admin)', '⏭️ Dry-run preview');
        } else {
            $this->call('exam:seed-admin', [
                '--force'   => $force,
                '--dry-run' => $isDryRun,
            ]);
            $this->addResult('Admin Seeder (exam:seed-admin)', '✅ Complete');
        }

        $this->newLine();

        // =============================================
        // Step 4: Run Migrations
        // =============================================
        $this->components->info('Step 4/4 — Run Migrations');
        $this->newLine();

        if ($isDryRun) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menjalankan: php artisan migrate');
            $this->line('  Menjalankan semua pending migration ke database.');
            $this->addResult('Run Migrations (migrate)', '⏭️ Dry-run preview');
        } else {
            $exitCode = $this->call('migrate', [
                '--force' => $force,
            ]);
            $this->addResult(
                'Run Migrations (migrate)',
                $exitCode === self::SUCCESS ? '✅ Complete' : '❌ Failed',
            );
        }

        $this->newLine();
        $this->newLine();

        // =============================================
        // Summary Table
        // =============================================
        $this->components->info('📋 Installation Summary');
        $this->table(['Step', 'Status'], $this->results);

        $this->newLine();

        if ($isDryRun) {
            $this->components->warn('⏭️ Dry-run complete — tidak ada perubahan yang benar-benar dilakukan.');
            $this->newLine();
            $this->info('Hilangkan opsi --dry-run untuk menjalankan instalasi sesungguhnya:');
            $this->info('  php artisan exam:install');

            return self::SUCCESS;
        }

        // =============================================
        // Next Steps
        // =============================================
        $this->components->info('🎉 Instalasi selesai!');
        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');

        $this->info('   1. Seed admin user: php artisan db:seed --class=AdminUserSeeder');
        $this->info('   2. Coba login: POST /api/auth/login');
        $this->info('       {"email": "admin@example.com", "password": "password"}');
        $this->info('   3. Mulai buat fitur ujian Anda!');

        return self::SUCCESS;
    }

    /**
     * Add a result entry for the summary table.
     */
    private function addResult(string $step, string $status): void
    {
        $this->results[] = [
            'Step'   => $step,
            'Status' => $status,
        ];
    }
}
