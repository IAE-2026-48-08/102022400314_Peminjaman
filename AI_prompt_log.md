# 🤖 AI Prompt Engineering Log — Tugas 2 IAE

**Mahasiswa**: 102022400314  
**Service**: Peminjaman Service — E-Library Ecosystem  
**Mata Kuliah**: BBK2HAB3 — Integrasi Aplikasi Enterprise  
**Dosen**: Ekky Novriza Alam  
**AI Tool**: Antigravity (Google DeepMind)  
**Periode Pengerjaan**: Juni 2026

---

## 📌 Tentang Dokumen Ini

Dokumen ini mencatat seluruh sesi prompting yang dilakukan selama pengerjaan Tugas 2 IAE. Setiap sesi berisi konteks pertanyaan, respons AI, validasi/persetujuan, serta keputusan teknis yang diambil. Log ini disusun sebagai bukti proses eksplorasi teknis mandiri menggunakan AI sebagai *pair programming assistant*.

---

## Session 1 — Analisis Kontrak & Validasi Scope Service

**Tujuan**: Memastikan pemahaman terhadap Standard Integration Contract dan scope endpoint Service Peminjaman sebelum mulai implementasi.

---

**🧑 Prompt Mahasiswa**:
> Saya sudah membaca Standard Integration Contract IAE-T2. Saya mendapat bagian Service 2 (Peminjaman). Berdasarkan kontrak, endpoint yang wajib saya implementasikan adalah:
> - `GET /api/v1/loans` — mengambil semua data peminjaman
> - `GET /api/v1/loans/{id}` — mengambil detail peminjaman
> - `POST /api/v1/loans` — membuat data peminjaman baru
>
> Satu hal yang ingin saya konfirmasi: apakah ketiga endpoint ini sudah cukup memenuhi syarat "minimal 3 endpoint fungsional" sesuai rubrik, atau apakah saya perlu menambahkan endpoint seperti PUT/DELETE?

**🤖 Respons AI**:
> Benar. Berdasarkan Standard Integration Contract yang mendefinisikan pola **Collection + Resource + Action**, tiga endpoint tersebut sudah memenuhi persyaratan minimum. Contract secara eksplisit hanya mewajibkan:
> - `GET /api/v1/[resource]` — Collection
> - `GET /api/v1/[resource]/{id}` — Resource
> - `POST /api/v1/[resource]` — Action
>
> Endpoint PUT/DELETE tidak disebutkan dalam rubrik Tugas 2, sehingga implementasi tersebut sudah tepat sasaran. Fokus lebih baik dialokasikan ke kualitas dokumentasi Swagger dan implementasi GraphQL.

**✅ Keputusan**: Lanjut implementasi hanya 3 endpoint sesuai kontrak. Tidak menambah PUT/DELETE untuk menghindari scope creep yang tidak dinilai.

---

## Session 2 — Perancangan Format Respons Sesuai Integration Contract

**Tujuan**: Memastikan struktur JSON respons konsisten dengan standar yang ditetapkan sebelum mulai coding controller.

---

**🧑 Prompt Mahasiswa**:
> Sebelum saya implementasikan controller-nya, saya ingin memvalidasi rancangan format respons saya dulu. Rencananya saya akan menggunakan wrapper seperti ini:
>
> ```json
> {
>   "status": "success",
>   "message": "Data retrieved successfully",
>   "data": [...],
>   "meta": {
>     "service_name": "Peminjaman-Service",
>     "api_version": "v1",
>     "total": 6
>   }
> }
> ```
>
> Apakah field `meta` wajib diisi? Dan apakah `total` perlu ada di semua endpoint atau cukup di GET collection saja?

**🤖 Respons AI**:
> Format yang Anda rancang sudah benar dan sesuai kontrak. Untuk field `meta`, kontrak menyebutkan ini sebagai *opsional*, namun sangat direkomendasikan karena membantu integrasi service lain.
>
> Rekomendasi saya:
> - **GET /api/v1/loans** (collection): sertakan `total` di `meta` agar consumer tahu jumlah record
> - **GET /api/v1/loans/{id}** dan **POST /api/v1/loans**: cukup `service_name` dan `api_version` tanpa `total`
>
> Untuk respons error, pastikan strukturnya berbeda:
> ```json
> {
>   "status": "error",
>   "message": "Loan not found",
>   "errors": null
> }
> ```

