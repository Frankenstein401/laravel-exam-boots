<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots;

use Illuminate\Support\ServiceProvider;
use NamaKamu\LaravelExamBoots\Console\ExamAddCommand;
use NamaKamu\LaravelExamBoots\Console\ExamAuthCommand;
use NamaKamu\LaravelExamBoots\Console\ExamResponseCommand;

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
            $this->commands([
                ExamAddCommand::class,
                ExamAuthCommand::class,
                ExamResponseCommand::class,
            ]);
        }
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        //
    }
}
