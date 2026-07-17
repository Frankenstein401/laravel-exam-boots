<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://laravel.com/img/logomark.min.svg">
    <img alt="Laravel Exam Boots" src="https://laravel.com/img/logomark.min.svg" width="80" height="80">
  </picture>
</p>

<h1 align="center">Laravel Exam Boots</h1>

<p align="center">
  <strong>CLI Component & Auth Generator — Shadcn-style boilerplate ejector untuk ujian sertifikasi Laravel</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/franken/laravel-exam-boots"><img src="https://img.shields.io/packagist/v/franken/laravel-exam-boots?label=version&logo=packagist&logoColor=white&color=FF2D20" alt="Packagist"></a>
  <a href="https://packagist.org/packages/franken/laravel-exam-boots"><img src="https://img.shields.io/packagist/dt/franken/laravel-exam-boots?logo=packagist&logoColor=white&color=FF2D20" alt="Downloads"></a>
  <img src="https://img.shields.io/badge/PHP-%5E8.3-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-%5E13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
</p>

<p align="center">
  <a href="#-fitur-utama">Fitur</a> •
  <a href="#-quick-start">Quick Start</a> •
  <a href="#-referensi-command-cli">Commands</a> •
  <a href="#-arsitektur">Arsitektur</a> •
  <a href="#-contoh-kode">Contoh Kode</a> •
  <a href="https://franken.github.io/laravel-exam-boots">Dokumentasi HTML</a>
</p>

---

> Hemat hingga **90% waktu setup** dengan boilerplate arsitektur modern yang langsung di-*eject* ke `app/` dan `database/`. Bukan library blackbox — kamu dapatkan kode utuh yang bisa langsung diedit.

---

## Demo

```bash
# One-shot setup: environment check + auth + seeder + migrate
php artisan forge:install

# Buat CRUD Product dalam <10 detik
php artisan forge:add Product

# Setup autentikasi JWT/Sanctum
php artisan forge:auth
```

---

## Fitur Utama

| Fitur | Command | Deskripsi |
|----------|---------|-----------|
| **One-Shot Install** | `forge:install` | Setup lengkap: doctor → auth → seed-admin → migrate |
| **Full CRUD Eject** | `forge:add {name}` | 6 file sekaligus: Model, Migration, Controller, Service, Request, Resource |
| **Auth (JWT/Sanctum)** | `forge:auth` | Login, Register, Logout, Profile — siap pakai |
| **Relasi Eloquent** | `forge:relation` | Inject relasi + migration FK/pivot ke kedua model |
| **API Response Trait** | `forge:response` | Standarisasi JSON response helper |
| **Policy Otorisasi** | `forge:policy {Model}` | Policy class + auto-register di AuthServiceProvider |
| **Feature Tests** | `forge:test {Model}` | CRUD endpoint test (PHPUnit/Pest) |
| **Export Excel/PDF** | `forge:export {Model}` | Boilerplate export class + PDF view |
| **Admin Seeder** | `forge:seed-admin` | Akun admin default + auto-register DatabaseSeeder |
| **Backup & Rollback** | `forge:undo` | Rollback operasi generator + restore backup |
| **Environment Check** | `forge:doctor` | Cek PHP, DB, config, migration sebelum ujian |
| **Cheatsheet Offline** | `forge:cheatsheet` | Referensi command cepat di terminal |

### Flag Global

Semua command mendukung flag berikut:

| Flag | Fungsi |
|------|--------|
| `--dry-run` | Preview aksi tanpa menyentuh filesystem |
| `--force` | Skip konfirmasi overwrite |

---

## Quick Start

### 1. Install

```bash
composer require franken/laravel-exam-boots
```

Package menggunakan Laravel Auto-Discovery — tidak perlu register manual.

### 2. One-Shot Setup (Rekomendasi)

```bash
php artisan forge:install
```

Perintah ini menjalankan 4 langkah sekaligus:
- Verifikasi environment (`forge:doctor`)
- Setup autentikasi JWT/Sanctum (`forge:auth`)
- Buat akun admin (`forge:seed-admin`)
- Jalankan migration

### 3. Generate CRUD

```bash
php artisan forge:add Product
```

CLI akan bertanya interaktif:
```
Apakah fitur ini membutuhkan Auth Middleware? (yes/no)
> yes

Pilih tipe database operation:
> Eloquent CRUD
```