**✅ Keputusan**: Format respons dikonfirmasi. Field `total` hanya di endpoint collection. Field `errors` dipakai untuk error validasi (array) dan null untuk error umum.

---

## Session 3 — Implementasi API Key Middleware & Diskusi Keamanan

**Tujuan**: Memahami mekanisme API Key yang tepat sebelum implementasi middleware.

---

**🧑 Prompt Mahasiswa**:
> Untuk implementasi `X-IAE-KEY`, saya mau pakai NIM sebagai nilai API Key sementara seperti yang disebutkan di kontrak. Tapi saya bertanya-tanya — apakah NIM ini aman disimpan langsung di `.env` atau ada cara yang lebih proper?
>
> Selain itu, apakah middleware ini perlu diterapkan juga ke endpoint GraphQL, atau cukup di REST API saja?

**🤖 Respons AI**:
> Pertanyaan bagus. Untuk konteks Tugas 2, menyimpan API Key di `.env` adalah pendekatan yang sudah benar dan merupakan *best practice* dibanding hardcode di kode. Ini memisahkan konfigurasi dari logika aplikasi.
>
> Mengenai GraphQL: secara teknis GraphQL endpoint bisa diproteksi, namun untuk Tugas 2 ini kontrak tidak mewajibkan GraphQL diproteksi dengan API Key yang sama. Graphql Playground biasanya diakses sebagai tool eksplorasi oleh developer. Saran saya:
> - **REST API** → wajib diproteksi `X-IAE-KEY`
> - **GraphQL** → biarkan terbuka untuk kemudahan demo dan pengujian playground

**🧑 Follow-up Mahasiswa**:
> Berarti saya perlu daftarkan middleware-nya hanya ke route group `/api/v1`, bukan ke semua route? Bagaimana cara yang paling clean di Laravel 12?

**🤖 Respons AI**:
> Tepat. Di Laravel 12, cara paling bersih adalah mendaftarkan middleware sebagai alias di `bootstrap/app.php`, lalu apply ke route group di `routes/api.php`:
>
> ```php
> // bootstrap/app.php
> $middleware->alias(['api.key' => ApiKeyMiddleware::class]);
>
> // routes/api.php
> Route::middleware('api.key')->prefix('v1')->group(function () {
>     Route::get('/loans', [LoanController::class, 'index']);
>     ...
> });
> ```
>
> Pendekatan ini lebih modular dibanding apply middleware langsung di controller.

**✅ Keputusan**: Middleware alias didaftarkan di `bootstrap/app.php`. GraphQL dibiarkan terbuka untuk keperluan demo playground.

---

## Session 4 — Desain Schema GraphQL & Perbandingan dengan REST

**Tujuan**: Merancang schema GraphQL yang mencerminkan data yang sama dengan REST namun dengan fleksibilitas pemilihan field.

---

**🧑 Prompt Mahasiswa**:
> Saya sedang merancang schema GraphQL menggunakan Lighthouse. Saya bingung antara dua pendekatan:
>
> **Opsi A**: Buat satu Query `loans` saja yang bisa filter by ID sebagai argumen opsional  
> **Opsi B**: Buat dua Query terpisah — `loans` (ambil semua) dan `loan(id: ID!)` (ambil satu)
>
> Menurut best practice GraphQL, mana yang lebih tepat?

**🤖 Respons AI**:
> **Opsi B lebih tepat** dan sesuai konvensi GraphQL. Alasannya:
>
> 1. **Type safety**: `loan(id: ID!)` mengembalikan `Loan` (nullable), sedangkan `loans` mengembalikan `[Loan!]!` (array non-null). Ini berbeda secara semantik dan lebih ekspresif.
> 2. **Single Responsibility**: setiap query memiliki tanggung jawab tunggal yang jelas.
> 3. **Lighthouse directives**: bisa pakai `@find` + `@eq` untuk `loan(id)` dan `@all` untuk `loans`, yang sudah dioptimasi secara internal.
>
> Schema yang saya rekomendasikan:
> ```graphql
> type Query {
>   loans: [Loan!]! @all
>   loan(id: ID! @eq): Loan @find
> }
> ```

