<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots;

use Illuminate\Support\ServiceProvider;
use NamaKamu\LaravelExamBoots\Console\ExamAddCommand;
use NamaKamu\LaravelExamBoots\Console\ExamAuthCommand;
use NamaKamu\LaravelExamBoots\Console\ExamCheatsheetCommand;
use NamaKamu\LaravelExamBoots\Console\ExamDoctorCommand;
use NamaKamu\LaravelExamBoots\Console\ExamExportCommand;
use NamaKamu\LaravelExamBoots\Console\ExamPolicyCommand;
use NamaKamu\LaravelExamBoots\Console\ExamRelationCommand;
use NamaKamu\LaravelExamBoots\Console\ExamResponseCommand;
use NamaKamu\LaravelExamBoots\Console\ExamSeedAdminCommand;
use NamaKamu\LaravelExamBoots\Console\ExamTestCommand;
use NamaKamu\LaravelExamBoots\Console\ExamUndoCommand;

/**
 * Service provider for the Laravel Exam Boots package.
 *
 * Registers Artisan commands and handles package auto-discovery
 * for the exam boilerplate generator.
 *
 * Register this provider in composer.json for auto-discovery:
 *   "extra": {
 *       "laravel": {
 *           "providers": [
 *               "NamaKamu\\LaravelExamBoots\\ExamStarterServiceProvider"
 *           ]
 *       }
 *   }
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamStarterServiceProvider extends ServiceProvider
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
                __DIR__ . '/config/exam-boots.php' => config_path('exam-boots.php'),
            ], 'exam-boots-config');

            $this->commands([
                ExamAddCommand::class,
                ExamAuthCommand::class,
                ExamRelationCommand::class,
                ExamResponseCommand::class,
                ExamPolicyCommand::class,
                ExamTestCommand::class,
                ExamDoctorCommand::class,
                ExamExportCommand::class,
                ExamCheatsheetCommand::class,
                ExamUndoCommand::class,
                ExamSeedAdminCommand::class,
            ]);
        }
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/exam-boots.php',
            'exam-boots'
        );
    }
}
