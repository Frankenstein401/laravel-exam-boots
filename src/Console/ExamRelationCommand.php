<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate Eloquent relationships between two models.
 *
 * Injects relationship methods into both model files and generates
 * the corresponding foreign key / pivot table migration automatically.
 *
 * Inspired by NestJS module-based relationship patterns.
 *
 * Usage: php artisan exam:relation
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamRelationCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:relation 
                            {--dry-run : Preview operations without writing files} 
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Eloquent relationship between two models (hasOne, hasMany, belongsToMany) with migration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('🔗 Laravel Exam Boots — Relationship Generator');
        $this->newLine();

        // =============================================
        // Step 1: Ask parent & child model
        // =============================================
        /** @var string $parentRaw */
        $parentRaw = $this->ask('Nama Model Parent (induk)? Contoh: User, Category');

        if (empty($parentRaw)) {
            $this->components->error('Nama model parent tidak boleh kosong!');
            return self::FAILURE;
        }

        /** @var string $childRaw */
        $childRaw = $this->ask('Nama Model Child (anak)? Contoh: Post, Product');

        if (empty($childRaw)) {
            $this->components->error('Nama model child tidak boleh kosong!');
            return self::FAILURE;
        }

        $parent = Str::studly($parentRaw);
        $child = Str::studly($childRaw);

        $parentSnake = Str::snake($parent);       // user
        $childSnake = Str::snake($child);         // post
        $parentTable = Str::snake(Str::pluralStudly($parent));  // users
        $childTable = Str::snake(Str::pluralStudly($child));    // posts
        $childPlural = Str::camel(Str::pluralStudly($child));   // posts (camelCase)
        $childSingular = Str::camel($child);                    // post

        $this->newLine();
        $this->info("   Parent : {$parent} (tabel: {$parentTable})");
        $this->info("   Child  : {$child} (tabel: {$childTable})");
        $this->newLine();

        // =============================================
        // Step 2: Ask relation type
        // =============================================
        /** @var string $relationType */
        $relationType = $this->choice(
            'Pilih jenis relasi:',
            [
                'One to One   — (contoh: User hasOne Profile)',
                'One to Many  — (contoh: User hasMany Post)',
                'Many to Many — (contoh: User belongsToMany Role)',
            ],
            1,
        );

        // Parse the choice
        $isOneToOne = str_starts_with($relationType, 'One to One');
        $isManyToMany = str_starts_with($relationType, 'Many to Many');

        // =============================================
        // Step 3: Validate model files exist
        // =============================================
        $parentModelPath = app_path("Models/{$parent}.php");
        $childModelPath = app_path("Models/{$child}.php");

        if (! File::exists($parentModelPath)) {
            $this->components->error("Model {$parent} tidak ditemukan di app/Models/{$parent}.php");
            $this->info('   Jalankan dulu: php artisan exam:add ' . $parent);
            return self::FAILURE;
        }

        if (! File::exists($childModelPath)) {
            $this->components->error("Model {$child} tidak ditemukan di app/Models/{$child}.php");
            $this->info('   Jalankan dulu: php artisan exam:add ' . $child);
            return self::FAILURE;
        }

        /** @var array<int, array{Step: string, Status: string}> $results */
        $results = [];

        // =============================================
        // Step 4: Inject relationship methods into models
        // =============================================
        if ($isManyToMany) {
            $parentMethod = $this->buildBelongsToManyMethod($child, $childPlural);
            $parentPlural = Str::camel(Str::pluralStudly($parent));
            $childMethod = $this->buildBelongsToManyMethod($parent, $parentPlural);
        } elseif ($isOneToOne) {
            $parentMethod = $this->buildHasOneMethod($child, $childSingular);
            $childMethod = $this->buildBelongsToMethod($parent, Str::camel($parent));
        } else {
            $parentMethod = $this->buildHasManyMethod($child, $childPlural);
            $childMethod = $this->buildBelongsToMethod($parent, Str::camel($parent));
        }

        // Inject into parent model
        if ($this->injectMethodIntoModel($parentModelPath, $parentMethod, $parent)) {
            $results[] = ['Step' => "Inject relasi ke {$parent} model", 'Status' => '✅ Added'];
        } else {
            $results[] = ['Step' => "Inject relasi ke {$parent} model", 'Status' => '⚠️ Method mungkin sudah ada'];
        }

        // Inject into child model
        if ($this->injectMethodIntoModel($childModelPath, $childMethod, $child)) {
            $results[] = ['Step' => "Inject relasi ke {$child} model", 'Status' => '✅ Added'];
        } else {
            $results[] = ['Step' => "Inject relasi ke {$child} model", 'Status' => '⚠️ Method mungkin sudah ada'];
        }

        // =============================================
        // Step 5: Generate migration
        // =============================================
        $stubDir = __DIR__ . '/../stubs/';
        $timestamp = date('Y_m_d_His');

        if ($isManyToMany) {
            $names = [$parentSnake, $childSnake];
            sort($names);
            $pivotTable = implode('_', $names);

            $migrationName = "{$timestamp}_create_{$pivotTable}_table.php";
            $migrationPath = database_path("migrations/{$migrationName}");

            $stubContent = File::get($stubDir . 'relation-pivot-migration.stub');
            $stubContent = str_replace(
                ['{{pivotTable}}', '{{parentKey}}', '{{childKey}}', '{{parentTable}}', '{{childTable}}'],
                [$pivotTable, "{$names[0]}_id", "{$names[1]}_id", Str::snake(Str::pluralStudly(Str::studly($names[0]))), Str::snake(Str::pluralStudly(Str::studly($names[1])))],
                $stubContent,
            );
        } else {
            $foreignKey = "{$parentSnake}_id";
            $migrationName = "{$timestamp}_add_{$foreignKey}_to_{$childTable}_table.php";
            $migrationPath = database_path("migrations/{$migrationName}");

            $stubContent = File::get($stubDir . 'relation-migration.stub');
            $stubContent = str_replace(
                ['{{childTable}}', '{{foreignKey}}', '{{parentTable}}'],
                [$childTable, $foreignKey, $parentTable],
                $stubContent,
            );
        }

        if ($this->writeFile($migrationPath, $stubContent)) {
            $results[] = ['Step' => 'Migration', 'Status' => '✅ Created'];
        } else {
            $results[] = ['Step' => 'Migration', 'Status' => '⏭️ Preview / Skipped'];
        }

        // Persist operation log for exam:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog("exam:relation {$parent} {$child}");
        }

        // =============================================
        // Summary
        // =============================================
        $this->newLine();
        $this->table(['Step', 'Status'], $results);

        $this->newLine();
        $relationLabel = $isOneToOne ? 'One to One' : ($isManyToMany ? 'Many to Many' : 'One to Many');
        $this->components->info("🔗 Relasi [{$parent}] ←→ [{$child}] ({$relationLabel}) berhasil dibuat!");

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info('   1. Jalankan: php artisan migrate');

        return self::SUCCESS;
    }

    // =============================================
    // Relationship Method Builders
    // =============================================

    private function buildHasOneMethod(string $related, string $methodName): string
    {
        return <<<PHP

    /**
     * Get the {$methodName} associated with this model.
     */
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return \$this->hasOne(\App\Models\\{$related}::class);
    }
