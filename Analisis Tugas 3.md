# Analisis Proses Bisnis dan Integrasi Sistem - Service Peminjaman

## Tugas 3 & Laporan Progres Tugas Besar - Integrasi Aplikasi Enterprise (IAE)

NIM: 102022400314  
Nama: Rosi Rahmawati  
Kelas: SI-48-08  
Kelompok: TEAM-05

---

## 1. Pendahuluan dan Konteks Integrasi Enterprise

Dalam perancangan sistem informasi modern skala enterprise, arsitektur monolitik lambat laun mulai digantikan oleh pendekatan microservice untuk meningkatkan skalabilitas, keandalan, dan independensi deployment. Pada proyek Tugas Besar mata kuliah Integrasi Aplikasi Enterprise (IAE) ini, kelompok kami merancang sistem E-Library terintegrasi. Secara individu, saya memegang tanggung jawab penuh atas pengembangan Peminjaman-Service.

Jika pada Tugas 2 fokus saya berada pada sisi internal service—membangun REST API dasar, GraphQL schema, dokumentasi Swagger, dan pengamanan lokal berbasis API Key (`X-IAE-KEY`), maka pada Tugas 3 ini fokus utama bergeser sepenuhnya pada aspek interoperabilitas dan integrasi dengan infrastruktur terpusat (Cloud) yang dikelola oleh dosen pengampu.

Layanan Peminjaman yang saya kembangkan kini wajib berinteraksi aktif dengan tiga komponen eksternal terpusat:

1.  Federated Single Sign-On (SSO) Dosen: Sebagai penyedia identitas tunggal (Identity Provider) untuk mengotentikasi user warga/anggota menggunakan standar token JWT (JSON Web Token) dengan algoritma enkripsi tanda tangan RS256.
2.  Legacy SOAP Audit Server Dosen: Sebagai sistem pencatatan aktivitas bisnis krusial berbasis protokol SOAP/XML untuk menjamin aspek akuntabilitas transaksi (non-repudiation).
3.  RabbitMQ Message Broker Dosen: Sebagai jalur komunikasi asinkron berbasis Event-Driven Architecture untuk menyiarkan event transaksi ke service milik rekan tim lain di dalam kelompok.

---

## 2. Justifikasi Pemilihan Transaksi Kritis (State-Changing Transaction)

Sesuai dengan ketentuan tugas, saya diwajibkan mengidentifikasi dan memilih satu transaksi kritis yang menyebabkan perubahan status data (state-changing transaction) untuk diintegrasikan secara penuh dengan infrastruktur pusat. Transaksi kritis yang saya pilih adalah Pembuatan Peminjaman Buku Baru (Create Loan) yang diakses melalui endpoint:
`POST /api/v1/secure/loans` (dengan proteksi JWT).

### Analisis Justifikasi Pemilihan Transaksi `CREATE LOAN`

Berikut adalah analisis mendalam mengenai alasan pemilihan transaksi ini dibandingkan dengan transaksi non-kritis lainnya (seperti menampilkan daftar peminjaman):

1.  Mutasi Status Data Aset Fisik (State Mutation):
    Proses peminjaman buku bukan sekadar entri data biasa, melainkan representasi perpindahan hak akses aset fisik (dalam hal ini akses E-book) kepada warga. Transaksi ini mengubah status ketersediaan buku secara langsung. Kegagalan pencatatan atau ketidakkonsistenan data di sini akan berakibat fatal pada keakuratan sistem inventaris perpustakaan.
2.  Kebutuhan Audit Hukum yang Kuat (Non-Repudiation):
    Perpustakaan memerlukan jaminan keamanan hukum bahwa data peminjaman tidak dapat disangkal baik oleh peminjam maupun oleh sistem. Integrasi dengan SOAP Audit Dosen memastikan bahwa setiap kali peminjaman dibuat, payload data transaksi tersebut dikirim dan dikunci di server audit pusat. Nomor resi unik (`ReceiptNumber`) yang dikembalikan oleh server SOAP bertindak sebagai "resi digital" resmi yang menjadi bukti otentik transaksi yang tidak bisa dimanipulasi.
