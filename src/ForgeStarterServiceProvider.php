<?php

declare(strict_types=1);

namespace NamaKamu\LaravelForgeBoots;

use Illuminate\Support\ServiceProvider;
use NamaKamu\LaravelForgeBoots\Console\ForgeAddCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeAuthCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeCheatsheetCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeDoctorCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeExportCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeInstallCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgePolicyCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeRelationCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeResponseCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeSeedAdminCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeTestCommand;
use NamaKamu\LaravelForgeBoots\Console\ForgeUndoCommand;

/**
 * Service provider for the Laravel Forge Boots package.
 *
 * Registers Artisan commands and handles package auto-discovery
 * for the forge boilerplate generator.
 *
 * Register this provider in composer.json for auto-discovery:
 *   "extra": {
 *       "laravel": {
 *           "providers": [
 *               "NamaKamu\\LaravelForgeBoots\\ForgeStarterServiceProvider"
 *           ]
 *       }
 *   }
 *
 * @package NamaKamu\LaravelForgeBoots
 */
class ForgeStarterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services.
     *
     * Registers Artisan commands when the application is running
     * in the console environment.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/config/forge-boots.php' => config_path('forge-boots.php'),
            ], 'forge-boots-config');

            $this->commands([
                ForgeAddCommand::class,
                ForgeAuthCommand::class,
                ForgeRelationCommand::class,
                ForgeResponseCommand::class,
                ForgePolicyCommand::class,
                ForgeTestCommand::class,
                ForgeDoctorCommand::class,
                ForgeExportCommand::class,
                ForgeCheatsheetCommand::class,
                ForgeInstallCommand::class,
                ForgeUndoCommand::class,
                ForgeSeedAdminCommand::class,
            ]);
        }
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/forge-boots.php',
            'forge-boots'
        );
    }
}