PHP;
    }

    private function buildHasManyMethod(string $related, string $methodName): string
    {
        return <<<PHP

    /**
     * Get the {$methodName} for this model.
     */
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany(\App\Models\\{$related}::class);
    }
PHP;
    }

    private function buildBelongsToMethod(string $related, string $methodName): string
    {
        return <<<PHP

    /**
     * Get the {$methodName} that owns this model.
     */
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return \$this->belongsTo(\App\Models\\{$related}::class);
    }
PHP;
    }

    private function buildBelongsToManyMethod(string $related, string $methodName): string
    {
        return <<<PHP

    /**
     * The {$methodName} that belong to this model.
     */
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return \$this->belongsToMany(\App\Models\\{$related}::class)->withTimestamps();
    }
PHP;
    }

    /**
     * Inject a relationship method into a model file.
     */
    private function injectMethodIntoModel(string $modelPath, string $methodCode, string $modelName): bool
    {
        $content = File::get($modelPath);
        $content = rtrim($content);

        if (preg_match('/public function (\w+)\(/', $methodCode, $matches)) {
            $methodName = $matches[1];

            if (str_contains($content, "function {$methodName}(")) {
                $this->components->warn("Method {$methodName}() sudah ada di {$modelName} model, dilewati.");
                return false;
            }
        }

        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos === false) {
            $this->components->error("Format model {$modelName} tidak valid (tidak ditemukan closing brace).");
            return false;
        }

        $newContent = substr_replace($content, $methodCode . "\n}", $lastBracePos, 1);

        return $this->modifyFile($modelPath, $newContent, "Injected relation method {$methodName}()");
    }
}
