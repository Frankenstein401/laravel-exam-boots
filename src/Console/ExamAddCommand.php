<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Artisan command to generate CRUD boilerplate components.
 *
 * Generates Model, Migration, Controller, Service, Request, and Resource files
 * from stub templates with interactive prompts for configuration.
 *
 * Usage: php artisan exam:add {name}
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamAddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:add {name : The name of the component to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD boilerplate components (Model, Migration, Controller, Service, Request, Resource)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNameLower = Str::camel($rawName);
        $tableName = Str::snake(Str::pluralStudly($modelName));

        $this->components->info("Generating CRUD boilerplate for: {$modelName}");
        $this->newLine();

        // --- Interactive Prompts ---

        /** @var bool $useAuth */
        $useAuth = $this->confirm('Apakah fitur ini membutuhkan Auth Middleware?', true);

        /** @var string $dbType */
        $dbType = $this->choice(
            'Pilih tipe database operation:',
            ['Eloquent CRUD', 'Blank Service'],
            0,
        );

        $authMiddleware = $useAuth
            ? "\$this->middleware('auth:sanctum');"
            : '';

        // --- Determine Stub Files ---

        $stubDir = __DIR__ . '/../stubs/';
        $isEloquent = $dbType === 'Eloquent CRUD';

        $migrationFileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";

        $filesToGenerate = [
            [
                'label'    => 'Model',
                'stub'     => $stubDir . 'model.stub',
                'target'   => app_path("Models/{$modelName}.php"),
                'filename' => "{$modelName}.php",
            ],
            [
                'label'    => 'Migration',
                'stub'     => $stubDir . 'migration.stub',
                'target'   => database_path("migrations/{$migrationFileName}"),
                'filename' => $migrationFileName,
            ],
            [
                'label'    => 'Controller',
                'stub'     => $stubDir . ($isEloquent ? 'controller.stub' : 'controller.blank.stub'),
                'target'   => app_path("Http/Controllers/{$modelName}Controller.php"),
                'filename' => "{$modelName}Controller.php",
            ],
            [
                'label'    => 'Service',
                'stub'     => $stubDir . ($isEloquent ? 'service.stub' : 'service.blank.stub'),
                'target'   => app_path("Services/{$modelName}Service.php"),
                'filename' => "{$modelName}Service.php",
            ],
            [
                'label'    => 'Request',
                'stub'     => $stubDir . 'request.stub',
                'target'   => app_path("Http/Requests/{$modelName}Request.php"),
                'filename' => "{$modelName}Request.php",
            ],
            [
                'label'    => 'Resource',
                'stub'     => $stubDir . 'resource.stub',
                'target'   => app_path("Http/Resources/{$modelName}Resource.php"),
                'filename' => "{$modelName}Resource.php",
            ],
        ];

        $this->newLine();

        // --- Generate Files ---

        /** @var array<int, array{Component: string, File: string, Status: string}> $results */
        $results = [];

        foreach ($filesToGenerate as $file) {
            // Validate that the stub file exists
            if (! File::exists($file['stub'])) {
                $this->error("Stub file not found: {$file['stub']}");
                $results[] = [
                    'Component' => $file['label'],
                    'File'      => $file['filename'],
                    'Status'    => '❌ Stub not found',
                ];
                continue;
            }

            // Check if target file already exists and ask for overwrite confirmation
            // (Skip for Migration — always create new)
            if ($file['label'] !== 'Migration' && File::exists($file['target'])) {
                $overwrite = $this->confirm(
                    "File {$file['filename']} sudah ada, apakah ingin menimpa (overwrite)?",
                    false,
                );

                if (! $overwrite) {
                    $this->components->warn("Skipped: {$file['filename']}");
                    $results[] = [
                        'Component' => $file['label'],
                        'File'      => $file['filename'],
                        'Status'    => '⏭️ Skipped',
                    ];
                    continue;
                }
            }

            // For migration, check if a migration for this table already exists
            if ($file['label'] === 'Migration') {
                $existingMigrations = glob(database_path("migrations/*_create_{$tableName}_table.php"));
                if (! empty($existingMigrations)) {
                    $overwrite = $this->confirm(
                        "Migration untuk tabel '{$tableName}' sudah ada, apakah ingin membuat lagi?",
                        false,
                    );

                    if (! $overwrite) {
                        $this->components->warn("Skipped: Migration {$tableName}");
                        $results[] = [
                            'Component' => $file['label'],
                            'File'      => $file['filename'],
                            'Status'    => '⏭️ Skipped',
                        ];
                        continue;
                    }
                }
            }

            // Read stub content and replace placeholders
            $content = File::get($file['stub']);
            $content = str_replace(
                ['{{ModelName}}', '{{modelNameLower}}', '{{authMiddleware}}', '{{tableName}}'],
                [$modelName, $modelNameLower, $authMiddleware, $tableName],
                $content,
            );

            // Ensure target directory exists (cross-OS compatible)
            File::ensureDirectoryExists(dirname($file['target']));

            // Write the generated file
            File::put($file['target'], $content);

            $this->components->info("Created: {$file['filename']}");
            $results[] = [
                'Component' => $file['label'],
                'File'      => $file['target'],
                'Status'    => '✅ Created',
            ];
        }

        // --- Summary Output ---

        $this->newLine();
        $this->table(
            ['Component', 'File', 'Status'],
            $results,
        );

        $this->newLine();
        $this->components->info("🚀 CRUD boilerplate for [{$modelName}] generated successfully!");
        $this->info("   Mode       : {$dbType}");
        $this->info('   Auth       : ' . ($useAuth ? 'auth:sanctum' : 'None'));
        $this->info("   Table      : {$tableName}");

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info('   1. Edit migration di database/migrations/ — tambahkan kolom tabel');
        $this->info('   2. Edit model di app/Models/ — tambahkan $fillable');
        $this->info('   3. Edit request di app/Http/Requests/ — tambahkan validation rules');
        $this->info("   4. Jalankan: php artisan migrate");
        $this->info("   5. Tambahkan route di routes/api.php:");
        $this->info("      Route::apiResource('{$tableName}', \\App\\Http\\Controllers\\{$modelName}Controller::class);");

        return self::SUCCESS;
    }
}
