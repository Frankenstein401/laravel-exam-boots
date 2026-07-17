<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NamaKamu\LaravelForgeBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate AdminUserSeeder.
 *
 * Usage: php artisan forge:seed-admin
 *
 * @package NamaKamu\LaravelForgeBoots
 */
class ForgeSeedAdminCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forge:seed-admin
                            {--email=admin@example.com : Email address for the admin user}
                            {--password=password : Plaintext password for the admin user}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a default Admin User Seeder for testing authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Admin User Seeder Generator');
        $this->newLine();

        $email = $this->option('email');
        $password = $this->option('password');

        $seederPath = database_path('seeders/AdminUserSeeder.php');
        $seederClassContent = <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => '{$email}'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('{$password}'),
                'email_verified_at' => now(),
            ]
        );
    }
}
PHP;

        if (File::exists($seederPath)) {
            if (! $this->confirmOverwrite($seederPath)) {
                $this->components->warn('Pembuatan AdminUserSeeder dibatalkan.');
                return self::SUCCESS;
            }
        }

        $created = false;
        if ($this->writeFile($seederPath, $seederClassContent)) {
            $created = true;
        }

        // --- Register in DatabaseSeeder ---
        $dbSeederPath = database_path('seeders/DatabaseSeeder.php');
        $registered = false;

        if (File::exists($dbSeederPath)) {
            $dbSeederContent = File::get($dbSeederPath);

            if (str_contains($dbSeederContent, 'AdminUserSeeder')) {
                $registered = true;
                $this->components->info('AdminUserSeeder sudah terdaftar di DatabaseSeeder.');
            } else {
                // Find run() method signature
                $pos = strpos($dbSeederContent, 'public function run()');
                if ($pos === false) {
                    $pos = strpos($dbSeederContent, 'public function run(): void');
                }

                if ($pos !== false) {
                    // Find the first '{' after the signature
                    $bracePos = strpos($dbSeederContent, '{', $pos);
                    if ($bracePos !== false) {
                        $newContent = substr($dbSeederContent, 0, $bracePos + 1) . "\n        \$this->call(AdminUserSeeder::class);" . substr($dbSeederContent, $bracePos + 1);
                        if ($this->modifyFile($dbSeederPath, $newContent, 'Registered AdminUserSeeder in DatabaseSeeder')) {
                            $registered = true;
                        }
                    }
                }
            }
        }

        // Persist operation log for forge:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog('forge:seed-admin');
        }

        // --- Summary Output ---
        $results = [
            [
                'Component' => 'AdminUserSeeder Class',
                'File'      => $seederPath,
                'Status'    => $created ? 'Created' : 'Preview / Skipped',
            ],
            [
                'Component' => 'DatabaseSeeder Registration',
                'File'      => $dbSeederPath,
                'Status'    => $registered ? 'Registered' : 'Skipped',
            ],
        ];

        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        $this->info('   1. Jalankan seeder admin: php artisan db:seed --class=AdminUserSeeder');
        $this->info("   2. Gunakan untuk login: email = {$email}, password = {$password}");

        return self::SUCCESS;
    }
}