3.  Ketergantungan Alur Data Lintas Layanan (Event-Driven Integration):
    Ketika peminjaman berhasil dibuat, service lain dalam kelompok kami harus segera melakukan penyesuaian:
    - Service Katalog (milik rekan tim) harus mengurangi stok buku yang dipinjam.
    - Service Keanggotaan/Member harus mencatat kuota peminjaman aktif warga tersebut agar batas peminjaman tidak terlampaui.
      Oleh karena itu, penyiaran event secara asinkron melalui RabbitMQ Dosen menjadi bagian mutlak dari siklus hidup transaksi ini agar sistem kelompok dapat saling tersinkronisasi tanpa dependensi ketat (loose coupling).

---

## 3. Analisis Proses Bisnis (Probis) Lintas Sistem (Step-by-Step)

Proses bisnis integrasi pada Service Peminjaman berjalan secara runtut dan atomik dari hulu ke hilir. Berikut adalah penjelasan rinci alur datanya:

### A. Inisiasi Akses & Autentikasi (Federated SSO)

- **Warga / Anggota** melakukan login terlebih dahulu melalui antarmuka web (atau Postman) ke backend kita pada endpoint `POST /api/auth/login`.
- Backend kita bertindak sebagai _proxy gateway_ yang meneruskan kredensial tersebut ke server SSO Dosen (`https://iae-sso.virtualfri.id/api/v1/auth/token`).
- Jika kredensial valid (menggunakan akun warga seperti `warga01@ktp.iae.id`), SSO Dosen akan mengembalikan token JWT berformat RS256.
- Token JWT tersebut kemudian dikirimkan kembali ke warga dan disimpan di browser/client untuk digunakan pada request berikutnya.

### B. Validasi Request ke Secure Endpoint

- Saat warga ingin meminjam buku, mereka mengirimkan request `POST /api/v1/secure/loans` dengan melampirkan token JWT di header HTTP `Authorization: Bearer <JWT_TOKEN>`.
- Backend Laravel kita pencegat request tersebut menggunakan middleware khusus bernama `JwtMiddleware`.
- Untuk memverifikasi keaslian token, middleware akan mengambil kunci publik (JWKS) dari endpoint SSO dosen (`/api/v1/auth/jwks`). Agar performa sistem tidak lambat (bottleneck akibat request HTTP berulang), kunci publik ini saya simpan di dalam memori Cache lokal selama 24 jam.
- Jika verifikasi tanda tangan (signature) JWT berhasil menggunakan algoritma RS256, middleware akan membaca klaim `token_type`. Jika tipenya adalah `user`, maka secara lokal akan dipetakan ke role `member` agar warga hanya memiliki hak akses yang sesuai dengan kapasitasnya. Jika token palsu atau kedaluwarsa, sistem langsung menolak dengan response `401 Unauthorized`.

### C. Penyimpanan Lokal Tahap Awal (Local Storage)

- Setelah lolos dari middleware otentikasi, backend memproses data input (NIM, ID Buku, Judul Buku, dll.) menggunakan validator ketat.
- Jika validasi lolos, data peminjaman disimpan ke database SQLite lokal dengan status awal `active`. Pada tahap ini, kolom `audit_receipt` masih bernilai `NULL` karena transaksi belum mendapat nomor resi dari pusat.

### D. Registrasi Bukti Audit Terpusat (SOAP Audit Logging)

