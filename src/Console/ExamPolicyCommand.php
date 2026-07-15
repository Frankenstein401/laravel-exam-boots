<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate authorization Policy for a model.
 *
 * Usage: php artisan exam:policy {name}
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamPolicyCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:policy {name : The name of the model to generate policy for}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate authorization Policy for a model with auto-registration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNameLower = Str::camel($rawName);

        $this->components->info("🛡️ Generating Policy for: {$modelName}");

        $policyPath = app_path("Policies/{$modelName}Policy.php");
        $stubPath = __DIR__ . '/../stubs/policy.stub';

        if (! File::exists($stubPath)) {
            $this->components->error("Stub policy tidak ditemukan di: {$stubPath}");
            return self::FAILURE;
        }

        if (File::exists($policyPath)) {
            if (! $this->confirmOverwrite($policyPath)) {
                $this->components->warn('Proses pembuatan policy dibatalkan.');
                return self::SUCCESS;
            }
        }

        $content = File::get($stubPath);
        $content = str_replace(
            ['{{ModelName}}', '{{modelNameLower}}'],
            [$modelName, $modelNameLower],
            $content
        );

        $created = false;
        if ($this->writeFile($policyPath, $content)) {
            $created = true;
        }

        // --- Auto Registration in AuthServiceProvider ---
        $authProviderPath = app_path('Providers/AuthServiceProvider.php');
        $registered = false;

        if (File::exists($authProviderPath)) {
            $authProviderContent = File::get($authProviderPath);
            $policyMapping = "\\App\\Models\\{$modelName}::class => \\App\\Policies\\{$modelName}Policy::class,";

            if (str_contains($authProviderContent, "{$modelName}Policy::class")) {
                $registered = true;
                $this->components->info("Policy {$modelName}Policy sudah terdaftar di AuthServiceProvider.");
            } else {
                if (str_contains($authProviderContent, 'protected $policies = [')) {
                    $newContent = str_replace(
                        'protected $policies = [',
                        "protected \$policies = [\n        {$policyMapping}",
                        $authProviderContent
                    );
                    if ($this->modifyFile($authProviderPath, $newContent, 'Registered Policy in AuthServiceProvider')) {
                        $registered = true;
                    }
                }
            }
        }

        // Persist operation log for exam:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog("exam:policy {$rawName}");
        }

        // --- Summary Output ---
        $results = [
            [
                'Component' => 'Policy Class',
                'File'      => $policyPath,
                'Status'    => $created ? '✅ Created' : '⏭️ Preview / Skipped',
            ],
            [
                'Component' => 'AuthServiceProvider Registration',
                'File'      => $authProviderPath,
                'Status'    => $registered ? '✅ Registered' : '⏭️ Skipped',
            ],
        ];

        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info("   1. Atur logika authorization di app/Policies/{$modelName}Policy.php");
        $this->info("   2. Gunakan di Controller dengan: \$this->authorize('update', \${$modelNameLower});");

        return self::SUCCESS;
    }
}
