# 🤖 AI Prompt Engineering Log — Tugas 2 IAE

**Mahasiswa**: 102022400314  
**Service**: Peminjaman Service (E-Library)  
**Mata Kuliah**: BBK2HAB3 — Integrasi Aplikasi Enterprise  
**Tools AI**: Antigravity (Google DeepMind)  
**Tanggal**: 2026-06-09

---

## 📋 Daftar Prompting Session

### Session 1 — Analisis Tugas & Perencanaan

**Prompt**:
> "Saya ada tugas MK Integrasi Aplikasi Enterprise (IAE). ini adalah kontrak proses bisnis yg udah di acc oleh dosenku. Dan ini adalah instruksi tugasnya, dari tugas 2, 3 dan tugas besar ini akan nyambung ya dan ini nanti menggunakan github sebagai repo nya/buat dinilai sm dosennya nanti. Yg tugas 2 terlebih dahulu yg apa yg perlu aku lakukan."

**Respons AI**:
- AI membaca dan menganalisis 4 file PDF (instruksi tugas, kontrak proses bisnis, Standard Integration Contract, dan Tugas Besar/Tugas 3)
- AI mengidentifikasi bahwa terdapat 3 service dalam ekosistem E-Library: Katalog, Peminjaman, dan Keanggotaan
- AI membuat implementation plan lengkap dengan checklist deliverable Tugas 2

**Hasil**: Pemahaman lengkap tentang scope tugas dan peta 3 service yang harus dibuat secara kelompok.

---

### Session 2 — Konfirmasi Service & Tech Stack

**Prompt**:
> "aku yg Peminjaman, nim ku 102022400314. Tech stack nya aku gk tau karna aku jarang ngoding, tapi kyknya laravel sih karna biasanya utk tugas coding pake itu, dan yg di github temen temenku repo nya pada pake bahasa php/blade sih."

**Respons AI**:
- AI memilihkan Laravel 12 + PHP 8.2 sebagai tech stack (sudah tersedia di sistem via XAMPP)
- AI mengidentifikasi bahwa project ini akan menggunakan `102022400314_Peminjaman-Service` sebagai nama repo
- AI melakukan cek environment: PHP 8.2 ✅, Composer 2.8 ✅, Docker 29.4 ✅, Git 2.51 ✅

**Teknik Prompting**: Zero-shot — AI langsung memahami context dan membuat keputusan teknis tanpa perlu iterasi.

---

### Session 3 — Build REST API dengan Laravel

**Prompt (implisit dari context)**:
> Bantu saya implementasikan 3 endpoint REST API sesuai Standard Integration Contract: GET /api/v1/loans, GET /api/v1/loans/{id}, POST /api/v1/loans dengan format respons standar dan API Key middleware.

**Proses AI**:
1. Membuat `ApiKeyMiddleware` untuk proteksi `X-IAE-KEY` header
2. Membuat `Loan` Model dengan semua field yang relevan
3. Membuat `LoanController` dengan 3 method: `index()`, `show()`, `store()`
4. Mengikuti format respons dari Standard Integration Contract (wrapper `status`, `message`, `data`, `meta`)
5. Mendaftarkan routes di `routes/api.php` dengan prefix `/api/v1`

**Iterasi & Problem Solving**:
- Issue: L5-Swagger tidak auto-discovered → Solusi: Tambah manual di `bootstrap/providers.php`
- Issue: swagger-php v6 menggunakan PHP 8 Attributes, bukan docblock → Solusi: Update ke format `#[OA\Info(...)]`

**Teknik Prompting**: Chain-of-thought — AI membuat file satu per satu secara berurutan dengan dependency yang tepat.

---

### Session 4 — Implementasi GraphQL dengan Lighthouse

**Prompt (implisit)**:
> Implementasikan GraphQL menggunakan Lighthouse untuk Laravel agar bisa mengambil data yang sama dengan REST API.

**Proses AI**:
1. Install `nuwave/lighthouse` via Composer
2. Buat `graphql/schema.graphql` dengan type `Loan`, enum `LoanStatus`, Query `loans` dan `loan(id)`, Mutation `createLoan`
3. Menggunakan Lighthouse built-in directives: `@all`, `@find`, `@eq`, `@create`, `@spread`

**Hasil**: GraphQL endpoint berjalan di `/graphql` dengan Playground di `/graphql-playground`.

---

### Session 5 — Docker Configuration

**Prompt (implisit)**:
> Buat konfigurasi Docker agar service bisa dijalankan menggunakan docker-compose.

**Proses AI**:
1. Buat `Dockerfile` menggunakan `php:8.2-fpm-alpine` dengan Nginx dan Supervisor
2. Buat `docker-compose.yml` dengan 2 service: app + MySQL 8.0
3. Buat `docker/nginx.conf` untuk Nginx sebagai web server
4. Buat `docker/supervisord.conf` untuk menjalankan PHP-FPM dan Nginx dalam satu container

**Pertimbangan Desain**:
- Menggunakan Alpine Linux untuk image yang lebih kecil
- Health check pada MySQL service untuk memastikan database siap sebelum app start
- Named volume untuk persistent data database

---

## 📊 Ringkasan Hasil

| Komponen | Status | Catatan |
|----------|--------|---------|
| REST API (3 endpoints) | ✅ Berhasil | GET all, GET by ID, POST |
| API Key Security | ✅ Berhasil | Header X-IAE-KEY: 102022400314 |
| Standard Response Format | ✅ Berhasil | Sesuai Integration Contract |
| Swagger/OpenAPI Documentation | ✅ Berhasil | Akses di /api/documentation |
| GraphQL (Lighthouse) | ✅ Berhasil | Query loans + loan(id), Mutation createLoan |
| Docker Configuration | ✅ Berhasil | docker-compose up |
| Database Seeder | ✅ Berhasil | 5 data contoh peminjaman |

---

## 🧠 Lesson Learned dari AI Collaboration

1. **Versi Compatibility**: swagger-php v6 yang bundled dengan L5-Swagger v11 sudah menggunakan PHP 8 Attributes (`#[OA\Info]`) bukan docblock (`/** @OA\Info */`). AI perlu iterasi untuk mengenali ini.

2. **Auto-discovery Issue**: L5-Swagger tidak selalu auto-discovered karena `minimum-stability: dev` di composer.json package. Solusi: register manual di `bootstrap/providers.php`.

3. **SQLite untuk Development**: Menggunakan SQLite (built-in Laravel) jauh lebih mudah untuk development lokal tanpa perlu setup MySQL terpisah.

4. **Lighthouse Directives**: Lighthouse menyediakan `@all` dan `@find` yang sangat powerful — tidak perlu menulis resolver manual untuk query sederhana.

---

*Log ini dibuat sebagai bukti akuntabilitas penggunaan AI dalam pengerjaan Tugas 2 IAE sesuai ketentuan tugas.*
