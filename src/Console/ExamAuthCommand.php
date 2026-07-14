<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Artisan command to setup complete authentication system.
 *
 * Supports JWT (tymon/jwt-auth) and Laravel Sanctum with interactive setup.
 * Auto-installs API scaffolding, configures auth guards, modifies User model,
 * generates AuthController, and appends auth routes.
 *
 * Usage: php artisan exam:auth
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup authentication system (JWT / Sanctum) with Login, Register, Logout';

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
        $this->components->info('🔐 Laravel Exam Boots — Authentication Setup');
        $this->newLine();

        $stubDir = __DIR__ . '/../stubs/';

        // =============================================
        // Step 1: API Installation
        // =============================================

        $useApi = $this->confirm('Apakah project ini menggunakan API?', true);

        if ($useApi) {
            $this->components->info('Installing Laravel API scaffolding...');

            try {
                $this->call('install:api', ['--without-migration-prompt' => true]);
                $this->addResult('Install API (routes/api.php)', '✅ Installed');
            } catch (\Throwable $e) {
                $this->components->warn('API scaffolding mungkin sudah terinstall atau terjadi error.');
                $this->addResult('Install API', '⚠️ ' . $e->getMessage());
            }
        } else {
            $this->addResult('Install API', '⏭️ Skipped');
        }

        $this->newLine();

        // =============================================
        // Step 2: Choose Authentication Method
        // =============================================

        $authMethod = $this->choice(
            'Pilih metode autentikasi:',
            ['JWT (tymon/jwt-auth)', 'Laravel Sanctum'],
            0,
        );

        $isJwt = $authMethod === 'JWT (tymon/jwt-auth)';
        $guardName = $isJwt ? 'api' : 'sanctum';

        $this->newLine();
        $this->components->info("Setting up: {$authMethod}");
        $this->newLine();

        // =============================================
        // Step 3: Install & Configure Auth Package
        // =============================================

        if ($isJwt) {
            $this->setupJwt();
        } else {
            $this->setupSanctum($useApi);
        }

        // =============================================
        // Step 4: Setup User Model
        // =============================================

        $userModelPath = app_path('Models/User.php');
        $userStub = $stubDir . ($isJwt ? 'auth-user.jwt.stub' : 'auth-user.sanctum.stub');

        if (File::exists($userModelPath)) {
            $overwrite = $this->confirm(
                'File User.php sudah ada, apakah ingin menimpa dengan versi auth?',
                true,
            );

            if ($overwrite) {
                $this->writeStubToTarget($userStub, $userModelPath, 'User Model');
            } else {
                $this->components->warn('User model tidak diubah. Pastikan model sudah dikonfigurasi untuk auth.');
                $this->addResult('User Model', '⏭️ Skipped (manual setup needed)');

                if ($isJwt) {
                    $this->newLine();
                    $this->info('   Tambahkan ke User model secara manual:');
                    $this->info('   - implements \\Tymon\\JWTAuth\\Contracts\\JWTSubject');
                    $this->info('   - method getJWTIdentifier()');
                    $this->info('   - method getJWTCustomClaims()');
                } else {
                    $this->newLine();
                    $this->info('   Tambahkan ke User model secara manual:');
                    $this->info('   - use \\Laravel\\Sanctum\\HasApiTokens;');
                }
            }
        } else {
            File::ensureDirectoryExists(dirname($userModelPath));
            $this->writeStubToTarget($userStub, $userModelPath, 'User Model');
        }

        // =============================================
        // Step 5: Generate AuthController
        // =============================================

        $controllerPath = app_path('Http/Controllers/AuthController.php');
        $controllerStub = $stubDir . ($isJwt ? 'auth-controller.jwt.stub' : 'auth-controller.sanctum.stub');

        if (File::exists($controllerPath)) {
            $overwrite = $this->confirm(
                'File AuthController.php sudah ada, apakah ingin menimpa (overwrite)?',
                false,
            );

            if ($overwrite) {
                $this->writeStubToTarget($controllerStub, $controllerPath, 'AuthController');
            } else {
                $this->addResult('AuthController', '⏭️ Skipped');
            }
        } else {
            File::ensureDirectoryExists(dirname($controllerPath));
            $this->writeStubToTarget($controllerStub, $controllerPath, 'AuthController');
        }

        // =============================================
        // Step 6: Append Auth Routes
        // =============================================

        $apiRoutePath = base_path('routes/api.php');
        $routeStub = $stubDir . ($isJwt ? 'auth-routes.jwt.stub' : 'auth-routes.sanctum.stub');

        if (! File::exists($apiRoutePath)) {
            $this->components->error('File routes/api.php tidak ditemukan!');
            $this->info('   Jalankan: php artisan install:api');
            $this->addResult('Auth Routes', '❌ routes/api.php not found');
        } else {
            $existingRoutes = File::get($apiRoutePath);

            if (str_contains($existingRoutes, 'AuthController')) {
                $overwrite = $this->confirm(
                    'Auth routes sudah ada di api.php, apakah ingin menambahkan lagi?',
                    false,
                );

                if (! $overwrite) {
                    $this->addResult('Auth Routes', '⏭️ Skipped (already exists)');
                } else {
                    $this->appendRoutes($routeStub, $apiRoutePath);
                }
            } else {
                $this->appendRoutes($routeStub, $apiRoutePath);
            }
        }

        // =============================================
        // Summary Output
        // =============================================

        $this->newLine();
        $this->table(
            ['Step', 'Status'],
            $this->results,
        );

        $this->newLine();
        $this->components->info("🚀 Authentication [{$authMethod}] setup complete!");

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info('   1. Jalankan: php artisan migrate');

        if ($isJwt) {
            $this->info('   2. Test login: POST /api/auth/login {"email": "...", "password": "..."}');
            $this->info('   3. Test register: POST /api/auth/register {"name": "...", "email": "...", "password": "...", "password_confirmation": "..."}');
            $this->info('   4. Gunakan header: Authorization: Bearer {token}');
        } else {
            $this->info('   2. Test login: POST /api/auth/login {"email": "...", "password": "..."}');
            $this->info('   3. Test register: POST /api/auth/register {"name": "...", "email": "...", "password": "...", "password_confirmation": "..."}');
            $this->info('   4. Gunakan header: Authorization: Bearer {token}');
        }

        return self::SUCCESS;
    }

    /**
     * Setup JWT authentication (install package, publish config, generate secret, update auth config).
     */
    private function setupJwt(): void
    {
        // Install tymon/jwt-auth via Composer
        $this->components->info('Installing tymon/jwt-auth...');

        try {
            $process = new Process(['composer', 'require', 'tymon/jwt-auth']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(300);
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if ($process->isSuccessful()) {
                $this->addResult('Install tymon/jwt-auth', '✅ Installed');
            } else {
                $this->addResult('Install tymon/jwt-auth', '❌ Failed');
                $this->components->error('Gagal install tymon/jwt-auth. Install manual: composer require tymon/jwt-auth');
            }
        } catch (\Throwable $e) {
            $this->addResult('Install tymon/jwt-auth', '❌ ' . $e->getMessage());
        }

        // Publish JWT config
        try {
            $this->call('vendor:publish', [
                '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider',
            ]);
            $this->addResult('Publish JWT Config', '✅ Published');
        } catch (\Throwable $e) {
            $this->addResult('Publish JWT Config', '⚠️ ' . $e->getMessage());
        }

        // Generate JWT secret
        try {
            $this->call('jwt:secret', ['--force' => true]);
            $this->addResult('Generate JWT Secret', '✅ Generated (in .env)');
        } catch (\Throwable $e) {
            $this->addResult('Generate JWT Secret', '⚠️ ' . $e->getMessage());
        }

        // Update config/auth.php — add 'api' guard with JWT driver
        $authConfigPath = config_path('auth.php');

        if (File::exists($authConfigPath)) {
            $authConfig = File::get($authConfigPath);

            if (! str_contains($authConfig, "'driver' => 'jwt'")) {
                // Add api guard if not present
                if (! str_contains($authConfig, "'api'")) {
                    $authConfig = str_replace(
                        "'guards' => [",
                        "'guards' => [\n        'api' => [\n            'driver' => 'jwt',\n            'provider' => 'users',\n        ],\n",
                        $authConfig,
                    );
                    File::put($authConfigPath, $authConfig);
                    $this->addResult('Config auth.php (api guard)', '✅ Added JWT guard');
                } else {
                    $this->addResult('Config auth.php (api guard)', '⚠️ api guard exists, update manually');
                }
            } else {
                $this->addResult('Config auth.php (api guard)', '✅ JWT guard already configured');
            }
        } else {
            $this->addResult('Config auth.php', '❌ File not found');
        }
    }

    /**
     * Setup Sanctum authentication.
     */
    private function setupSanctum(bool $apiAlreadyInstalled): void
    {
        // Sanctum comes bundled with Laravel. If install:api was run, it's already set up.
        if ($apiAlreadyInstalled) {
            $this->components->info('Sanctum sudah terinstall melalui install:api.');
            $this->addResult('Install Sanctum', '✅ Already installed (via install:api)');
        } else {
            // Need to install API scaffolding for Sanctum to work
            $this->components->info('Sanctum membutuhkan API scaffolding. Menginstall...');

            try {
                $this->call('install:api', ['--without-migration-prompt' => true]);
                $this->addResult('Install Sanctum (via install:api)', '✅ Installed');
            } catch (\Throwable $e) {
                $this->components->warn('Mungkin sudah terinstall.');
                $this->addResult('Install Sanctum', '⚠️ ' . $e->getMessage());
            }
        }
    }

    /**
     * Write stub content to a target file.
     */
    private function writeStubToTarget(string $stubPath, string $targetPath, string $label): void
    {
        if (! File::exists($stubPath)) {
            $this->components->error("Stub not found: {$stubPath}");
            $this->addResult($label, '❌ Stub not found');
            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, File::get($stubPath));

        $this->components->info("Created: {$label}");
        $this->addResult($label, '✅ Created');
    }

    /**
     * Append route stub content to api.php.
     */
    private function appendRoutes(string $stubPath, string $apiRoutePath): void
    {
        if (! File::exists($stubPath)) {
            $this->addResult('Auth Routes', '❌ Route stub not found');
            return;
        }

        $routeContent = File::get($stubPath);
        File::append($apiRoutePath, "\n" . $routeContent);

        $this->components->info('Auth routes appended to routes/api.php');
        $this->addResult('Auth Routes', '✅ Appended to api.php');
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
