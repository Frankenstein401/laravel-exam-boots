Product Requirement Document (PRD)
Project Name: laravel-exam-boots (Shadcn-style Backend CLI for Certification)
Version: 1.0.0 (Targeting PHP 8.5+ & Laravel latest)

Author: franken

1. Objective & Overview
Tujuan dari project ini adalah membuat sebuah CLI Component Generator berbasis Laravel Package (di-install via Composer) yang mengadopsi filosofi shadcn/ui. Tool ini tidak bertindak sebagai library blackbox, melainkan menyuntikkan (eject) kode boilerplate arsitektur modern (Controller, Service, Request, Resource) langsung ke dalam direktori aplikasi utama (app/) agar peserta ujian dapat menghemat waktu hingga 80% dalam setup struktur CRUD dan Authentication.

2. Technical Stack & Environment
Language: PHP ^8.5 (Backwards compatible up to PHP ^8.2)

Framework: Laravel latest

Dependency Manager: Composer ^2.9.5

Architecture Pattern: Controller-Service-Resource (Component-Based Separation)

3. Core Features & User Flow (CLI Interactivity)
CLI harus bersifat interaktif menggunakan bawaan Artisan Command Prompts.

Flow Perintah: php artisan forge:add {ComponentName}
Cuplikan kode
graph TD
    A[Jalankan php artisan forge:add Product] --> B{Fitur butuh Auth Middleware?}
    B -- Yes --> C[Set Auth Middleware di Controller Stub]
    B -- No --> D[Biarkan Public]
    C --> E{Pilih Tipe Database Operation}
    D --> E
    E -- Eloquent CRUD --> F[Generate File dengan Boilerplate Eloquent]
    E -- Blank Service --> G[Generate File dengan Struktur Kosong]
    F --> H[Suntikkan file ke folder app/]
    G --> H
    H --> I[Output: Sukses & Tampilkan daftar file yang dibuat]
4. File Structure Requirements (The Output Components)
Setiap kali perintah dijalankan, CLI wajib meng-generate 4 file utama dengan struktur modern (Constructor Property Promotion & Readonly Class):

A. Request Component (app/Http/Requests/{Name}Request.php)
Requirement: Mengembalikan true pada method authorize(). Menggunakan aturan validasi standar yang mudah diedit di bagian rules().

B. Resource Component (app/Http/Resources/{Name}Resource.php)
Requirement: Transformasi data standar API dengan wrapper array status dan data.

C. Service Component (app/Services/{Name}Service.php)
Requirement: Menggunakan readonly class (PHP 8.2+). Berisi template method: getAll(), getById(), create(), update(), delete().

D. Controller Component (app/Http/Controllers/{Name}Controller.php)
Requirement: Menggunakan Constructor Property Promotion untuk meng-inject Service terkait. Memiliki response JSON yang seragam.

5. System Architecture & Package Directory (The Generator)
Package ini dikembangkan dengan struktur folder terisolasi sebelum di-publish ke Packagist:

Plaintext
laravel-exam-fast/
├── src/
│   ├── Console/
│   │   └── ExamAddCommand.php           # File Logic Handler CLI
│   ├── stubs/
│   │   ├── controller.stub              # Template Controller
│   │   ├── service.stub                 # Template Service
│   │   ├── request.stub                 # Template Request
│   │   └── resource.stub                # Template Resource
│   └── ForgeStarterServiceProvider.php   # Registrasi Package & Command
└── composer.json                        # Konfigurasi Auto-discovery
6. Stub Template Specifications (For AI Code Generation)
Dokumen ini memberikan spesifikasi kode stubs yang harus dibuat oleh AI:

controller.stub
PHP
<?php

namespace App\Http\Controllers;

use App\Http\Requests\{{ModelName}}Request;
use App\Http\Resources\{{ModelName}}Resource;
use App\Services\{{ModelName}}Service;
use Illuminate\Http\JsonResponse;

class {{ModelName}}Controller extends Controller
{
    public function __construct(
        protected {{ModelName}}Service ${{modelNameLower}}Service
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->{{modelNameLower}}Service->getAll();
        return response()->json(['status' => 'success', 'data' => {{ModelName}}Resource::collection($data)]);
    }

    public function store({{ModelName}}Request $request): JsonResponse
    {
        $result = $this->{{modelNameLower}}Service->create($request->validated());
        return response()->json(['status' => 'success', 'data' => new {{ModelName}}Resource($result)], 201);
    }
    
    // Tambahkan stub untuk show, update, destroy...
}
service.stub
PHP
<?php

namespace App\Services;

use App\Models\{{ModelName}};

