# AI Prompt Engineering Log — Tugas 2 IAE

**Nama**: Rosi Rahmawati  
**NIM**: 102022400314
**Service**: Service Peminjaman Buku
**Kelompok**: 05
**Mata Kuliah**: BBK2HAB3 — Integrasi Aplikasi Enterprise

---

## Tentang Dokumen Ini

Dokumen ini mencatat seluruh sesi prompting yang dilakukan selama pengerjaan Tugas 2 IAE. Log ini menjadi bukti proses berpikir dan kolaborasi aktif dengan AI sebagai _pair programming assistant_.

---

## SESSION 1 — Analisis Kontrak & Validasi Scope Endpoint

**Prompt Mahasiswa**:

> "Berdasarkan standard integration contract, saya mendapat bagian service 2 yaitu Peminjaman. Endpoint yang wajib saya implementasikan ada 3: GET semua data, GET by ID, dan POST buat data baru. Apakah tiga endpoint ini sudah cukup atau perlu tambahan endpointnya lagi seperti put/delete?"

**Respons AI**:

> Berdasarkan kontrak yang mendefinisikan pola Collection + Resource + Action, tiga endpoint tersebut memenuhi persyaratan minimum:
>
> - `GET /api/v1/loans` → Collection
> - `GET /api/v1/loans/{id}` → Resource
> - `POST /api/v1/loans` → Action
>
> PUT dan DELETE tidak disebutkan dalam rubrik Tugas 2. Menambahkannya justru berisiko memperluas scope yang tidak dinilai (scope creep).

**Validasi Mahasiswa**:

> "Oke berarti sekarang fokus ke dokumentasi Swagger dan GraphQL aja seperti ditugas 2."

**📌 Keputusan**: Implementasi tepat 3 endpoint sesuai kontrak. Tidak menambah PUT/DELETE.

---

## SESSION 2 — Perancangan Format Respons JSON

**Prompt Mahasiswa**:

> "Saya merancang format json response dengan wrapper seperti ini? Dan apakah `total` perlu ada di semua endpoint atau cukup di GET collection saja?"
>
> ```json
> {
>   "status": "success",
>   "message": "...",
>   "data": [...],
>   "meta": { "service_name": "...", "api_version": "v1", "total": 6 }
> }
> ```

**Respons AI**:

> Format ini sudah benar. Untuk field `total`:
>
> - **GET collection** → wajib ada `total` agar consumer tahu jumlah record
> - **GET by ID & POST** → cukup `service_name` dan `api_version`, tanpa `total`
>
> Untuk response error, struktur harus berbeda:
>
> ```json
> { "status": "error", "message": "...", "errors": null }
> ```

**Keputusan**: Field `total` hanya di endpoint collection. Field `errors` dipakai untuk validasi (array) dan null untuk error umum.

---

## SESSION 3 — Implementasi & Validasi API Key Middleware

**Prompt Mahasiswa**:

> "Untuk implementasi `X-IAE-KEY`, saya rencanakan menyimpan API Key di `.env` bukan di hardcode. Tapi saya bingung apakah middleware ini perlu juga diterapkan ke endpoint GraphQL?"

**Respons AI**:

> Menyimpan di `.env` sudah merupakan best practice — memisahkan konfigurasi dari logika aplikasi.
>
> Untuk GraphQL: kontrak tidak mewajibkan proteksi yang sama. GraphQL Playground biasanya diakses sebagai tool eksplorasi developer. Rekomendasi:
>
> - **REST API** → proteksi wajib dengan `X-IAE-KEY`
> - **GraphQL** → biarkan terbuka untuk kemudahan demo

**Follow-up & Validasi Mahasiswa**:

> "Cara yang paling clean di Laravel 12 untuk register middleware ke route group tertentu itu bagaimana?"

**Respons AI Lanjutan**:

> Di Laravel 12, daftarkan sebagai alias di `bootstrap/app.php`:
>
> ```php
> $middleware->alias(['api.key' => ApiKeyMiddleware::class]);
> ```
>
> Lalu apply ke route group di `routes/api.php`:
>
> ```php
> Route::middleware('api.key')->prefix('v1')->group(function () {
>     Route::get('/loans', [LoanController::class, 'index']);
>     Route::get('/loans/{id}', [LoanController::class, 'show']);
>     Route::post('/loans', [LoanController::class, 'store']);
> });
> ```

