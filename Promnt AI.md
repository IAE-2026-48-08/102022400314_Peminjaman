# AI Prompt Engineering Log — Tugas 2 IAE

Nama: Rosi Rahmawati  
NIM: 102022400314
Service: Service Peminjaman
Kelompok: 05

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
> 1. GET `/api/v1/loans` -> 200 
> 2. GET `/api/v1/loans/{id}` -> 200 
> 3. POST `/api/v1/loans` -> 201 
> 4. Tanpa API Key -> 401 
> 5. GraphQL query berjalan 
> 6. Swagger UI bisa diakses 
>    Ada yang terlewat?"

**Respons AI**:

> Semua poin kritis sudah tercakup. Beberapa hal tambahan yang perlu dipastikan:
>
> - Status code POST harus 201, bukan 200
> - Content-Type response harus `application/json`
> - `AI_prompt_log.md` wajib ada di root repository
> - `Dockerfile` dan `docker-compose.yml` harus ada dan bisa di-build

**Hasil**: Semua komponen terverifikasi. Commit final dilakukan dengan message yang deskriptif.

---

# AI Prompt Engineering Log — Tugas 3 IAE

## Integrasi Federated SSO, Legacy SOAP Audit, & RabbitMQ Message Broker

---

## SESSION 7 — Autentikasi Federated SSO & Payload JWT Dosen

**Prompt Mahasiswa**:

> "Untuk Tugas 3 ini kita kan harus integrasi dengan Federated SSO dari cloud dosen (`iae-sso.virtualfri.id`). Akun warga yang diberikan itu `warga01@ktp.iae.id` (password `KtpDigital2026!`) dan API-KEY kelompokku `KEY-MHS-325`. Bagaimana ya alurnya supaya backend Laravel kita bisa login ke SSO dan memverifikasi JWT yang didapat secara lokal menggunakan RS256?"

**Respons AI**:

> Alurnya adalah backend kita bertindak sebagai proxy login:
>
> 1. Client menembak `POST /api/auth/login` ke backend kita dengan email & password warga.
> 2. Backend kita meneruskan data login tersebut ke endpoint SSO Dosen `/api/v1/auth/token` dan menerima token JWT warga.
> 3. Token JWT ini dikembalikan ke client.
>
> Untuk memvalidasi request berikutnya ke secure endpoint:
>
> 1. Buat `JwtMiddleware` yang mencegat request dan membaca Bearer Token di header `Authorization`.
> 2. Middleware mendownload Public Key (JWKS) dari server dosen di `/api/v1/auth/jwks` secara berkala (gunakan caching agar tidak lambat).
> 3. Lakukan verifikasi signature JWT menggunakan algoritma RS256 secara lokal (dengan library `firebase/php-jwt`).
> 4. Jika valid, sistem mengekstrak `token_type` (misal `user` dipetakan ke role lokal `member`) dan melanjutkan request.

**📌 Keputusan**: Membuat `SsoController` untuk handle login, dan `JwtMiddleware` dengan caching JWKS 24 jam untuk verifikasi token JWT lokal secara aman.

---

## SESSION 8 — Transaksi Kritis & SOAP CDATA Integration

**Prompt Mahasiswa**:

> "Dosen mewajibkan transaksi paling kritis (state-changing) untuk dicatat ke server audit SOAP versi legacy milik dosen. Aku memilih transaksi pembuatan peminjaman (`POST /api/v1/secure/loans`). Tapi data payload transaksi ini kan berformat JSON, sedangkan SOAP meminta format XML. Bagaimana cara menyusun SOAP Envelope di Laravel dan mengirimkannya, terutama cara membungkus JSON di dalam XML supaya karakter kurung kurawal tidak merusak XML?"

**Respons AI**:

> Kamu bisa merakit XML SOAP Envelope secara manual di PHP atau menggunakan `SimpleXMLElement`. Agar payload JSON di dalam XML tidak bentrok dengan karakter XML parser, gunakan tag `<![CDATA[ ... ]]>` untuk membungkus data JSON tersebut.
>
> Contoh struktur XML request body:
>
> ```xml
> <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
>   <soap:Body>
>     <iae:AuditRequest>
>       <iae:TeamID>TEAM-05</iae:TeamID>
>       <iae:ActivityName>CREATE_LOAN</iae:ActivityName>
>       <iae:LogContent><![CDATA[ {"loan_id":1,"book_id":"BOOK-789","member_name":"Rosi"} ]]></iae:LogContent>
>     </iae:AuditRequest>
>   </soap:Body>
> </soap:Envelope>
> ```
>
> Kirim XML ini dengan metode `POST` ke endpoint SOAP Dosen, lalu ekstrak tag `<iae:ReceiptNumber>` atau `<ReceiptNumber>` dari respon XML yang didapat sebagai bukti transaksi legal.

**📌 Keputusan**: Membuat `AuditSoapService.php` untuk merakit SOAP Envelope, membungkus data transaksi ke dalam tag `CDATA`, mengirimkan request via HTTP Client Guzzle, dan mengekstrak nomor resi (`ReceiptNumber`) menggunakan regex/XML parser.

---

## SESSION 9 — Event-Driven Architecture dengan AMQP/RabbitMQ Dosen

**Prompt Mahasiswa**:

> "Selain SOAP Audit, kita juga wajib menyebarkan (broadcast) transaksi kritis tadi ke RabbitMQ broker punya dosen secara asinkron menggunakan routing key kelompok. Aku belum paham cara integrasi ke RabbitMQ lewat HTTP API-nya dosen di `/api/v1/messages/publish`. Payload seperti apa yang harus dikirim ke broker dosen agar exchange `iae.central.exchange` menerima pesannya?"

**Respons AI**:

> Gateway RabbitMQ dosen mengekspos endpoint HTTP POST di `/api/v1/messages/publish`. Untuk mempublikasikannya:
>
> 1. Kamu harus mengambil token M2M (Machine-to-Machine) terlebih dahulu menggunakan API Key kelompok ke SSO Dosen.
> 2. Kirim request HTTP POST ke endpoint publish RabbitMQ dosen dengan menyertakan token M2M tersebut di header `Authorization: Bearer <M2M_TOKEN>`.
> 3. Format body JSON harus menyertakan `routing_key` (misal: `peminjaman.loan.created`) dan `payload` transaksi yang ingin disiarkan.
>
> Dengan begini, service lain (seperti Katalog atau Keanggotaan) bisa menangkap event ini secara asinkron.

**📌 Keputusan**: Membuat `MessageBrokerService.php` yang bertugas mengambil token M2M, lalu mengirimkan payload event peminjaman baru ke RabbitMQ Gateway dosen dengan routing key `peminjaman.loan.created`.

---

## SESSION 10 — Migrasi Database & Kolom audit_receipt

**Prompt Mahasiswa**:

> "Karena kita sekarang mendapatkan nomor resi audit (`ReceiptNumber`) dari server SOAP, kita harus menyimpannya di database lokal. Bagaimana cara membuat migrasi di Laravel 12 untuk menambahkan kolom `audit_receipt` ke tabel `loans` tanpa menghapus data lama?"

**Respons AI**:

> Jalankan perintah berikut di terminal:
>
> ```bash
> php artisan make:migration add_audit_receipt_to_loans_table --table=loans
> ```
>
> Di dalam file migrasi baru tersebut, tambahkan kolom string nullable:
>
> ```php
> Schema::table('loans', function (Blueprint $table) {
>     $table->string('audit_receipt')->nullable()->after('notes');
> });
> ```
>
> Lalu jalankan `php artisan migrate` untuk memperbarui skema database SQLite lokal kamu.

**📌 Keputusan**: Menambahkan kolom `audit_receipt` bertipe string (nullable) ke tabel `loans` melalui file migrasi dan menjalankannya secara lokal.

---

## SESSION 11 — Penyesuaian M2M SSO Dosen (Penambahan Parameter NIM)

**Prompt Mahasiswa**:

> "Ada pengumuman mendadak dari dosen di kelas! Untuk menyesuaikan beberapa hal, sekarang wajib menyertakan parameter `nim` di request body saat menembak token SSO M2M. Contoh request-nya:
>
> ```bash
> curl -s -X POST http://localhost:8080/api/v1/auth/token \
>   -H "Content-Type: application/json" \
>   -d '{"api_key":"KEY-MHS-45","nim":"102022400033"}'
> ```
>
> Bagaimana cara kita menyesuaikan `SsoService` di backend Laravel agar selalu mengirimkan `nim` dan `api_key` kelompok secara dinamis?"

**Respons AI**:

> Kamu bisa menambahkan konfigurasi `nim` di file `.env` kamu:
>
> ```env
> IAE_API_KEY=KEY-MHS-325
> # Tambahkan NIM kamu di env
> services.sso.nim=102022400314
> ```
>
> Lalu, di `SsoService.php`, saat merakit body untuk request token M2M, tambahkan field `nim` di samping `api_key`:
>
> ```php
> $response = Http::post('https://iae-sso.virtualfri.id/api/v1/auth/token', [
>     'api_key' => config('services.sso.api_key'),
>     'nim'     => config('services.sso.nim'), // Sertakan NIM sesuai arahan dosen
> ]);
> ```
>
> Dengan begitu, backend kamu akan selalu mengirimkan request M2M yang valid dan tidak ditolak oleh SSO dosen.

**📌 Keputusan**: Menyesuaikan `SsoService` dengan menambahkan parameter `nim` yang dibaca dari variabel env (`IAE_API_KEY` dipetakan ke NIM) pada payload request token M2M.

---

## SESSION 12 — Dockerization & Sinkronisasi Real-Time (Volume Mounting)

**Prompt Mahasiswa**:

> "Saat aku test di Postman, kenapa ya kalau mau test di localhost aku harus ketik `php artisan serve` terus? Padahal aku sudah jalankan `docker compose up -d` di Docker Desktop dan statusnya 'Started'. Gimana ya caranya agar pengujian di Postman bisa langsung mengarah ke Docker tanpa repot ngetik `artisan serve`?"

**Respons AI**:

> Hal ini terjadi karena:
>
> 1. Postman kamu menembak port `8000` (port `artisan serve`), sedangkan container Docker kamu berjalan di port `8080`. Untuk mengetes Docker, ganti URL di Postman menjadi `http://localhost:8080`.
> 2. Di `docker-compose.yml` belum ada volume mounting. Jadi setiap kali kamu mengubah kode di Windows, perubahan tersebut tidak masuk ke container sampai kamu rebuild.
>
> Solusinya:
>
> - Tambahkan volume mounting di `docker-compose.yml` agar source code dan database SQLite tersinkronisasi otomatis:
>     ```yaml
>     volumes:
>         - .:/var/www/html
>     ```
> - Ubah alamat endpoint API di frontend (`src/services/api.js`) agar menembak ke port `8080` secara default.
> - Publish konfigurasi CORS Laravel (`config/cors.php`) agar mengizinkan akses dari domain frontend (`localhost:5173`) dan izinkan jalur `graphql`.

**📌 Keputusan**: Menambahkan volume mounting di `docker-compose.yml`, mengubah default port frontend ke `8080`, dan mengkonfigurasi CORS di backend agar integrasi frontend-backend di lingkungan Docker berjalan lancar secara real-time.