readonly class {{ModelName}}Service
{
    public function getAll()
    {
        return {{ModelName}}::all();
    }

    public function create(array $data)
    {
        return {{ModelName}}::create($data);
    }
    
    // Tambahkan stub untuk getById, update, delete...
}
7. Non-Functional Requirements (Guardrails)
Zero External Dependency: Package tidak boleh bergantung pada package pihak ketiga selain core framework Laravel (illuminate/support).

Idempotency (Safe Overwrite): Jika file komponen sudah ada di folder tujuan, CLI harus memberikan konfirmasi: "File sudah ada, apakah ingin menimpa (overwrite)? (y/n)" untuk mencegah terhapusnya kode yang sudah ditulis user secara tidak sengaja.

Environment Independent: Kode generator wajib menggunakan fungsi File::ensureDirectoryExists() untuk memastikan kompabilitas lintas OS (Windows/Linux saat ujian).

TAMBAHAN

Yang perlu dicek konsistensinya dulu

forge:add vs exam:relation overlap: di Fitur Utama poin 1, --belongsTo=Model katanya udah bisa inject relasi via forge:add. Tapi terus ada juga exam:relation (Command 4) yang isinya relationship generator. Ini dua command beda tapi kerjaannya mirip — apa exam:relation khusus buat nambah relasi ke model yang sudah ada (tanpa generate ulang CRUD), sedangkan --belongsTo di forge:add buat pas bikin model baru? Kalau iya, worth ditulis jelas di README biar gak bingung sendiri pas ujian mode panik. Kalau enggak ada bedanya, mending salah satu di-deprecate biar gak ada dua source of truth.

Fitur yang masih kosong slot-nya
1. Validation Rules Preset
Form Request stub sekarang generic. Exam sering butuh validasi spesifik cepat (unique email, exists:table,column buat FK, image mimes). Kalau --belongsTo=Order udah otomatis nambahin 'order_id' => 'required|exists:orders,id' di Request stub, itu ngehemat waktu isi manual.
2. Enum/Status Field Generator
Banyak soal sertifikasi minta status field (pending/approved/rejected, draft/published). Flag kayak --enum=status:pending,approved,rejected yang generate PHP 8.1+ backed enum class + cast di Model + migration enum() column bisa jadi pembeda besar dari kompetitor generator lain.
3. Rollback Safety / exam:undo
Karena tool ini nulis banyak file + inject ke file existing (User.php, AuthServiceProvider, routes), kalau salah pilih opsi pas ujian (grogi, keburu-buru), ada risiko harus manual cleanup. Command exam:undo yang revert generate terakhir (atau minimal auto-backup .bak sebelum inject ke file existing) itu jaring pengaman yang penting — terutama karena kamu sendiri udah pernah encounter kasus security/injection issue di DocsAuthController sebelumnya, jadi safety net di tool sendiri worth diprioritaskan.
4. Dry-run Mode
--dry-run flag yang nampilin preview file apa aja yang bakal dibuat/diubah tanpa benar-benar nulis. Berguna pas belum yakin sama pilihan interactive prompt.
Kecil tapi kepake

Config file (config/exam-boots.php) buat set default (misal default auth method JWT/Sanctum, default test framework Pest/PHPUnit) biar gak perlu jawab prompt yang sama tiap kali di project baru.
--force flag global buat skip semua konfirmasi overwrite sekaligus, kepake kalau situasi ujian sisa waktu dikit dan yakin mau timpa semua.


Kalau saya rank ulang: #3 (rollback/backup safety) ini saya naikkan urgensi-nya karena tool kamu udah lumayan agresif nge-inject ke file existing (User.php, AuthServiceProvider, providers.php) — sekali salah, cleanup manual di tengah ujian itu mahal banget waktu-nya. Mau saya bantu desain teknis buat itu, atau lebih ke enum generator dulu?desainnya semuanyaMerancang fitur-fitur teknis dengan detail implementasi konkretMerancang fitur-fitur teknis dengan detail implementasi konkretOke, gas semua sekaligus. Saya urutin dari yang paling fundamental (karena beberapa saling bergantung — backup system harus ada duluan sebelum rollback bisa jalan) ke yang paling cosmetic.

1. Backup & Rollback System (exam:undo) — fondasi dulu
Ini harus jadi infrastructure layer yang dipakai semua command lain, bukan command berdiri sendiri.
a. Central Action Logger
Bikin trait yang di-use di semua Command class:
php// src/Concerns/TracksFileOperations.php
trait TracksFileOperations
{
    protected array $operationLog = [];

    protected function trackCreate(string $path): void
    {
        $this->operationLog[] = ['action' => 'create', 'path' => $path];
    }

    protected function trackModify(string $path): void
    {
        $backupPath = $this->backupFile($path);
        $this->operationLog[] = ['action' => 'modify', 'path' => $path, 'backup' => $backupPath];
    }