**🧑 Follow-up Mahasiswa**:
> Apakah perlu juga buat Mutation `createLoan` di GraphQL meskipun sudah ada `POST /api/v1/loans` di REST?

**🤖 Respons AI**:
> Secara rubrik Tugas 2, hanya Query yang diwajibkan ("minimal 1 Query"). Namun menambahkan Mutation akan menunjukkan pemahaman GraphQL yang lebih komprehensif dan bisa jadi nilai tambah. Dengan Lighthouse, implementasinya sangat singkat menggunakan directive `@create` dan `@spread`. Saran saya: tambahkan saja, kompleksitas kodenya minimal tapi nilai demonstrasinya tinggi.

**✅ Keputusan**: Implementasi Query `loans` + `loan(id)` dan Mutation `createLoan`. Menggunakan Lighthouse directives `@all`, `@find`, `@eq`, `@create`, `@spread`.

---

## Session 5 — Troubleshooting: swagger-php v6 PHP Attributes

**Tujuan**: Menyelesaikan error saat generate Swagger documentation.

---

**🧑 Prompt Mahasiswa**:
> Saya mengalami error saat menjalankan `php artisan l5-swagger:generate`:
> ```
> Required @OA\Info() not found
> ```
> Padahal saya sudah menambahkan `@OA\Info()` di docblock controller. Apa yang salah?

**🤖 Respons AI**:
> Error ini terjadi karena `l5-swagger` versi 11 menggunakan `zircote/swagger-php` versi **6**, yang sudah **tidak mendukung docblock annotations** (`/** @OA\Info */`). Versi 6 menggunakan **PHP 8 Attributes** sebagai sintaks utamanya.
>
> Ubah dari format lama:
> ```php
> /**
>  * @OA\Info(title="...", version="1.0.0")
>  */
> class LoanController {}
> ```
>
> Ke format baru dengan PHP 8 Attributes:
> ```php
> #[OA\Info(version: '1.0.0', title: 'Peminjaman Service API')]
> class LoanController {}
> ```

**🧑 Validasi Mahasiswa**:
> Saya coba ubah ke format PHP Attributes, dan sekarang `l5-swagger:generate` berhasil. Tapi saya mau validasi — apakah format Attributes ini juga berlaku untuk `@OA\Property` di dalam schema model?

**🤖 Respons AI**:
> Ya, semua annotation perlu diubah ke format Attributes. Di Model, gunakan:
> ```php
> #[OA\Schema(
>     schema: 'Loan',
>     properties: [
>         new OA\Property(property: 'id', type: 'integer'),
>         new OA\Property(property: 'book_title', type: 'string'),
>         ...
>     ]
> )]
> class Loan extends Model {}
> ```
> Perhatikan penggunaan `new OA\Property(...)` (bukan `@OA\Property`) di dalam array `properties`.

**✅ Resolusi**: Error teratasi setelah migrasi penuh ke PHP 8 Attributes. Swagger UI berhasil diakses di `/api/documentation` dengan semua 3 endpoint terdokumentasi.

---

## Session 6 — Verifikasi Akhir & Testing Menyeluruh

**Tujuan**: Memastikan semua komponen bekerja sesuai rubrik sebelum commit final.

---

**🧑 Prompt Mahasiswa**:
> Sebelum saya push ke repository, saya ingin verifikasi checklist berikut. Tolong konfirmasi apakah setiap poin sudah benar:
>
> 1. ✅ `GET /api/v1/loans` → 200 dengan array data + meta
> 2. ✅ `GET /api/v1/loans/{id}` → 200 dengan object data, atau 404 kalau tidak ada
> 3. ✅ `POST /api/v1/loans` → 201 dengan data yang baru dibuat
> 4. ✅ Request tanpa `X-IAE-KEY` → ditolak 401
> 5. ✅ GraphQL query `{ loans { id book_title status } }` → mengembalikan data
> 6. ✅ Swagger UI bisa diakses di `/api/documentation`
>
> Ada yang terlewat?

