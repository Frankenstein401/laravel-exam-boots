<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ForgeCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure directories exist
        File::ensureDirectoryExists(app_path('Models'));
        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::ensureDirectoryExists(app_path('Services'));
        File::ensureDirectoryExists(app_path('Http/Requests'));
        File::ensureDirectoryExists(app_path('Http/Resources'));
        File::ensureDirectoryExists(app_path('Enums'));
        File::ensureDirectoryExists(database_path('migrations'));
        File::ensureDirectoryExists(database_path('factories'));
        File::ensureDirectoryExists(database_path('seeders'));

        // Cleanup any leftover migrations
        foreach (glob(database_path('migrations/*_create_test_products_table.php')) as $mFile) {
            File::delete($mFile);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (File::exists(storage_path('forge-boots'))) {
            File::deleteDirectory(storage_path('forge-boots'));
        }
    }

    public function test_it_can_run_doctor_check(): void
    {
        $this->artisan('forge:doctor')
            ->assertSuccessful()
            ->expectsOutputToContain('🏥');
    }

    public function test_it_can_display_cheatsheet(): void
    {
        $this->artisan('forge:cheatsheet')
            ->assertSuccessful()
            ->expectsOutputToContain('🥾');
    }

    public function test_it_can_generate_admin_user_seeder_and_undo_it(): void
    {
        $seederFile = database_path('seeders/AdminUserSeeder.php');
        if (File::exists($seederFile)) {
            File::delete($seederFile);
        }

        // Run seed admin
        $this->artisan('forge:seed-admin --email=testadmin@example.com --password=adminpass --force')
            ->assertSuccessful();

        $this->assertTrue(File::exists($seederFile));
        $this->assertStringContainsString('testadmin@example.com', File::get($seederFile));
        $this->assertStringContainsString('adminpass', File::get($seederFile));

        // Read the timestamp from history.json to construct exact question
        $historyFile = storage_path('exam-boots/history.json');
        $history = json_decode(File::get($historyFile), true);
        $lastEntry = end($history);
        $timestamp = $lastEntry['timestamp'];

        // Revert using undo
        $this->artisan('forge:undo')
            ->expectsQuestion("Apakah Anda yakin ingin me-revert/undo operasi forge:seed-admin ({$timestamp})?", true)
            ->assertSuccessful();

        $this->assertFalse(File::exists($seederFile));
    }

    public function test_it_can_run_add_command_in_dry_run_mode(): void
    {
        // Delete TestProduct model before dry-run to avoid any overwrite prompt
        File::delete(app_path('Models/TestProduct.php'));

        $this->artisan('forge:add TestProduct --belongsTo=Category --with-factory --enum=status:active,inactive --dry-run --force')
            ->expectsQuestion('Apakah fitur ini membutuhkan Auth Middleware?', false)
            ->expectsChoice('Pilih tipe database operation:', 'Eloquent CRUD', ['Eloquent CRUD', 'Blank Service'])
            ->expectsQuestion('Terdeteksi relasi belongsTo(Category). Daftarkan sebagai nested route? (/categories/{parent}/test-products)', false)
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertSuccessful();
    }

    public function test_it_can_generate_crud_components_with_relations_enums_and_undo_them(): void
    {
        $modelFile = app_path('Models/TestProduct.php');
        $parentModelFile = app_path('Models/Category.php');
        $enumFile = app_path('Enums/Status.php');
        $factoryFile = database_path('factories/TestProductFactory.php');
        $seederFile = database_path('seeders/TestProductSeeder.php');
        $controllerFile = app_path('Http/Controllers/Api/TestProductController.php');
        $serviceFile = app_path('Services/TestProductService.php');
        $requestFile = app_path('Http/Requests/TestProductRequest.php');
        $resourceFile = app_path('Http/Resources/TestProductResource.php');

        // Cleanup before test
        File::delete(array_filter([
            $modelFile, $parentModelFile, $enumFile, $factoryFile, $seederFile,
            $controllerFile, $serviceFile, $requestFile, $resourceFile
        ], 'file_exists'));

        // Create parent model first (simulate existing model)
        File::put($parentModelFile, "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass Category extends Model\n{\n}\n");

        // Run add command
        $this->artisan('forge:add TestProduct --belongsTo=Category --with-factory --enum=status:active,inactive --soft-deletes --force')
            ->expectsQuestion('Apakah fitur ini membutuhkan Auth Middleware?', false)
            ->expectsChoice('Pilih tipe database operation:', 'Eloquent CRUD', ['Eloquent CRUD', 'Blank Service'])
            ->expectsQuestion('Terdeteksi relasi belongsTo(Category). Daftarkan sebagai nested route? (/categories/{parent}/test-products)', false)
            ->assertSuccessful();

        // 1. Check Model
        $this->assertTrue(File::exists($modelFile));
        $modelContent = File::get($modelFile);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\SoftDeletes;', $modelContent);
        $this->assertStringContainsString('use HasFactory, SoftDeletes;', $modelContent);
        $this->assertStringContainsString('public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo', $modelContent);
        $this->assertStringContainsString("'status' => \App\Enums\Status::class,", $modelContent);

        // 2. Check Parent Model hasMany reverse relation injection
        $this->assertTrue(File::exists($parentModelFile));
        $parentContent = File::get($parentModelFile);
        $this->assertStringContainsString('public function testProducts(): \Illuminate\Database\Eloquent\Relations\HasMany', $parentContent);

        // 3. Check Enum
        $this->assertTrue(File::exists($enumFile));
        $enumContent = File::get($enumFile);
        $this->assertStringContainsString('enum Status: string', $enumContent);
        $this->assertStringContainsString("case Active = 'active';", $enumContent);
        $this->assertStringContainsString("case Inactive = 'inactive';", $enumContent);

        // 4. Check Migration
        $migrations = glob(database_path('migrations/*_create_test_products_table.php'));
        $this->assertNotEmpty($migrations);
        $migrationContent = File::get($migrations[0]);
        $this->assertStringContainsString("\$table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();", $migrationContent);
        $this->assertStringContainsString("\$table->enum('status', ['active', 'inactive'])->default('active');", $migrationContent);
        $this->assertStringContainsString("\$table->softDeletes();", $migrationContent);

        // 5. Check Factory & Seeder
        $this->assertTrue(File::exists($factoryFile));
        $this->assertTrue(File::exists($seederFile));

        // 6. Check Request
        $this->assertTrue(File::exists($requestFile));
        $requestContent = File::get($requestFile);
        $this->assertStringContainsString("'category_id' => 'required|integer|exists:categories,id',", $requestContent);
        $this->assertStringContainsString("'status' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\Status::class)],", $requestContent);

        // Read the timestamp from history.json to construct exact question
        $historyFile = storage_path('exam-boots/history.json');
        $history = json_decode(File::get($historyFile), true);
        $lastEntry = end($history);
        $timestamp = $lastEntry['timestamp'];

        // 7. Revert using undo
        $this->artisan('forge:undo')
            ->expectsQuestion("Apakah Anda yakin ingin me-revert/undo operasi forge:add TestProduct ({$timestamp})?", true)
            ->assertSuccessful();

        // Verify generated files are deleted
        $this->assertFalse(File::exists($modelFile));
        $this->assertFalse(File::exists($enumFile));
        $this->assertFalse(File::exists($factoryFile));
        $this->assertFalse(File::exists($seederFile));
        $this->assertFalse(File::exists($controllerFile));
        $this->assertFalse(File::exists($serviceFile));
        $this->assertFalse(File::exists($requestFile));
        $this->assertFalse(File::exists($resourceFile));
        
        // Verify parent model reverse relation is reverted (back to original state)
        $revertedParentContent = File::get($parentModelFile);
        $this->assertStringNotContainsString('testProducts()', $revertedParentContent);

        // Verify migrations are deleted
        $this->assertEmpty(glob(database_path('migrations/*_create_test_products_table.php')));

        // Cleanup parent model
        File::delete($parentModelFile);
    }

    public function test_it_does_not_delete_pre_existing_files_on_undo(): void
    {
        $preExistingFile = app_path('Models/PreExisting.php');
        File::ensureDirectoryExists(dirname($preExistingFile));
        
        // Write initial content
        File::put($preExistingFile, "<?php\n\n// Original Content\n");

        // Run add command with --force (overwriting it)
        $this->artisan('forge:add PreExisting --force')
            ->expectsQuestion('Apakah fitur ini membutuhkan Auth Middleware?', false)
            ->expectsChoice('Pilih tipe database operation:', 'Eloquent CRUD', ['Eloquent CRUD', 'Blank Service'])
            ->assertSuccessful();

        // Verify the file was modified/overwritten
        $overwrittenContent = File::get($preExistingFile);
        $this->assertStringContainsString('class PreExisting', $overwrittenContent);
        $this->assertStringNotContainsString('// Original Content', $overwrittenContent);

        // Get timestamp for undo prompt
        $historyFile = storage_path('exam-boots/history.json');
        $history = json_decode(File::get($historyFile), true);
        $lastEntry = end($history);
        $timestamp = $lastEntry['timestamp'];

        // Run undo
        $this->artisan('forge:undo')
            ->expectsQuestion("Apakah Anda yakin ingin me-revert/undo operasi forge:add PreExisting ({$timestamp})?", true)
            ->assertSuccessful();

        // Verify the pre-existing file was RESTORED to original content, NOT DELETED!
        $this->assertTrue(File::exists($preExistingFile));
        $restoredContent = File::get($preExistingFile);
        $this->assertStringContainsString('// Original Content', $restoredContent);
        $this->assertStringNotContainsString('class PreExisting', $restoredContent);

        // Cleanup
        File::delete($preExistingFile);
        
        // Cleanup other generated files for PreExisting
        File::delete(app_path('Http/Controllers/Api/PreExistingController.php'));
        File::delete(app_path('Services/PreExistingService.php'));
        File::delete(app_path('Http/Requests/PreExistingRequest.php'));
        File::delete(app_path('Http/Resources/PreExistingResource.php'));
        foreach (glob(database_path('migrations/*_create_pre_existings_table.php')) as $mFile) {
            File::delete($mFile);
        }
    }
}
