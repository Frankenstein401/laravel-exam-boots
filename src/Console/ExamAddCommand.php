<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate CRUD boilerplate components.
 *
 * Usage: php artisan exam:add {name}
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamAddCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:add {name : The name of the component to generate}
                            {--belongsTo=* : Parent model(s), e.g. --belongsTo=Order}
                            {--hasMany=* : Child model(s), e.g. --hasMany=Review}
                            {--hasOne=* : One-to-one child model}
                            {--belongsToMany=* : Pivot relation model}
                            {--with-factory : Generate Factory & Seeder}
                            {--upload=* : File upload fields, e.g. --upload=image}
                            {--web : Generate HTML/Blade views and web routing}
                            {--enum=* : Enum fields, format: field:value1,value2,value3}
                            {--soft-deletes : Add SoftDeletes support to model and migration}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD boilerplate components (Model, Migration, Controller, Service, Request, Resource, Blade Views, Factory, Seeder, Enums, SoftDeletes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNameLower = Str::camel($rawName);
        $tableName = Str::snake(Str::pluralStudly($modelName));

        $isWeb = $this->option('web');
        $softDeletes = $this->option('soft-deletes');

        $this->components->info("Generating CRUD boilerplate for: {$modelName} (" . ($isWeb ? 'Web MVC Mode' : 'API Mode') . ")");
        $this->newLine();

        // --- Interactive Prompts / Config Defaults ---
        /** @var bool $useAuth */
        $useAuth = $this->confirm('Apakah fitur ini membutuhkan Auth Middleware?', true);

        // Naming / Config defaults
        $configCrudType = config('exam-boots.defaults.crud_type', 'eloquent');
        $defaultChoice = ($configCrudType === 'blank') ? 1 : 0;

        /** @var string $dbType */
        $dbType = $this->choice(
            'Pilih tipe database operation:',
            ['Eloquent CRUD', 'Blank Service'],
            $defaultChoice
        );

        $authMiddleware = '';
        if ($useAuth) {
            $authMiddleware = $isWeb ? "\$this->middleware('auth');" : "\$this->middleware('auth:sanctum');";
        }

        // --- Options ---
        $belongsTo = $this->option('belongsTo');
        $hasMany = $this->option('hasMany');
        $hasOne = $this->option('hasOne');
        $belongsToMany = $this->option('belongsToMany');
        $withFactory = $this->option('with-factory');
        $upload = $this->option('upload');

        // Parse Enums
        $enums = [];
        foreach ($this->option('enum') as $enumItem) {
            if (str_contains($enumItem, ':')) {
                [$field, $valuesRaw] = explode(':', $enumItem, 2);
                $enums[$field] = explode(',', $valuesRaw);
            }
        }

        $stubDir = __DIR__ . '/../stubs/';
        $isEloquent = $dbType === 'Eloquent CRUD';
        $migrationFileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";

        // --- Build Relationship Methods, Columns, Rules ---
        $relationshipsStr = $this->buildRelationshipMethods([
            'belongsTo' => $belongsTo,
            'hasMany' => $hasMany,
            'hasOne' => $hasOne,
            'belongsToMany' => $belongsToMany,
        ]);

        $foreignKeysStr = $this->buildForeignKeys($belongsTo);
        $uploadColumnsStr = $this->buildUploadColumns($upload);
        $enumColumnsStr = $this->buildEnumColumns($enums);
        $rulesStr = $this->buildValidationRules($belongsTo, $upload, $enums);
        $resourceExtraStr = $this->buildResourceExtra($upload);
        $enumCastsStr = $this->buildEnumCasts($enums);

        // Soft delete placeholders
        $softDeletesMigrationStr = $softDeletes ? "\$table->softDeletes();" : '';
        $softDeletesImportStr = $softDeletes ? "use Illuminate\Database\Eloquent\SoftDeletes;" : '';
        $softDeletesTraitStr = $softDeletes ? ", SoftDeletes" : '';

        $filesToGenerate = [
            [
                'id'       => 'model',
                'label'    => 'Model',
                'stub'     => $stubDir . 'model.stub',
                'target'   => app_path("Models/{$modelName}.php"),
                'filename' => "{$modelName}.php",
            ],
            [
                'id'       => 'migration',
                'label'    => 'Migration',
                'stub'     => $stubDir . 'migration.stub',
                'target'   => database_path("migrations/{$migrationFileName}"),
                'filename' => $migrationFileName,
            ],
            [
                'id'       => 'controller',
                'label'    => 'Controller',
                'stub'     => $isWeb ? ($stubDir . 'web-controller.stub') : ($stubDir . ($isEloquent ? 'controller.stub' : 'controller.blank.stub')),
                'target'   => $isWeb ? app_path("Http/Controllers/{$modelName}Controller.php") : app_path("Http/Controllers/Api/{$modelName}Controller.php"),
                'filename' => $isWeb ? "{$modelName}Controller.php" : "Api/{$modelName}Controller.php",
            ],
            [
                'id'       => 'service',
                'label'    => 'Service',
                'stub'     => $stubDir . ($isEloquent ? 'service.stub' : 'service.blank.stub'),
                'target'   => app_path("Services/{$modelName}Service.php"),
                'filename' => "{$modelName}Service.php",
            ],
            [
                'id'       => 'request',
                'label'    => 'Request',
                'stub'     => $stubDir . 'request.stub',
                'target'   => app_path("Http/Requests/{$modelName}Request.php"),
                'filename' => "{$modelName}Request.php",
            ],
        ];

        // API Mode adds Resource
        if (! $isWeb) {
            $filesToGenerate[] = [
                'id'       => 'resource',
                'label'    => 'Resource',
                'stub'     => $stubDir . 'resource.stub',
                'target'   => app_path("Http/Resources/{$modelName}Resource.php"),
                'filename' => "{$modelName}Resource.php",
            ];
        } else {
            // Web Mode adds Blade Views
            $filesToGenerate[] = [
                'id'       => 'view_index',
                'label'    => 'Index View (Blade)',
                'stub'     => $stubDir . 'view-index.stub',
                'target'   => resource_path("views/{$tableName}/index.blade.php"),
                'filename' => "views/{$tableName}/index.blade.php",
            ];
            $filesToGenerate[] = [
                'id'       => 'view_create',
                'label'    => 'Create View (Blade)',
                'stub'     => $stubDir . 'view-create.stub',
                'target'   => resource_path("views/{$tableName}/create.blade.php"),
                'filename' => "views/{$tableName}/create.blade.php",
            ];
            $filesToGenerate[] = [
                'id'       => 'view_edit',
                'label'    => 'Edit View (Blade)',
                'stub'     => $stubDir . 'view-edit.stub',
                'target'   => resource_path("views/{$tableName}/edit.blade.php"),
                'filename' => "views/{$tableName}/edit.blade.php",
            ];
            $filesToGenerate[] = [
                'id'       => 'view_show',
                'label'    => 'Show View (Blade)',
                'stub'     => $stubDir . 'view-show.stub',
                'target'   => resource_path("views/{$tableName}/show.blade.php"),
                'filename' => "views/{$tableName}/show.blade.php",
            ];
        }

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
            if ($file['label'] !== 'Migration' && File::exists($file['target'])) {
                if (! $this->confirmOverwrite($file['target'])) {
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
                    if (! $this->confirm("Migration untuk tabel '{$tableName}' sudah ada, apakah ingin membuat lagi?", false)) {
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

            if ($file['id'] === 'controller' && ! $isWeb) {
                $content = str_replace(
                    'namespace App\Http\Controllers;',
                    "namespace App\Http\Controllers\Api;\n\nuse App\Http\Controllers\Controller;",
                    $content
                );
            }

            $content = str_replace(
                [
                    '{{ModelName}}',
                    '{{modelNameLower}}',
                    '{{authMiddleware}}',
                    '{{tableName}}',
                    '{{foreignKeys}}',
                    '{{uploadColumns}}',
                    '{{enumColumns}}',
                    '{{softDeletes}}',
                    '{{softDeletesImport}}',
                    '{{softDeletesTrait}}',
                    '{{relationships}}',
                    '{{rules}}',
                    '{{resourceExtra}}',
                    '{{casts}}'
                ],
                [
                    $modelName,
                    $modelNameLower,
                    $authMiddleware,
                    $tableName,
                    $foreignKeysStr,
                    $uploadColumnsStr,
                    $enumColumnsStr,
                    $softDeletesMigrationStr,
                    $softDeletesImportStr,
                    $softDeletesTraitStr,
                    $relationshipsStr,
                    $rulesStr,
                    $resourceExtraStr,
                    $enumCastsStr
                ],
                $content,
            );

            // Model adjustment: Add fillable upload and enum columns if any
            if ($file['id'] === 'model') {
                $mergedFields = collect($upload)->merge(array_keys($enums));
                if ($mergedFields->isNotEmpty()) {
                    $fillableFields = $mergedFields->map(fn($f) => "'" . Str::snake($f) . "'")->implode(",\n        ");
                    $content = str_replace(
                        "protected \$fillable = [\n        //\n    ];",
                        "protected \$fillable = [\n        {$fillableFields}\n    ];",
                        $content
                    );
                }
            }

            // Controller adjustment: Inject file upload methods
            if ($file['id'] === 'controller' && ! empty($upload)) {
                $uploadMethods = [];
                foreach ($upload as $f) {
                    $fSnake = Str::snake($f);
                    $fStudly = Str::studly($f);
                    $uploadMethods[] = <<<PHP

    /**
     * Upload {$fSnake} for the resource.
     */
    public function upload{$fStudly}(\Illuminate\Http\Request \$request, \$id): \Illuminate\Http\JsonResponse
    {
        \$request->validate([
            '{$fSnake}' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:2048'],
        ]);

        \${$modelNameLower} = \$this->{$modelNameLower}Service->getDetail{$ModelName}(\$id);

        \$path = \$request->file('{$fSnake}')->store('{$tableName}', 'public');

        \${$modelNameLower}->update(['{$fSnake}' => \$path]);

        return response()->json([
            'status' => 'success',
            'data' => [
                '{$fSnake}' => \$path,
                'url' => asset('storage/' . \$path),
            ],
        ]);
    }
PHP;
                }
                $uploadMethodsStr = implode("\n", $uploadMethods);
                $lastBracePos = strrpos($content, '}');
                if ($lastBracePos !== false) {
                    $content = substr($content, 0, $lastBracePos) . $uploadMethodsStr . "\n" . substr($content, $lastBracePos);
                }
            }

            // Write the generated file using TracksFileOperations
            if ($this->writeFile($file['target'], $content)) {
                $results[] = [
                    'Component' => $file['label'],
                    'File'      => $file['target'],
                    'Status'    => '✅ Created',
                ];
            } else {
                $results[] = [
                    'Component' => $file['label'],
                    'File'      => $file['target'],
                    'Status'    => '⏭️ Preview / Skipped',
                ];
            }
        }

        // --- Generate Enum Classes ---
        foreach ($enums as $field => $values) {
            $this->generateEnumClass($field, $values, $results);
        }

        // --- Inject Reverse Relations into existing Parent/Child Models ---
        foreach ($belongsTo as $parent) {
            $this->injectReverseRelation($parent, $modelName, 'hasMany');
        }
        foreach ($hasMany as $child) {
            $this->injectReverseRelation($child, $modelName, 'belongsTo');
        }
        foreach ($hasOne as $child) {
            $this->injectReverseRelation($child, $modelName, 'belongsTo');
        }
        foreach ($belongsToMany as $pivot) {
            $this->injectReverseRelation($pivot, $modelName, 'belongsToMany');
        }

        // --- Generate Factory & Seeder if requested ---
        if ($withFactory) {
            $factoryPath = database_path("factories/{$modelName}Factory.php");
            $factoryStub = $stubDir . 'factory.stub';
            if ($this->confirmOverwrite($factoryPath)) {
                $factoryContent = File::get($factoryStub);
                $factoryContent = str_replace(['{{ModelName}}', '{{modelNameLower}}'], [$modelName, $modelNameLower], $factoryContent);
                if ($this->writeFile($factoryPath, $factoryContent)) {
                    $results[] = ['Component' => 'Factory', 'File' => $factoryPath, 'Status' => '✅ Created'];
                }
            }

            $seederPath = database_path("seeders/{$modelName}Seeder.php");
            $seederStub = $stubDir . 'seeder.stub';
            if ($this->confirmOverwrite($seederPath)) {
                $seederContent = File::get($seederStub);
                $seederContent = str_replace(['{{ModelName}}', '{{modelNameLower}}'], [$modelName, $modelNameLower], $seederContent);
                if ($this->writeFile($seederPath, $seederContent)) {
                    $results[] = ['Component' => 'Seeder', 'File' => $seederPath, 'Status' => '✅ Created'];
                }
            }
        }

        // --- Register Resource Route ---
        $routeFile = $isWeb ? 'routes/web.php' : 'routes/api.php';
        $routePath = base_path($routeFile);

        if (File::exists($routePath)) {
            $routeContent = File::get($routePath);
            $routeLine = $isWeb 
                ? "Route::resource('{$tableName}', \\App\\Http\\Controllers\\{$modelName}Controller::class);"
                : "Route::apiResource('{$tableName}', \\App\\Http\\Controllers\\Api\\{$modelName}Controller::class);";

            if (! str_contains($routeContent, "{$modelName}Controller::class")) {
                if ($this->modifyFile($routePath, $routeContent . "\n" . $routeLine, 'Registered resource route')) {
                    $results[] = ['Component' => 'Route Registration', 'File' => $routeFile, 'Status' => '✅ Registered'];
                }
            } else {
                $results[] = ['Component' => 'Route Registration', 'File' => $routeFile, 'Status' => '⏭️ Already exists'];
            }
        }

        // --- Ask for Nested Resource Routes ---
        $registeredNested = false;
        if (! empty($belongsTo)) {
            $primaryParent = $belongsTo[0];
            $parentPluralKebab = Str::kebab(Str::plural($primaryParent));
            $childPluralKebab = Str::kebab(Str::plural($modelName));
            $this->newLine();
            if ($this->confirm("Terdeteksi relasi belongsTo({$primaryParent}). Daftarkan sebagai nested route? (/{$parentPluralKebab}/{parent}/{$childPluralKebab})", true)) {
                $apiRoutePath = base_path('routes/api.php');
                if (File::exists($apiRoutePath)) {
                    $routeInject = "\nRoute::apiResource('{$parentPluralKebab}.{$childPluralKebab}', \\App\\Http\\Controllers\\Api\\{$modelName}Controller::class);";
                    if ($this->modifyFile($apiRoutePath, File::get($apiRoutePath) . $routeInject, 'Registered nested resource route')) {
                        $registeredNested = true;
                    }
                }
            }
        }

        // Persist operation log for exam:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog("exam:add {$rawName}");
        }

        // --- Summary Table ---
        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->info("🚀 CRUD boilerplate for [{$modelName}] generated successfully!");

        if ($registeredNested) {
            $this->info("   Nested Route: Route::apiResource('{$parentPluralKebab}.{$childPluralKebab}', ...)");
        }

        return self::SUCCESS;
    }

    /**
     * Build foreign keys for migration.
     */
    private function buildForeignKeys(array $belongsTo): string
    {
        return collect($belongsTo)
            ->map(function ($parent) {
                $fk = Str::snake($parent) . '_id';
                $table = Str::snake(Str::pluralStudly($parent));
                return "\$table->foreignId('{$fk}')->constrained('{$table}')->cascadeOnDelete();";
            })
            ->implode("\n            ");
    }

    /**
     * Build upload columns for migration.
     */
    private function buildUploadColumns(array $upload): string
    {
        return collect($upload)
            ->map(function ($field) {
                $col = Str::snake($field);
                return "\$table->string('{$col}')->nullable();";
            })
            ->implode("\n            ");
    }

    /**
     * Build enum columns for migration.
     */
    protected function buildEnumColumns(array $enums): string
    {
        return collect($enums)
            ->map(function ($values, $field) {
                $valueList = collect($values)->map(fn($v) => "'{$v}'")->implode(', ');
                $default = $values[0];
                return "\$table->enum('{$field}', [{$valueList}])->default('{$default}');";
            })
            ->implode("\n            ");
    }

    /**
     * Build validation rules for Request class.
     */
    private function buildValidationRules(array $belongsTo, array $upload, array $enums): string
    {
        $rules = [];

        // BelongsTo parent exists rules
        foreach ($belongsTo as $parent) {
            $fk = Str::snake($parent) . '_id';
            $table = Str::snake(Str::pluralStudly($parent));
            $rules[$fk] = "'required|integer|exists:{$table},id'";
        }

        // Upload rules
        foreach ($upload as $field) {
            $rules[$field] = "'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'";
        }

        // Enum rules
        foreach ($enums as $field => $values) {
            $enumClass = "\\App\\Enums\\" . Str::studly($field);
            $rules[$field] = "['required', new \\Illuminate\\Validation\\Rules\\Enum({$enumClass}::class)]";
        }

        if (empty($rules)) {
            return '';
        }

        return collect($rules)
            ->map(fn($rule, $field) => "'{$field}' => {$rule},")
            ->implode("\n            ");
    }

    /**
     * Build resource extra mapping array.
     */
    private function buildResourceExtra(array $upload): string
    {
        return collect($upload)
            ->map(function ($field) {
                $col = Str::snake($field);
                return "'{$col}_url' => \$this->{$col} ? asset('storage/' . \$this->{$col}) : null,";
            })
            ->implode("\n                ");
    }

    /**
     * Build enum casts mapping for Model casts() method.
     */
    protected function buildEnumCasts(array $enums): string
    {
        return collect($enums)
            ->map(fn($values, $field) => "'{$field}' => \\App\\Enums\\" . Str::studly($field) . "::class,")
            ->implode("\n            ");
    }

    /**
     * Build relationship methods to inject into Model class.
     */
    private function buildRelationshipMethods(array $options): string
    {
        $methods = [];

        foreach ($options['belongsTo'] ?? [] as $parent) {
            $method = Str::camel($parent);
            $methods[] = <<<PHP
    public function {$method}(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return \$this->belongsTo(\App\Models\\{$parent}::class);
    }
PHP;
        }

        foreach ($options['hasMany'] ?? [] as $child) {
            $method = Str::camel(Str::plural($child));
            $methods[] = <<<PHP
    public function {$method}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany(\App\Models\\{$child}::class);
    }
PHP;
        }

        foreach ($options['hasOne'] ?? [] as $child) {
            $method = Str::camel($child);
            $methods[] = <<<PHP
    public function {$method}(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return \$this->hasOne(\App\Models\\{$child}::class);
    }
PHP;
        }

        foreach ($options['belongsToMany'] ?? [] as $pivot) {
            $method = Str::camel(Str::plural($pivot));
            $methods[] = <<<PHP
    public function {$method}(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return \$this->belongsToMany(\App\Models\\{$pivot}::class)->withTimestamps();
    }
PHP;
        }

        if (empty($methods)) {
            return '';
        }

        return "\n" . implode("\n\n", $methods) . "\n";
    }

    /**
     * Generate Enum class.
     */
    protected function generateEnumClass(string $field, array $values, array &$results): void
    {
        $enumName = Str::studly($field);
        $enumPath = app_path("Enums/{$enumName}.php");

        $cases = collect($values)
            ->map(fn($v) => "    case " . Str::studly($v) . " = '{$v}';")
            ->implode("\n");

        $labelCases = collect($values)
            ->map(fn($v) => "            self::" . Str::studly($v) . " => '" . ucfirst($v) . "',")
            ->implode("\n");

        $stub = <<<PHP
<?php

namespace App\Enums;

enum {$enumName}: string
{
{$cases}

    public function label(): string
    {
        return match(\$this) {
{$labelCases}
        };
    }
}
PHP;

        if ($this->writeFile($enumPath, $stub)) {
            $results[] = [
                'Component' => "Enum ({$enumName})",
                'File'      => $enumPath,
                'Status'    => '✅ Created',
            ];
        } else {
            $results[] = [
                'Component' => "Enum ({$enumName})",
                'File'      => $enumPath,
                'Status'    => '⏭️ Preview / Skipped',
            ];
        }
    }

    /**
     * Inject reverse relation into an existing model.
     */
    protected function injectReverseRelation(string $targetModel, string $sourceModel, string $type): void
    {
        $path = app_path("Models/{$targetModel}.php");

        if (! File::exists($path)) {
            $this->components->warn("Target model {$targetModel} tidak ditemukan di: {$path}. Skip reverse relation.");
            return;
        }

        $content = File::get($path);
        // Ensure no trailing spaces/newlines disrupt finding the last brace
        $content = rtrim($content);

        // Determine method name based on relation type
        if ($type === 'hasMany' || $type === 'belongsToMany') {
            $methodName = Str::camel(Str::plural($sourceModel));
        } elseif ($type === 'hasOne') {
            $methodName = Str::camel($sourceModel);
        } else {
            $methodName = Str::camel($sourceModel); // Default belongsTo
        }

        // Guard: don't inject if method already exists
        if (str_contains($content, "function {$methodName}(")) {
            $this->components->warn("Relasi {$methodName}() sudah ada di {$targetModel}, skip.");
            return;
        }

        // Build relationship code
        if ($type === 'hasMany') {
            $relationCode = <<<PHP

    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany(\App\Models\\{$sourceModel}::class);
    }
PHP;
        } elseif ($type === 'hasOne') {
            $relationCode = <<<PHP

    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return \$this->hasOne(\App\Models\\{$sourceModel}::class);
    }
PHP;
        } elseif ($type === 'belongsToMany') {
            $relationCode = <<<PHP

    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return \$this->belongsToMany(\App\Models\\{$sourceModel}::class)->withTimestamps();
    }
PHP;
        } else {
            $relationCode = <<<PHP

    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return \$this->belongsTo(\App\Models\\{$sourceModel}::class);
    }
PHP;
        }

        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false) {
            $newContent = substr_replace($content, $relationCode . "\n}", $lastBracePos, 1);
            $this->modifyFile($path, $newContent, "Injected reverse relation {$methodName}()");
        }
    }
}