- Sistem kemudian merakit payload XML SOAP Envelope secara dinamis di dalam `AuditSoapService.php`.
- Karena server audit dosen meminta data transaksi dikirim dalam format JSON namun dibungkus di dalam tag XML, saya membungkus string JSON tersebut menggunakan tag **`<![CDATA[ ... ]]>`** di dalam tag `<LogContent>`. Langkah ini krusial agar parser XML di server dosen tidak menganggap kurung kurawal `{}` pada JSON sebagai tag XML yang rusak.
- XML dikirim melalui request HTTP POST ke server SOAP audit dosen. Server memprosesnya dan mengembalikan response XML yang berisi kode status sukses beserta `<ReceiptNumber>` unik (contoh: `IAE-LOG-2026-EEA9461A`).
- Backend mengekstrak nomor resi tersebut dan langsung meng-update kolom `audit_receipt` pada baris peminjaman terkait di database lokal sebagai bukti sah.

### E. Penyiaran Event Asinkron (AMQP / RabbitMQ)

- Terakhir, backend memanggil `MessageBrokerService.php`.
- Backend terlebih dahulu menembak endpoint SSO untuk mendapatkan token M2M (Machine-to-Machine) kelompok (`TEAM-05`) menggunakan API Key kelompok (`KEY-MHS-325`) dan NIM saya (`102022400314`) di dalam request body.
- Dengan membawa token M2M tersebut, backend mempublikasikan event peminjaman baru ke HTTP RabbitMQ Gateway milik dosen pada exchange `iae.central.exchange` dengan routing key `peminjaman.loan.created`.
- Service Katalog dan Member kelompok kami yang telah subscribe ke routing key ini akan otomatis menangkap pesan tersebut secara asinkron untuk memperbarui data di sisi mereka.

### F. Respons Akhir ke Client

- Setelah seluruh proses di atas berhasil diselesaikan secara berurutan, backend mengembalikan response JSON dengan status code `201 Created` beserta data peminjaman lengkap dan resi audit SOAP kepada warga.

---

## 4. Analisis Teknis dan Tantangan Implementasi

Selama pengerjaan integrasi Tugas 3 ini, saya menghadapi beberapa tantangan teknis yang membutuhkan analisis mendalam dan solusi arsitektural yang tepat:

### 4.1. Verifikasi Kriptografi Token JWT (RS256 & Caching JWKS)

SSO Dosen menggunakan algoritma RS256 (Asymmetric Cryptography) untuk menandatangani JWT. Artinya, token ditandatangani menggunakan Private Key milik server dosen, dan kita harus memverifikasinya menggunakan Public Key dosen.

- JWKS Endpoint: Public Key disediakan dalam format JWKS (JSON Web Key Set) di endpoint `/api/v1/auth/jwks`.
- Optimasi Performa (Caching): Menembak endpoint JWKS dosen pada setiap request API masuk akan menyebabkan overhead jaringan yang sangat besar dan memperlambat response time backend (bisa memakan waktu 1-2 detik per request).
- Solusi: Saya mengimplementasikan mekanisme caching menggunakan Laravel Cache helper. Backend hanya akan melakukan request HTTP untuk mendownload JWKS jika data cache kosong atau telah kedaluwarsa (di-set selama 24 jam). Hal ini berhasil memangkas waktu verifikasi token hingga kurang dari 10 milidetik pada request berikutnya.

### 4.2. CDATA Handling pada SOAP Client

Protokol SOAP berbasis XML sangat sensitif terhadap karakter khusus. Mengirim data JSON mentah di dalam tag XML dapat merusak struktur dokumen jika di dalam JSON terdapat karakter seperti `<`, `>`, `&`, atau kurung kurawal `{}` yang diinterpretasikan salah oleh parser XML server audit.

- Solusi CDATA (Character Data): Saya menggunakan blok `<![CDATA[ ... ]]>` untuk membungkus payload JSON peminjaman di dalam elemen `<LogContent>`. Blok CDATA memberi tahu parser XML dosen agar mengabaikan karakter di dalamnya dan membacanya sebagai teks/string murni.
- Parsing Response: Response SOAP dikembalikan dalam bentuk XML Envelope. Saya menggunakan ekstrak Regex (`preg_match`) dan `SimpleXMLElement` untuk mengurai tag `<ReceiptNumber>` secara dinamis untuk mengantisipasi jika terjadi perubahan namespace XML di kemudian hari.

