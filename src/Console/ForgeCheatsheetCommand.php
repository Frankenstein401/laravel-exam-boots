<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots\Console;

use Illuminate\Console\Command;

/**
 * Artisan command to print formatted command cheatsheet.
 *
 * Usage: php artisan forge:cheatsheet
 *
 * @package NamaKamu\LaravelForgeBoots
 */
class ForgeCheatsheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forge:cheatsheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display quick command reference cheatsheet in terminal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Quick Reference Cheatsheet');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> GENERATE BOILERPLATE</>');
        $this->line('    <fg=green>php artisan forge:add Product</>                    # CRUD (Model, Migration, Controller, Service, Request, Resource)');
        $this->line('    <fg=green>php artisan forge:add Product --with-factory</>     # + Factory & Seeder');
        $this->line('    <fg=green>php artisan forge:add Product --belongsTo=User</>   # + Auto-relasi belongsTo + FK migration');
        $this->line('    <fg=green>php artisan forge:add Product --upload=image</>     # + Upload handler di Controller & Resource URL');
        $this->line('    <fg=green>php artisan forge:add Product --enum=status:active,inactive</> # + Enum Class, casting model, & migration');
        $this->line('    <fg=green>php artisan forge:add Product --soft-deletes</>     # + SoftDeletes trait & migration column');
        $this->line('    <fg=green>php artisan forge:add Product --web</>              # + Tailwind View Blade CRUD & Web Route');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> RELATIONSHIP</>');
        $this->line('    <fg=green>php artisan forge:relation</>                       # Interactive relationship builder (1:1, 1:N, N:M)');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> AUTHENTICATION</>');
        $this->line('    <fg=green>php artisan forge:auth</>                           # Setup JWT / Sanctum + routes + controller + Scramble API docs');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> ADMIN SEEDER</>');
        $this->line('    <fg=green>php artisan forge:seed-admin</>                     # Generate AdminUserSeeder + register in DatabaseSeeder');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> RESPONSE HELPER</>');
        $this->line('    <fg=green>php artisan forge:response</>                       # Generate ApiResponse trait (success & error response)');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> AUTHORIZATION</>');
        $this->line('    <fg=green>php artisan forge:policy Product</>                 # Generate Policy + auto-register');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> TESTING</>');
        $this->line('    <fg=green>php artisan forge:test Product</>                   # Generate PHPUnit feature test');
        $this->line('    <fg=green>php artisan forge:test Product --pest</>            # Generate Pest PHP test');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> EXPORT</>');
        $this->line('    <fg=green>php artisan forge:export Product</>                 # Generate Excel/PDF export boilerplate');
        $this->newLine();

        $this->line('  <fg=yellow;options=bold> DOCTOR & UNDO</>');
        $this->line('    <fg=green>php artisan forge:doctor</>                         # Pre-flight environment check');
        $this->line('    <fg=green>php artisan forge:undo</>                           # Revert/Undo operasi generator terakhir');
        $this->newLine();

        $this->line('  <fg=red;options=bold> TIPS UJIAN:</>');
        $this->line('    1. Jalankan <fg=yellow>forge:doctor</> dulu sebelum mulai ujian.');
        $this->line('    2. Setup auth menggunakan <fg=yellow>forge:auth</>.');
        $this->line('    3. Bikin User admin login awal memakai <fg=yellow>forge:seed-admin</>.');
        $this->line('    4. Generate CRUD: <fg=yellow>forge:add NamaModel --with-factory --belongsTo=Parent</>.');
        $this->line('    5. Buat relasi tambahan: <fg=yellow>forge:relation</>.');
        $this->line('    6. Buat test coverage: <fg=yellow>forge:test NamaModel</>.');
        $this->line('    7. Jangan lupa migrate & seed: <fg=yellow>php artisan migrate && php artisan db:seed</>.');
        $this->newLine();

        return self::SUCCESS;
    }
}
