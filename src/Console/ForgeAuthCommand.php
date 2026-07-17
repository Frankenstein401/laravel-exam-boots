<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NamaKamu\LaravelForgeBoots\Concerns\TracksFileOperations;
use Symfony\Component\Process\Process;

/**
 * Artisan command to setup complete authentication system.
 *
 * Supports JWT (tymon/jwt-auth) and Laravel Sanctum with interactive setup.
 * Auto-installs API scaffolding, configures auth guards, modifies User model,
 * generates AuthController, and appends auth routes.
 *
 * Usage: php artisan forge:auth
 *
 * @package NamaKamu\LaravelForgeBoots
 */
class ForgeAuthCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forge:auth
                            {--method= : Authentication method (jwt or sanctum)}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

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
        $this->components->info('Authentication Setup');
        $this->newLine();

        $stubDir = __DIR__ . '/../stubs/';

        // =============================================
        // Step 1: API Installation
        // =============================================
        $useApi = $this->confirm('Apakah project ini menggunakan API?', true);

        if ($useApi) {
            $this->components->info('Installing Laravel API scaffolding...');
            if (! $this->option('dry-run')) {
                try {
                    $this->call('install:api', ['--without-migration-prompt' => true]);
                    $this->addResult('Install API (routes/api.php)', '✅ Installed');
                } catch (\Throwable $e) {
                    $this->components->warn('API scaffolding mungkin sudah terinstall atau terjadi error.');
                    $this->addResult('Install API', '⚠️ ' . $e->getMessage());
                }
            } else {
                $this->line('<fg=yellow>[DRY-RUN]</> Akan menjalankan: php artisan install:api');
                $this->addResult('Install API (routes/api.php)', '⏭️ Dry-run preview');
            }
        } else {
            $this->addResult('Install API', '⏭️ Skipped');
        }

        $this->newLine();

        // =============================================
        // Step 2: Choose Authentication Method
        // =============================================
        $methodOpt = $this->option('method');
        if (! $methodOpt) {
            $configDefault = config('forge-boots.defaults.auth_method');
            if ($configDefault === 'jwt') {
                $methodOpt = 'JWT (tymon/jwt-auth)';
            } elseif ($configDefault === 'sanctum') {
                $methodOpt = 'Laravel Sanctum';
            }
        } else {
            $methodOpt = strtolower($methodOpt) === 'jwt' ? 'JWT (tymon/jwt-auth)' : 'Laravel Sanctum';
        }

        if (! $methodOpt) {
            $authMethod = $this->choice(
                'Pilih metode autentikasi:',
                ['JWT (tymon/jwt-auth)', 'Laravel Sanctum'],
                0,
            );
        } else {
            $authMethod = $methodOpt;
        }

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
            if ($this->confirmOverwrite($userModelPath)) {
                if (File::exists($userStub)) {
                    $this->writeFile($userModelPath, File::get($userStub));
                    $this->addResult('User Model', '✅ Overwritten');
                }
            } else {
                $this->components->warn('User model tidak diubah. Pastikan model sudah dikonfigurasi untuk auth.');
                $this->addResult('User Model', '⏭️ Skipped (manual setup needed)');
            }
        } else {
            if (File::exists($userStub)) {
                $this->writeFile($userModelPath, File::get($userStub));
                $this->addResult('User Model', '✅ Created');
            }
        }

        // =============================================
        // Step 5: Generate Auth Components (Service, Requests, Resource, Controller)
        // =============================================
        $this->components->info('Generating Auth Components (Service, Requests, Resource, Controller)...');

        $servicePath = app_path('Services/AuthService.php');
        $serviceStub = $stubDir . ($isJwt ? 'auth-service.jwt.stub' : 'auth-service.sanctum.stub');
        if (File::exists($serviceStub)) {
            $this->writeFile($servicePath, File::get($serviceStub));
            $this->addResult('AuthService', '✅ Created');
        }

        $loginRequestPath = app_path('Http/Requests/Auth/LoginRequest.php');
        $loginRequestStub = $stubDir . 'auth-request-login.stub';
        if (File::exists($loginRequestStub)) {
            $this->writeFile($loginRequestPath, File::get($loginRequestStub));
            $this->addResult('LoginRequest', '✅ Created');
        }

        $registerRequestPath = app_path('Http/Requests/Auth/RegisterRequest.php');
        $registerRequestStub = $stubDir . 'auth-request-register.stub';
        if (File::exists($registerRequestStub)) {
            $this->writeFile($registerRequestPath, File::get($registerRequestStub));
            $this->addResult('RegisterRequest', '✅ Created');
        }

        $userResourcePath = app_path('Http/Resources/UserResource.php');
        $userResourceStub = $stubDir . 'auth-user-resource.stub';
        if (File::exists($userResourceStub)) {
            $this->writeFile($userResourcePath, File::get($userResourceStub));
            $this->addResult('UserResource', '✅ Created');
        }

        $controllerSubdir = $useApi ? 'Api/' : '';
        $controllerNamespace = $useApi ? 'App\\Http\\Controllers\\Api' : 'App\\Http\\Controllers';
        $controllerPath = app_path("Http/Controllers/{$controllerSubdir}AuthController.php");
        $controllerStub = $stubDir . ($isJwt ? 'auth-controller.jwt.stub' : 'auth-controller.sanctum.stub');

        if (File::exists($controllerStub)) {
            $controllerContent = File::get($controllerStub);
            $controllerContent = str_replace('{{Namespace}}', $controllerNamespace, $controllerContent);
            $this->writeFile($controllerPath, $controllerContent);
            $this->addResult('AuthController', '✅ Created');
        }

        // =============================================
        // Step 6: Append Auth Routes
        // =============================================
        $routeFilePath = $useApi ? base_path('routes/api.php') : base_path('routes/web.php');
        $routeStub = $stubDir . ($isJwt ? 'auth-routes.jwt.stub' : 'auth-routes.sanctum.stub');

        if (! File::exists($routeFilePath)) {
            $this->components->error("File {$routeFilePath} tidak ditemukan!");
            $this->addResult('Auth Routes', '❌ routes file not found');
        } else {
            $existingRoutes = File::get($routeFilePath);

            if (str_contains($existingRoutes, 'AuthController')) {
                if ($this->confirm('Auth routes sudah ada di routes file, apakah ingin menambahkan lagi?', false)) {
                    $this->appendRoutes($routeStub, $routeFilePath, $useApi);
                } else {
                    $this->addResult('Auth Routes', '⏭️ Skipped (already exists)');
                }
            } else {
                $this->appendRoutes($routeStub, $routeFilePath, $useApi);
            }
        }

        // =============================================
        // Step 7: API Documentation (Scramble)
        // =============================================
        $this->newLine();
        $configInstallDocs = config('forge-boots.defaults.install_docs', true);
        $installDocs = $this->confirm('Apakah ingin menginstall API Documentation (Scramble)?', $configInstallDocs);

        if ($installDocs) {
            $this->setupScramble($isJwt, $stubDir);
        } else {
            $this->addResult('API Docs (Scramble)', '⏭️ Skipped');
        }

        // Persist operation log for forge:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog('forge:auth');
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
        $this->components->info("Authentication [{$authMethod}] setup complete!");

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info('   1. Jalankan: php artisan migrate');

        $this->info('   2. Test login: POST /api/auth/login {"email": "...", "password": "..."}');
        $this->info('   3. Test register: POST /api/auth/register {"name": "...", "email": "...", "password": "...", "password_confirmation": "..."}');
        $this->info('   4. Gunakan header: Authorization: Bearer {token}');

        if ($installDocs) {
            $this->newLine();
            $this->info('   API Documentation: http://localhost:8000/docs/api');
        }

        return self::SUCCESS;
    }

    /**
     * Setup JWT authentication.
     */
    private function setupJwt(): void
    {
        if ($this->option('dry-run')) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menginstall package tymon/jwt-auth');
            $this->addResult('Install tymon/jwt-auth', '⏭️ Dry-run preview');
            return;
        }

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

        // Update config/auth.php
        $authConfigPath = config_path('auth.php');

        if (File::exists($authConfigPath)) {
            $authConfig = File::get($authConfigPath);

            if (! str_contains($authConfig, "'driver' => 'jwt'")) {
                if (! str_contains($authConfig, "'api'")) {
                    $newConfig = str_replace(
                        "'guards' => [",
                        "'guards' => [\n        'api' => [\n            'driver' => 'jwt',\n            'provider' => 'users',\n        ],\n",
                        $authConfig,
                    );
                    $this->modifyFile($authConfigPath, $newConfig, 'Added JWT guard to config/auth.php');
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
        if ($apiAlreadyInstalled) {
            $this->components->info('Sanctum sudah terinstall melalui install:api.');
            $this->addResult('Install Sanctum', '✅ Already installed (via install:api)');
        } else {
            if ($this->option('dry-run')) {
                $this->line('<fg=yellow>[DRY-RUN]</> Akan menginstall API Scaffolding (Sanctum)');
                $this->addResult('Install Sanctum', '⏭️ Dry-run preview');
                return;
            }

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
     * Append route stub content to routes file.
     */
    private function appendRoutes(string $stubPath, string $routeFilePath, bool $useApi): void
    {
        if (! File::exists($stubPath)) {
            $this->addResult('Auth Routes', '❌ Route stub not found');
            return;
        }

        $routeContent = File::get($stubPath);
        if ($useApi) {
            $routeContent = str_replace('App\\Http\\Controllers\\AuthController', 'App\\Http\\Controllers\\Api\\AuthController', $routeContent);
        }
        
        $newContent = File::get($routeFilePath) . "\n" . $routeContent;
        $this->modifyFile($routeFilePath, $newContent, 'Appended auth routes');
        $this->addResult('Auth Routes', '✅ Appended');
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

    /**
     * Setup Scramble API documentation.
     */
    private function setupScramble(bool $isJwt, string $stubDir): void
    {
        if ($this->option('dry-run')) {
            $this->line('<fg=yellow>[DRY-RUN]</> Akan menginstall package dedoc/scramble dan register provider');
            $this->addResult('Install Scramble', '⏭️ Dry-run preview');
            return;
        }

        $this->components->info('Installing dedoc/scramble...');

        try {
            $process = new Process(['composer', 'require', 'dedoc/scramble']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(300);
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if ($process->isSuccessful()) {
                $this->addResult('Install dedoc/scramble', '✅ Installed');
            } else {
                $this->addResult('Install dedoc/scramble', '❌ Failed');
                $this->components->error('Gagal install dedoc/scramble. Install manual: composer require dedoc/scramble');
                return;
            }
        } catch (\Throwable $e) {
            $this->addResult('Install dedoc/scramble', '❌ ' . $e->getMessage());
            return;
        }

        // Publish Scramble config
        try {
            $this->call('vendor:publish', [
                '--provider' => 'Dedoc\Scramble\ScrambleServiceProvider',
                '--tag'      => 'scramble-config',
            ]);
            $this->addResult('Publish Scramble Config', '✅ Published');
        } catch (\Throwable $e) {
            $this->addResult('Publish Scramble Config', '⚠️ ' . $e->getMessage());
        }

        // Generate ScrambleServiceProvider
        $providerPath = app_path('Providers/ScrambleServiceProvider.php');
        $providerStub = $stubDir . ($isJwt ? 'scramble-provider.jwt.stub' : 'scramble-provider.sanctum.stub');

        if (File::exists($providerPath)) {
            if ($this->confirmOverwrite($providerPath)) {
                $this->writeFile($providerPath, File::get($providerStub));
                $this->addResult('ScrambleServiceProvider', '✅ Overwritten');
            } else {
                $this->addResult('ScrambleServiceProvider', '⏭️ Skipped');
            }
        } else {
            $this->writeFile($providerPath, File::get($providerStub));
            $this->addResult('ScrambleServiceProvider', '✅ Created');
        }

        // Register ScrambleServiceProvider in bootstrap/providers.php
        $providersPath = base_path('bootstrap/providers.php');

        if (File::exists($providersPath)) {
            $providersContent = File::get($providersPath);

            if (str_contains($providersContent, 'ScrambleServiceProvider')) {
                $this->addResult('Register ScrambleServiceProvider', '✅ Already registered');
            } else {
                $newContent = str_replace(
                    '];',
                    "    App\\Providers\\ScrambleServiceProvider::class,\n];",
                    $providersContent,
                );
                $this->modifyFile($providersPath, $newContent, 'Registered ScrambleServiceProvider in bootstrap/providers.php');
                $this->addResult('Register ScrambleServiceProvider', '✅ Registered');
            }
        } else {
            $this->addResult('Register ScrambleServiceProvider', '⚠️ bootstrap/providers.php not found');
        }
    }
}