Hasil: **6 file siap pakai** — tinggal `php artisan migrate` dan daftarkan route.

### 4. Setup Manual (Step-by-Step)

```bash
# 1. Cek environment
php artisan forge:doctor

# 2. Setup auth
php artisan forge:auth

# 3. Buat CRUD
php artisan forge:add Product

# 4. Seeder admin
php artisan forge:seed-admin

# 5. Migrate database
php artisan migrate
```

---

## Referensi Command CLI

### Command 1: `php artisan forge:install`

**One-shot installer** — menjalankan 4 langkah setup dalam satu perintah.

```bash
# Setup lengkap
php artisan forge:install

# Preview tanpa eksekusi
php artisan forge:install --dry-run

# Lewati semua konfirmasi
php artisan forge:install --force
```

Output:
```
Laravel Exam Boots — One-Shot Installer
 1. Verifikasi environment (forge:doctor)
 2. Setup autentikasi (forge:auth)
 3. Buat seeder admin (forge:seed-admin)
 4. Jalankan migrasi database

┌──────────────┬──────────────────────────────────────┐
│ Step         │ Status                               │
├──────────────┼──────────────────────────────────────┤
│ Environment  │ Passed (11/11 checks OK)           │
│ Auth         │ JWT + Scramble installed           │
│ Admin Seeder │ AdminUserSeeder created            │
│ Migrate      │ All migrations applied             │
└──────────────┴──────────────────────────────────────┘
```

---

### Command 2: `php artisan forge:add {name}`

Generate **6 file CRUD** sekaligus. Nama komponen otomatis dikonversi ke PascalCase untuk kelas, camelCase untuk variabel, dan snake_case untuk tabel.

#### Flags

| Flag | Contoh | Fungsi |
|------|--------|--------|
| `--belongsTo` | `--belongsTo=Category` | Relasi belongsTo + FK di migration |
| `--hasMany` | `--hasMany=Review` | Relasi hasMany + belongsTo di model anak |
| `--hasOne` | `--hasOne=Profile` | Relasi hasOne 1-to-1 |
| `--belongsToMany` | `--belongsToMany=Role` | Many-to-many + pivot table |
| `--with-factory` | `--with-factory` | Factory + Seeder |
| `--upload` | `--upload=image` | Handler upload file + validasi |
| `--web` | `--web` | Blade views + web routing |
| `--enum` | `--enum=status:active,inactive` | Enum class + casts + migration |
| `--soft-deletes` | `--soft-deletes` | SoftDeletes trait + kolom deleted_at |

#### Contoh Penggunaan

```bash
# Basic CRUD
php artisan forge:add Product

# CRUD dengan relasi kategori, upload gambar, soft deletes
php artisan forge:add Product \
  --belongsTo=Category \
  --upload=image \
  --soft-deletes

# CRUD dengan factory, enum status, dan blade views
php artisan forge:add Order \
  --with-factory \
  --enum=status:pending,processing,completed \
  --web

# CRUD many-to-many dengan roles
php artisan forge:add User \
  --belongsToMany=Role \
  --with-factory
```

---

### Command 3: `php artisan forge:auth`

Setup autentikasi API lengkap dengan pilihan **JWT** atau **Laravel Sanctum**.

```bash
# Setup interaktif
php artisan forge:auth

# Paksa metode tertentu
php artisan forge:auth --method=jwt
php artisan forge:auth --method=sanctum
```

Yang akan dihasilkan:
- API scaffolding (jika belum terinstall)
- AuthController dengan register, login, logout, me
- Konfigurasi guard (JWT/Sanctum)
- User model dengan method/trait auth
- Route auth di `routes/api.php`
- Scramble API docs (opsional)
- **Auto Guard Config** — generator `forge:add` otomatis membaca metode auth dari config

---

### Command 4: `php artisan forge:relation`

Membangun relasi Eloquent antar model secara interaktif.

```bash
php artisan forge:relation
```

- Inject method relasi ke kedua model
- Generate migration FK atau pivot table
- Dukungan: `hasOne`, `hasMany`, `belongsToMany`

---

### Command 5: `php artisan forge:policy {Model}`

```bash
php artisan forge:policy Post
```

- Membuat `app/Policies/PostPolicy.php`
- Auto-register di `AuthServiceProvider.php`