**Keputusan**: Middleware alias di `bootstrap/app.php`, apply hanya ke route group `/api/v1`. GraphQL dibiarkan terbuka.

---

## SESSION 4 — Desain Schema GraphQL

**Prompt Mahasiswa**:

> "Saya bingung milih design schema nya anatar berikut ini:
>
> - **Opsi A**: Satu query `loans` dengan argumen ID opsional
> - **Opsi B**: Dua query terpisah, loans untuk semua dan loan(id) untuk satu
>
> Mana yang lebih sesuai best practice GraphQL?"

**Respon oleh AI**:

> **Opsi B lebih tepat** karena:
>
> 1. **Type safety**: `loan(id)` mengembalikan `Loan` (nullable), `loans` mengembalikan `[Loan!]!` — berbeda secara semantik
> 2. **Single Responsibility**: setiap query memiliki tanggung jawab tunggal
> 3. **Efisiensi Lighthouse**: bisa pakai directive `@all` dan `@find` yang dioptimasi secara internal
>
> ```graphql
> type Query {
>     loans: [Loan!]! @all
>     loan(id: ID! @eq): Loan @find
> }
> ```

**Validasi Mahasiswa**:

> "Apakah perlu juga buat mutation createLoan meskipun sudah ada POST /api/v1/loans?"

**Respons AI**:

> Secara rubrik hanya Query yang diwajibkan, tapi Mutation menunjukkan pemahaman GraphQL lebih komprehensif. Dengan directive `@create` dan `@spread`, kompleksitasnya minimal tapi nilai demonstrasinya tinggi.

**Keputusan**: Implementasi dua Query + satu Mutation. Menggunakan Lighthouse directives `@all`, `@find`, `@eq`, `@create`, `@spread`.

---

## SESSION 5 — GraphQL Playground 404

**Prompt Mahasiswa**:

> "Saat membuka `http://localhost:8000/graphql-playground` hasilnya 404 Not Found. Padahal Lighthouse sudah terinstall. Apa yang salah?"

**Respons AI**:

> Lighthouse v6 sudah tidak menyertakan GraphQL Playground secara bawaan — fitur ini dipindahkan ke package terpisah `mll-lab/laravel-graphql-playground`. Perlu diinstall manual.

**Langkah yang diambil:**

> ```bash
> composer require mll-lab/laravel-graphql-playground
> php artisan vendor:publish --tag=lighthouse-config
> ```

**Validasi Mahasiswa**:

> "Setelah install dan restart server, itunya muncul tapi sisi kanan error dan tidak bisa fetch schema. Query juga gk bisa dijalanin jadi hrs apa?"

**Respons AI Lanjutan**:

> Error ini karena schema menggunakan tipe `Date` dan `DateTime` tanpa mendefinisikan scalar resolver-nya. Tambahkan di bagian atas `schema.graphql`:
>
> ```graphql
> scalar Date @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\Date")
> scalar DateTime
>     @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\DateTime")
> ```

**Resolusi**: Playground berhasil menampilkan data setelah scalar definition ditambahkan dan server di-restart.

---

## SESSION 6 — Verifikasi Akhir

**Prompt Mahasiswa**:

> "Saya ingin verifikasi checklist akhir sebelum push:
>
> 1. GET `/api/v1/loans` → 200 ✅
> 2. GET `/api/v1/loans/{id}` → 200 ✅
> 3. POST `/api/v1/loans` → 201 ✅
> 4. Tanpa API Key → 401 ✅
> 5. GraphQL query berjalan ✅
> 6. Swagger UI bisa diakses ✅
>    Ada yang terlewat?"

**Respons AI**:

> Semua poin kritis sudah tercakup. Beberapa hal tambahan yang perlu dipastikan:
>
> - Status code POST harus **201**, bukan 200
> - Content-Type response harus `application/json`
> - `AI_prompt_log.md` wajib ada di root repository
> - `Dockerfile` dan `docker-compose.yml` harus ada dan bisa di-build

**Hasil**: Semua komponen terverifikasi. Commit final dilakukan dengan message yang deskriptif.
