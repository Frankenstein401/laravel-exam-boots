Product Requirement Document (PRD)
Project Name: laravel-exam-boots (Shadcn-style Backend CLI for Certification)
Version: 1.0.0 (Targeting PHP 8.5+ & Laravel latest)

Author: Abdul Gani Hadiansyah

1. Objective & Overview
Tujuan dari project ini adalah membuat sebuah CLI Component Generator berbasis Laravel Package (di-install via Composer) yang mengadopsi filosofi shadcn/ui. Tool ini tidak bertindak sebagai library blackbox, melainkan menyuntikkan (eject) kode boilerplate arsitektur modern (Controller, Service, Request, Resource) langsung ke dalam direktori aplikasi utama (app/) agar peserta ujian dapat menghemat waktu hingga 80% dalam setup struktur CRUD dan Authentication.

2. Technical Stack & Environment
Language: PHP ^8.5 (Backwards compatible up to PHP ^8.2)

Framework: Laravel latest

Dependency Manager: Composer ^2.9.5

Architecture Pattern: Controller-Service-Resource (Component-Based Separation)

3. Core Features & User Flow (CLI Interactivity)
CLI harus bersifat interaktif menggunakan bawaan Artisan Command Prompts.

Flow Perintah: php artisan exam:add {ComponentName}
Cuplikan kode
graph TD
    A[Jalankan php artisan exam:add Product] --> B{Fitur butuh Auth Middleware?}
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
│   └── ExamStarterServiceProvider.php   # Registrasi Package & Command
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