### 4.3. Publikasi Asinkron via HTTP RabbitMQ Gateway

Meskipun RabbitMQ biasanya diakses menggunakan protokol AMQP (port 5672), untuk mempermudah pengerjaan dan keamanan jaringan lab, dosen menyediakan Gateway HTTP API di port 80/443 (`/api/v1/messages/publish`).

- Otentikasi M2M: Berbeda dengan otentikasi warga, untuk mengirim pesan ke broker, aplikasi kita harus diidentifikasi sebagai sistem kelompok (_Machine-to-Machine_).
- Parameter NIM & API Key: Sesuai arahan dosen terbaru, payload login M2M harus menyertakan parameter `nim` (NIM saya: `102022400314`) dan `api_key` kelompok (`KEY-MHS-325`). Token JWT M2M yang didapatkan dari request ini kemudian disisipkan pada header request publikasi RabbitMQ.

### 4.4. Sinkronisasi Real-Time Docker Container (Volume Mounting)

Pada pengujian awal menggunakan Docker, perubahan kode program yang saya lakukan di Windows tidak langsung tercermin di dalam container. Hal ini terjadi karena Dockerfile melakukan penyalinan statis (`COPY . .`) saat proses build image.

- Solusi Volume Mounting: Saya menambahkan konfigurasi `volumes:` pada `docker-compose.yml` untuk memetakan direktori proyek lokal di host langsung ke direktori `/var/www/html` di dalam container. Dengan ini, perubahan kode PHP maupun file database SQLite lokal otomatis langsung tersinkronisasi secara real-time ke dalam Docker container tanpa perlu melakukan rebuild image berulang kali.

---

## 5. Sequence Diagram

Berikut adalah visualisasi alur komunikasi lintas sistem yang telah saya susun untuk merepresentasikan integrasi Tugas 3 ini:

### Diagram Sequence

![Sequence Diagram Tugas 3](Diagram%20Sequence.png)

---

## 6. Rencana Arsitektur UI/Website Dashboard (Persiapan Tugas Besar)

Sebagai bagian dari persiapan penggabungan (merger) sistem ke tingkat kelompok pada Tugas Besar, berikut adalah konsep arsitektur frontend dashboard yang akan menghubungkan antarmuka pengguna ke service peminjaman:

### A. Konsep Desain UI & Estetika Dashboard

- Visual Theme: Desain moderen bertema Dark Mode dengan implementasi gaya Glassmorphism (kartu semi-transparan dengan efek buram/blur) untuk memberikan kesan premium, bersih, dan canggih.
- Aksen Warna: Latar belakang gelap (Navy/Charcoal) dipadukan dengan aksen neon bernuansa Sapphire Blue dan Electric Purple untuk menonjolkan data-data status penting.
- Tipografi: Menggunakan font Sans-Serif modern seperti `Inter` atau `Outfit` untuk keterbacaan data yang optimal.

### B. Komunikasi Frontend-Backend (Axios Interceptors)

Aplikasi frontend yang berbasis React + Vite akan berinteraksi dengan backend melalui API Gateway kelompok menggunakan Axios.

- JWT Lifecycle: Token JWT yang didapat dari SSO akan disimpan sementara di `localStorage`.
- Request Interceptor: Otomatis menyisipkan header `Authorization: Bearer <Token>` pada setiap request ke backend.
- Response Interceptor: Jika backend mengembalikan status `401 Unauthorized` (karena token expired), frontend akan mendeteksi ini, menghapus token lokal, dan mengarahkan user kembali ke halaman login secara halus dengan notifikasi Toast.
- Real-time Feedback: Menampilkan Loading Skeletons saat data sedang di-fetch, dan langsung menampilkan nomor resi audit SOAP (`IAE-LOG-*`) secara visual di layar sebagai bukti transaksi digital warga.

---