    protected function backupFile(string $path): ?string
    {
        if (!File::exists($path)) return null;

        $backupDir = storage_path('exam-boots/backups/' . now()->format('Ymd_His'));
        File::ensureDirectoryExists($backupDir);

        $backupPath = $backupDir . '/' . str_replace('/', '_', $path) . '.bak';
        File::copy($path, $backupPath);

        return $backupPath;
    }

    protected function persistOperationLog(string $command): void
    {
        $logFile = storage_path('exam-boots/history.json');
        $history = File::exists($logFile) ? json_decode(File::get($logFile), true) : [];

        $history[] = [
            'id' => (string) Str::uuid(),
            'command' => $command,
            'timestamp' => now()->toIso8601String(),
            'operations' => $this->operationLog,
        ];

        File::put($logFile, json_encode($history, JSON_PRETTY_PRINT));
    }
}
Setiap kali command bikin file baru → trackCreate(). Setiap kali command edit file existing (kayak inject relation ke Order.php, inject provider ke bootstrap/providers.php) → trackModify() sebelum nulis, jadi backup ke-capture kondisi originalnya.
b. exam:undo Command
phpprotected $signature = 'exam:undo {id? : ID operasi spesifik, default: yang terakhir}';

public function handle()
{
    $logFile = storage_path('exam-boots/history.json');
    if (!File::exists($logFile)) {
        $this->warn('Tidak ada riwayat operasi.');
        return;
    }

    $history = json_decode(File::get($logFile), true);
    $entry = $this->argument('id')
        ? collect($history)->firstWhere('id', $this->argument('id'))
        : end($history);

    if (!$entry) {
        $this->error('Operasi tidak ditemukan.');
        return;
    }

    $this->table(['Action', 'Path'], collect($entry['operations'])
        ->map(fn($op) => [$op['action'], $op['path']]));

    if (!$this->confirm("Undo {$entry['command']} ({$entry['timestamp']})?")) {
        return;
    }

    foreach (array_reverse($entry['operations']) as $op) {
        match ($op['action']) {
            'create' => File::exists($op['path']) && File::delete($op['path']),
            'modify' => $op['backup'] && File::exists($op['backup'])
                ? File::copy($op['backup'], $op['path'])
                : $this->warn("Backup tidak ditemukan untuk {$op['path']}, skip."),
            default => null,
        };
    }

    $this->info('Undo berhasil.');
}
Penting: history disimpan per-run (bukan per-file), jadi exam:undo tanpa argumen selalu revert operasi terakhir secara utuh — kalau forge:add Product --with-factory bikin 8 file, undo sekali hapus semua 8 + restore file yang di-modify.

2. Dry-run Mode (--dry-run)
Paling gampang diimplementasi kalau file-writing kamu udah lewat satu method sentral. Tambah flag global, lalu di titik penulisan file:
phpprotected function writeFile(string $path, string $content): void
{
    if ($this->option('dry-run')) {
        $this->line("<fg=yellow>[DRY-RUN]</> Akan membuat: {$path}");
        return;
    }

    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);
    $this->trackCreate($path);
    $this->info("Created: {$path}");
}

protected function modifyFile(string $path, string $newContent, string $description): void
{
    if ($this->option('dry-run')) {
        $this->line("<fg=yellow>[DRY-RUN]</> Akan mengubah: {$path} ({$description})");
        return;
    }

    $this->trackModify($path);
    File::put($path, $newContent);
    $this->info("Modified: {$path}");
}
Semua command (forge:add, forge:auth, exam:relation, dst) tinggal panggil writeFile()/modifyFile() daripada File::put() langsung. Tambahin di tiap signature: {--dry-run : Preview tanpa menulis file}.
Bonus: di akhir dry-run, kasih summary table biar keliatan jelas apa aja yang bakal kena.

3. --force Flag Global
Simple — bikin helper konfirmasi terpusat:
phpprotected function confirmOverwrite(string $path): bool
{
    if ($this->option('force')) return true;
    if (!File::exists($path)) return true;

    return $this->confirm("File {$path} sudah ada. Timpa?", false);
}
Dipanggil sebelum tiap writeFile(). Kalau false, command skip file itu dan lanjut ke file berikutnya (jangan abort total — biar partial generate tetep jalan).

