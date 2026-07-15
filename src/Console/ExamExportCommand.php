<?php

declare(strict_types=1);

namespace NamaKamu\LaravelExamBoots\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NamaKamu\LaravelExamBoots\Concerns\TracksFileOperations;

/**
 * Artisan command to generate data export boilerplate.
 *
 * Usage: php artisan exam:export {name}
 *
 * @package NamaKamu\LaravelExamBoots
 */
class ExamExportCommand extends Command
{
    use TracksFileOperations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:export {name : The name of the model to generate exports for}
                            {--dry-run : Preview operations without writing files}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Excel/CSV and PDF export helper classes for a model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNameLower = Str::camel($rawName);
        $tableName = Str::snake(Str::pluralStudly($modelName));

        $this->components->info("📊 Generating Export Boilerplate for: {$modelName}");

        $format = $this->choice(
            'Pilih format export:',
            ['Excel/CSV (maatwebsite/excel)', 'PDF (barryvdh/laravel-dompdf)', 'Keduanya'],
            0
        );

        $stubDir = __DIR__ . '/../stubs/';
        $results = [];

        $wantsExcel = ($format === 'Excel/CSV (maatwebsite/excel)' || $format === 'Keduanya');
        $wantsPdf = ($format === 'PDF (barryvdh/laravel-dompdf)' || $format === 'Keduanya');

        if ($wantsExcel) {
            $excelStub = $stubDir . 'export-excel.stub';
            $excelPath = app_path("Exports/{$modelName}Export.php");

            $proceed = true;
            if (File::exists($excelPath)) {
                $proceed = $this->confirmOverwrite($excelPath);
            }

            if ($proceed && File::exists($excelStub)) {
                $content = File::get($excelStub);
                $content = str_replace(
                    ['{{ModelName}}', '{{modelNameLower}}', '{{tableName}}'],
                    [$modelName, $modelNameLower, $tableName],
                    $content
                );
                if ($this->writeFile($excelPath, $content)) {
                    $results[] = ['Component' => 'Excel Export Class', 'File' => $excelPath, 'Status' => '✅ Created'];
                }
            } else {
                $results[] = ['Component' => 'Excel Export Class', 'File' => $excelPath, 'Status' => '⏭_ Skipped'];
            }
        }

        if ($wantsPdf) {
            $pdfStub = $stubDir . 'export-pdf.stub';
            $pdfPath = app_path("Exports/{$modelName}PdfExport.php");

            $proceed = true;
            if (File::exists($pdfPath)) {
                $proceed = $this->confirmOverwrite($pdfPath);
            }

            if ($proceed && File::exists($pdfStub)) {
                $content = File::get($pdfStub);
                $content = str_replace(
                    ['{{ModelName}}', '{{modelNameLower}}', '{{tableName}}'],
                    [$modelName, $modelNameLower, $tableName],
                    $content
                );
                if ($this->writeFile($pdfPath, $content)) {
                    $results[] = ['Component' => 'PDF Export Service', 'File' => $pdfPath, 'Status' => '✅ Created'];
                }
            } else {
                $results[] = ['Component' => 'PDF Export Service', 'File' => $pdfPath, 'Status' => '⏭_ Skipped'];
            }

            // Generate Blade view for PDF
            $viewStub = $stubDir . 'export-pdf-view.stub';
            $viewPath = resource_path("views/exports/{$tableName}-pdf.blade.php");

            $proceedView = true;
            if (File::exists($viewPath)) {
                $proceedView = $this->confirmOverwrite($viewPath);
            }

            if ($proceedView && File::exists($viewStub)) {
                $content = File::get($viewStub);
                $content = str_replace(
                    ['{{ModelName}}', '{{modelNameLower}}', '{{tableName}}'],
                    [$modelName, $modelNameLower, $tableName],
                    $content
                );
                if ($this->writeFile($viewPath, $content)) {
                    $results[] = ['Component' => 'PDF Blade View', 'File' => $viewPath, 'Status' => '✅ Created'];
                }
            } else {
                $results[] = ['Component' => 'PDF Blade View', 'File' => $viewPath, 'Status' => '⏭_ Skipped'];
            }
        }

        // Persist operation log for exam:undo
        if (! $this->option('dry-run')) {
            $this->persistOperationLog("exam:export {$rawName}");
        }

        $this->newLine();
        $this->table(['Component', 'File', 'Status'], $results);

        $this->newLine();
        $this->components->warn('Langkah selanjutnya:');
        
        if ($wantsExcel) {
            $this->info("   1. Pastikan package terinstall: composer require maatwebsite/excel");
            $this->info("   2. Gunakan di Controller / Route:");
            $this->info("      use Maatwebsite\\Excel\\Facades\\Excel;");
            $this->info("      use App\\Exports\\{$modelName}Export;");
            $this->info("      return Excel::download(new {$modelName}Export, '{$tableName}.xlsx');");
        }

        if ($wantsPdf) {
            $this->newLine();
            $this->info("   1. Pastikan package terinstall: composer require barryvdh/laravel-dompdf");
            $this->info("   2. Gunakan di Controller / Route:");
            $this->info("      use App\\Exports\\{$modelName}PdfExport;");
            $this->info("      return (new {$modelName}PdfExport)->download();");
        }

        return self::SUCCESS;
    }
}