---

### Command 6: `php artisan forge:test {Model}`

```bash
# PHPUnit
php artisan forge:test Product

# Pest PHP
php artisan forge:test Product --pest
```

Test yang dihasilkan mencakup: Read, Create, Detail, Update, Delete, Auth Protection.

---

### Command 7: `php artisan forge:export {Model}`

```bash
php artisan forge:export Product
```

- `App/Exports/ProductExport.php` — Excel (maatwebsite/excel)
- `App/Exports/ProductPdfExport.php` — PDF (barryvdh/laravel-dompdf)
- `resources/views/exports/product-pdf.blade.php` — Template PDF

---

### Command 8: `php artisan forge:response`

```bash
php artisan forge:response
```

Menghasilkan `app/Traits/ApiResponse.php` dengan helper:
- `success($data, $message, $code)` — Response sukses terstandarisasi
- `error($message, $code)` — Response error konsisten

---

### Command 9: `php artisan forge:doctor`

Cek environment sebelum ujian:
- PHP version (>= 8.3)
- Laravel structure
- Database connection
- APP_KEY & APP_DEBUG
- JWT secret (jika JWT terinstall)
- Storage symlink
- Pending migrations
- Route files
- **Backup retention** — peringatan jika backup terlalu tua

```bash
php artisan forge:doctor
```

---

### Command 10: `php artisan forge:seed-admin`

```bash
# Default (admin@example.com / password)
php artisan forge:seed-admin

# Custom
php artisan forge:seed-admin --email=admin@test.com --password=rahasia123
```

---

### Command 11: `php artisan forge:cheatsheet`

Tampilkan daftar command offline:

```bash
php artisan forge:cheatsheet
```

---

### Command 12: `php artisan forge:undo {id?} --prune`

Rollback operasi generator terakhir.

```bash
# Undo operasi terakhir
php artisan forge:undo

# Undo operasi spesifik (lihat ID dari history)
php artisan forge:undo abc123

# Undo + bersihkan backup expired
php artisan forge:undo --prune
```

Fitur:
- Restore file yang dimodifikasi ke versi asli
- Hapus file baru yang digenerate
- Backup otomatis sebelum modifikasi
- Riwayat operasi tersimpan di `storage/exam-boots/history.json`
- **Retensi backup**: 3 hari (konfigurabel via `config/exam-boots.php`)

---

## Contoh Kode yang Dihasilkan

### Service Class

```php
readonly class ProductService
{
    public function getAllProduct(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query();

        // Filter by exact field: ?filter[status]=active
        if (! empty($filters['filter'])) {
            foreach ($filters['filter'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        // Search: ?search=keyword
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%");
            });
        }

        // Sort: ?sort_by=created_at&sort_order=desc
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate: ?per_page=15
        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function getDetailProduct(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function deleteProduct($id): bool
    {
        return Product::findOrFail($id)->delete();
    }
}
```

### Controller dengan Dependency Injection

```php
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getAllProduct($request->all());

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
```

### Form Request dengan Validation Rules

```php
class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
```

---

## Arsitektur