**🤖 Respons AI**:
> Checklist Anda sudah mencakup semua poin kritis dari rubrik. Beberapa hal tambahan yang perlu dipastikan sebelum push:
>
> - **Status code POST harus 201**, bukan 200 — rubrik menyebutkan "status code tepat (200, 201, 404)"
> - **Content-Type response harus `application/json`** — sudah otomatis di Laravel tapi pastikan tidak ada response yang return non-JSON
> - **GraphQL Playground** perlu bisa diakses di `/graphql-playground` untuk keperluan demo
> - **File `AI_prompt_log.md`** wajib ada di root repository sesuai ketentuan tugas
>
> Selain itu semuanya sudah solid.

**🧑 Follow-up Mahasiswa**:
> Satu pertanyaan lagi: untuk Docker, apakah yang dinilai adalah Dockerfile dan docker-compose.yml-nya saja, atau service harus benar-benar running saat demo?

**🤖 Respons AI**:
> Rubrik menyebutkan *"Endpoint berjalan lancar di Docker"*, artinya service idealnya harus bisa dijalankan via Docker. Namun dalam konteks pengumpulan di GitHub, yang bisa diverifikasi dosen adalah kualitas `Dockerfile` dan `docker-compose.yml`. Pastikan konfigurasi Docker Anda:
> - Mengekspos port yang benar (8000)
> - Memiliki health check untuk database
> - Menjalankan `php artisan migrate` sebagai bagian dari startup

**✅ Keputusan**: Semua checklist terverifikasi. Commit final dilakukan setelah memastikan semua test passing.

---

## 📊 Ringkasan Keputusan Teknis

| Keputusan | Pilihan | Alasan |
|-----------|---------|--------|
| Framework | Laravel 12 + PHP 8.2 | Konsisten dengan ekosistem tim, familiar |
| Database (dev) | SQLite | Zero-config, mudah untuk development lokal |
| API Key storage | `.env` variable | Best practice, pisahkan config dari kode |
| Swagger library | L5-Swagger v11 | Official support untuk Laravel |
| Swagger format | PHP 8 Attributes | Wajib untuk swagger-php v6 |
| GraphQL library | Lighthouse v6 | Paling mature untuk Laravel GraphQL |
| GraphQL query | 2 query + 1 mutation | Lebih komprehensif, nilai tambah |
| GraphQL security | Tanpa API Key | Playground untuk kemudahan demo |
| Docker | Single container (Nginx + PHP-FPM) | Simplicity, cukup untuk demo |

---

## 🧠 Refleksi & Critical Thinking

**Hal yang paling menantang:**  
Migrasi syntax dari docblock annotations ke PHP 8 Attributes saat generate Swagger. Ini terjadi karena breaking change antara swagger-php v5 dan v6 yang tidak selalu terdokumentasi dengan jelas di readme library.

**Pelajaran tentang integrasi:**  
Mengimplementasikan service yang mengikuti kontrak standar (Standard Integration Contract) membuat saya menyadari pentingnya *interface consistency* dalam arsitektur microservice. Ketika setiap service menggunakan wrapper respons yang sama, proses integrasi menjadi jauh lebih predictable.

**Keterbatasan pendekatan saat ini:**  
API Key statis berbasis NIM hanya cocok untuk fase development. Pada Tugas 3, mekanisme ini akan digantikan dengan SSO (JWT) yang lebih aman — ini adalah evolusi yang logis dalam siklus development enterprise application.

---

*Dokumen ini merupakan rekaman otentik proses eksplorasi teknis selama pengerjaan Tugas 2 IAE.*  
*NIM: 102022400314 | Service: Peminjaman | Tech Stack: Laravel 12 + Lighthouse + L5-Swagger*
