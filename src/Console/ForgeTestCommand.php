<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NamaKamu\LaravelForgeBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate feature test skeleton.
 *
 * Usage: php artisan forge:test {name} {--pest}
 *
 * @package NamaKamu\LaravelForgeBoots
 */
class ForgeTestCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forge:test {name : The name of the model to generate tests for}
                            {--pest : Use Pest PHP syntax}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate feature test skeleton for CRUD + authorization';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNameLower = Str::camel($rawName);
        $tableName = Str::snake(Str::pluralStudly($modelName));

        $usePest = $this->option('pest');

        if (! $usePest) {
            $configFramework = config('forge-boots.defaults.test_framework', 'phpunit');
            if (strtolower($configFramework) === 'pest') {
                $usePest = true;
            } else {
                $choice = $this->choice(
                    'Pilih framework test:',
                    ['PHPUnit', 'Pest PHP'],
                    0
                );
                $usePest = ($choice === 'Pest PHP');
            }
        }

        $framework = $usePest ? 'Pest PHP' : 'PHPUnit';
        $this->components->info("Generating {$framework} Feature Test for: {$modelName}");

        $stubFile = $usePest ? 'test-pest.stub' : 'test-phpunit.stub';
        $stubPath = __DIR__ . "/../stubs/{$stubFile}";
        $testPath = base_path("tests/Feature/{$modelName}Test.php");

        if (! File::exists($stubPath)) {
            $this->components->error("Stub test tidak ditemukan di: {$stubPath}");
            return self::FAILURE;
        }

        if (File::exists($testPath)) {
            if (! $this->confirmOverwrite($testPath)) {
                $this->components->warn('Proses pembuatan test dibatalkan.');
                return self::SUCCESS;
            }
        }

        $content = File::get($stubPath);
        $content = str_replace(
            ['{{ModelName}}', '{{modelNameLower}}', '{{tableName}}'],
            [$modelName, $modelNameLower, $tableName],
            $content
        );

        $created = false;
        if ($this->writeFile($testPath, $content)) {
            $created = true;
        }

        // Persist operation log for forge:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog("forge:test {$rawName}");
        }

        // --- Summary Output ---
        $results = [
            [
                'Component' => "Feature Test ({$framework})",
                'File'      => $testPath,
                'Status'    => $created ? 'Created' : 'Preview / Skipped',
            ]
        ];

        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        if ($usePest) {
            $this->info("   1. Jalankan test dengan Pest: ./vendor/bin/pest tests/Feature/{$modelName}Test.php");
        } else {
            $this->info("   1. Jalankan test dengan phpunit: php artisan test tests/Feature/{$modelName}Test.php");
        }

        return self::SUCCESS;
    }
}
