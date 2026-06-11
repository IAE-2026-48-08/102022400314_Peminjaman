# 📚 Peminjaman Service — E-Library IAE

**NIM**: 102022400314 | **Mata Kuliah**: BBK2HAB3 — Integrasi Aplikasi Enterprise  
**Dosen**: Ekky Novriza Alam | **GitHub Org**: [IAE-2026-48-08](https://github.com/IAE-2026-48-08)

---

## 🏷️ Tentang Service Ini

**Peminjaman Service** adalah mini-service yang bertanggung jawab mengelola proses peminjaman buku pada sistem E-Library. Mengekspos **REST API** dan **GraphQL** untuk berinteraksi dengan service lain dalam ekosistem IAE.

**Proses bisnis yang ditangani:**
- Pengajuan peminjaman akses E-book oleh anggota
- Penyimpanan dan penampilan riwayat peminjaman
- Pengecekan detail peminjaman berdasarkan ID

---

## 🔗 Akses Repository & Demo

| Resource | Link |
|----------|------|
| 📦 Repository | [github.com/IAE-2026-48-08/102022400314_Peminjaman](https://github.com/IAE-2026-48-08/102022400314_Peminjaman) |
| 📖 Swagger UI | `http://localhost:8000/api/documentation` |
| 🔷 GraphQL Playground | `http://localhost:8000/graphql-playground` |

---

## 🚀 Cara Menjalankan

### Metode 1 — Local (php artisan)

```bash
# 1. Clone repository
git clone https://github.com/IAE-2026-48-08/102022400314_Peminjaman.git
cd 102022400314_Peminjaman

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Setup database & data contoh
php artisan migrate
php artisan db:seed

# 5. Jalankan server
php artisan serve
```

✅ Akses di: `http://localhost:8000`

---

### Metode 2 — Docker

```bash
# 1. Clone repository
git clone https://github.com/IAE-2026-48-08/102022400314_Peminjaman.git
cd 102022400314_Peminjaman

# 2. Build dan jalankan container
docker compose up --build -d

# 3. Cek container berjalan
docker ps
```

✅ Akses di: `http://localhost:8080`

> **Catatan**: Database, migration, dan seeder sudah dijalankan otomatis saat Docker build. Tidak perlu setup manual tambahan.

---

## 📡 REST API Endpoints

Semua endpoint **wajib** menyertakan header:
```
X-IAE-KEY: 102022400314
```

| Method | Endpoint | Deskripsi | Status Code |
|--------|----------|-----------|-------------|
| `GET` | `/api/v1/loans` | Ambil semua data peminjaman | 200 |
| `GET` | `/api/v1/loans/{id}` | Ambil detail peminjaman by ID | 200 / 404 |
| `POST` | `/api/v1/loans` | Buat peminjaman baru | 201 |
| `*` | `/api/v1/*` tanpa key | Akses tanpa header API Key | 401 |

### Contoh Request POST

```json
POST /api/v1/loans
Header: X-IAE-KEY: 102022400314

{
  "member_id": "MBR-001",
  "book_id": "BOOK-123",
  "book_title": "Clean Code",
  "member_name": "Budi Santoso",
  "loan_date": "2026-06-11",
  "due_date": "2026-06-25",
  "notes": "Peminjaman e-book"
}
```

---

## 🔷 GraphQL

Endpoint: `POST http://localhost:8000/graphql`  
Playground: `http://localhost:8000/graphql-playground`

```graphql
# Query semua peminjaman
query {
  loans {
    id
    book_title
    member_name
    status
  }
}

# Query peminjaman by ID
query {
  loan(id: 1) {
    id
    book_title
    loan_date
    due_date
    status
  }
}
```

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.2) |
| Database | SQLite (dev) |
| REST Docs | L5-Swagger v11 (OpenAPI 3.0) |
| GraphQL | Lighthouse v6 |
| Container | Docker + Apache |
| Security | API Key via `X-IAE-KEY` header |