```
laravel-exam-boots/
├── src/
│   ├── Concerns/
│   │   └── TracksFileOperations.php    # Trait: tracking, backup, dry-run, rollback
│   ├── Console/
│   │   ├── ExamAddCommand.php           # Generator CRUD utama
│   │   ├── ExamAuthCommand.php          # Setup autentikasi
│   │   ├── ExamCheatsheetCommand.php    # CLI cheatsheet offline
│   │   ├── ExamDoctorCommand.php        # Environment check + backup audit
│   │   ├── ExamExportCommand.php        # Excel & PDF export
│   │   ├── ExamInstallCommand.php       # One-shot installer
│   │   ├── ExamPolicyCommand.php        # Policy generator
│   │   ├── ExamRelationCommand.php      # Relasi Eloquent generator
│   │   ├── ExamResponseCommand.php      # API Response trait
│   │   ├── ExamSeedAdminCommand.php     # Admin seeder generator
│   │   ├── ExamTestCommand.php          # Test generator (PHPUnit/Pest)
│   │   └── ExamUndoCommand.php          # Rollback + backup prune
│   ├── config/
│   │   └── exam-boots.php               # Konfigurasi package
│   ├── stubs/
│   │   ├── controller.stub              # Eloquent CRUD Controller
│   │   ├── controller.blank.stub        # Blank Controller
│   │   ├── service.stub                 # Eloquent CRUD Service
│   │   ├── service.blank.stub           # Blank Service
│   │   ├── request.stub                 # Form Request
│   │   ├── resource.stub                # API Resource
│   │   ├── model.stub                   # Eloquent Model
│   │   ├── migration.stub               # Migration database
│   │   ├── web-controller.stub          # Web MVC Controller
│   │   ├── view-*.stub                  # Blade views (index, create, edit, show)
│   │   ├── factory.stub / seeder.stub   # Factory & Seeder
│   │   ├── policy.stub                  # Policy class
│   │   ├── test-*.stub                  # PHPUnit & Pest test
│   │   ├── export-*.stub                # Excel & PDF export
│   │   ├── auth-*.stub                  # Auth (JWT & Sanctum variants)
│   │   ├── api-response-trait.stub      # API Response helper
│   │   └── scramble-provider.*.stub     # Scramble providers
│   └── ForgeStarterServiceProvider.php   # Service Provider & command registration
├── docs/
│   └── index.html                       # Dokumentasi HTML interaktif
├── composer.json
└── README.md
```

### Pola Arsitektur

```
[Request] → [Controller] → [Service] → [Model]
                ↓                          ↓
          [Form Request]            [API Resource]
          (Validasi)               (Transformasi)
```

Setiap komponen dipisahkan dengan tanggung jawab yang jelas:
- **Controller**: Handling HTTP request/response, middleware
- **Service**: Business logic, query database
- **Form Request**: Validasi input
- **API Resource**: Transformasi data response

---

## Kustomisasi

Publikasikan konfigurasi untuk mengubah default:

```bash
php artisan vendor:publish --tag=exam-boots-config
```

File konfigurasi (`config/exam-boots.php`):

```php
'defaults' => [
    'auth_method' => env('EXAM_BOOTS_AUTH', 'jwt'),   // 'jwt' atau 'sanctum'
    'test_framework' => 'pest',                        // 'pest' atau 'phpunit'
    'install_docs' => true,                            // Install Scramble docs
    'crud_type' => 'eloquent',                         // 'eloquent' atau 'blank'
],

'backup' => [
    'enabled' => true,
    'retention_days' => 3,      // Backup otomatis dibersihkan setelah 3 hari
],
```

---

## Langkah Selanjutnya Setelah Generate

### 1. Daftarkan Route

Buka `routes/api.php`:

```php
use App\Http\Controllers\Api\ProductController;

Route::apiResource('products', ProductController::class);
```

### 2. Jalankan Migration

```bash
php artisan migrate
```

### 3. Uji Endpoint

**Auth:**
```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"User","email":"user@test.com","password":"secret","password_confirmation":"secret"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**CRUD (dengan token dari login):**
```bash
export TOKEN="your_token_here"

curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/products

curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Produk Baru","category_id":1}'
```

### 4. Buka API Docs (jika Scramble terinstall)

```
http://localhost:8000/docs/api
```

---

## FAQ

### Class JWTSubject tidak ditemukan?

Jalankan ulang `composer install` atau `composer update` untuk refresh autoload.

### Reset JWT secret?

```bash
php artisan jwt:secret --force
```

### Ingin preview tanpa menulis file?

Semua command support `--dry-run`:

```bash
php artisan forge:add Product --dry-run
php artisan forge:install --dry-run
php artisan forge:auth --dry-run
```

### Backup menumpuk?

Gunakan `--prune` untuk membersihkan backup yang sudah kedaluwarsa (retensi: 3 hari):

```bash
php artisan forge:undo --prune
```

Cek status backup via:

```bash
php artisan forge:doctor
```

---

## Kontribusi

Kontribusi sangat diterima! Silakan buka issue atau pull request di [GitHub](https://github.com/franken/laravel-exam-boots).

---

## Lisensi

MIT License — lihat file [LICENSE](LICENSE) untuk detail.

---

## Author

**Franken**

---

<p align="center">
  <strong>Boots Up, Code Fast, Pass The Exam!</strong>
  <br>
  <sub>Dibuat dengan dedikasi untuk mempermudah ujian sertifikasi programming</sub>
</p>