4. Config File (config/exam-boots.php)
Publish via php artisan vendor:publish --tag=exam-boots-config.
phpreturn [
    'defaults' => [
        'auth_method' => env('EXAM_BOOTS_AUTH', 'jwt'), // jwt | sanctum
        'test_framework' => 'pest', // pest | phpunit
        'install_docs' => true, // Scramble
        'crud_type' => 'eloquent', // eloquent | blank
    ],

    'naming' => [
        'controller_suffix' => 'Controller',
        'service_suffix' => 'Service',
    ],

    'paths' => [
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        // custom kalau struktur project beda dari default Laravel
    ],

    'backup' => [
        'enabled' => true,
        'retention_days' => 3, // auto-cleanup backup lama
    ],
];
Di tiap command, sebelum masuk ke interactive prompt, cek config dulu:
php$authMethod = $this->option('method')
    ?? config('exam-boots.defaults.auth_method')
    ?? $this->choice('Pilih metode autentikasi', ['jwt', 'sanctum']);
Prioritas: flag CLI eksplisit > config file > interactive prompt. Ini yang paling ngehemat waktu kalau kamu udah tau preferensi kamu dari awal ujian — tinggal isi config sekali di awal, sisanya forge:auth jalan tanpa nanya-nanya lagi.

5. Validation Rules Preset (auto di Request stub)
Ini nyambung ke --belongsTo dan --upload yang udah ada. Bikin builder yang jalan pas forge:add:
phpprotected function buildValidationRules(array $options): string
{
    $rules = [];

    // Dari --belongsTo, auto exists:table,id
    foreach ($options['belongsTo'] ?? [] as $parent) {
        $fk = Str::snake($parent) . '_id';
        $table = Str::snake(Str::plural($parent));
        $rules[$fk] = "'required|integer|exists:{$table},id'";
    }

    // Dari --upload, auto image validation
    foreach ($options['upload'] ?? [] as $field) {
        $rules[$field] = "'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'";
    }

    // Dari --enum (lihat poin 6), auto Rule::enum
    foreach ($options['enum'] ?? [] as $field => $enumClass) {
        $rules[$field] = "['required', new \Illuminate\Validation\Rules\Enum({$enumClass}::class)]";
    }

    return collect($rules)
        ->map(fn($rule, $field) => "'{$field}' => {$rule},")
        ->implode("\n            ");
}
Hasil inject ke request.stub di placeholder {{ rules }}. Field lain (non-relasi/upload/enum) tetep kosong dengan komentar // TODO: tambahkan validasi field lain.

6. Enum/Status Field Generator (--enum)
Signature tambahan di forge:add:
php{--enum=* : Format field:value1,value2,value3 — e.g. --enum=status:pending,approved,rejected}
a. Generate Enum Class
phpprotected function generateEnumClass(string $field, array $values): void
{
    $enumName = Str::studly($field); // status -> Status
    $cases = collect($values)
        ->map(fn($v) => "    case " . Str::studly($v) . " = '{$v}';")
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
{$this->buildLabelCases($enumName, $values)}
        };
    }
}
PHP;

    $this->writeFile(app_path("Enums/{$enumName}.php"), $stub);
}
b. Inject Cast ke Model
phpprotected function buildEnumCasts(array $enums): string
{
    return collect($enums)
        ->map(fn($values, $field) => "'{$field}' => \App\Enums\\" . Str::studly($field) . "::class,")
        ->implode("\n            ");
}
Masuk ke {{ casts }} placeholder di model.stub (dalam method casts() return array, sesuai konvensi Laravel 11+).
c. Migration Column
phpprotected function buildEnumColumns(array $enums): string
{
    return collect($enums)
        ->map(function ($values, $field) {
            $valueList = collect($values)->map(fn($v) => "'{$v}'")->implode(', ');
            $default = $values[0];
            return "\$table->enum('{$field}', [{$valueList}])->default('{$default}');";
        })
        ->implode("\n            ");
}
Parsing input --enum=status:pending,approved,rejected dilakukan sekali di awal handle():
php$enums = collect($this->option('enum'))
    ->mapWithKeys(function ($item) {
        [$field, $values] = explode(':', $item);
        return [$field => explode(',', $values)];
    })
    ->toArray();

Ringkasan Prioritas Implementasi
UrutanFiturKenapa urutan ini1TracksFileOperations trait + backupFondasi — command lain depend ke sini2writeFile()/modifyFile() central methodDipakai --dry-run, --force, backup sekaligus3exam:undoBaru bisa jalan setelah #1-2 ada4--dry-run, --forceTinggal nempel di method sentral5Config fileIndependent, bisa paralel kapan aja6Validation preset + Enum generatorFeature-level, gak depend ke apapun di atas
Titik kritis: refactor forge:add yang udah jalan sekarang ke writeFile()/modifyFile() dulu sebelum nambah fitur baru di atasnya — kalau langsung nambah fitur di atas kode lama yang masih File::put() manual, nanti kamu harus refactor ulang pas udah banyak command lain ikut bergantung ke pattern lama. Worth diinvest waktu sekarang.
Mau saya bantu tulis full ExamAddCommand.php versi refactored yang udah pakai semua pattern ini?