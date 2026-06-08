# 📚 Peminjaman Service — E-Library IAE

**NIM**: 102022400314  
**Mata Kuliah**: BBK2HAB3 — Integrasi Aplikasi Enterprise  
**Dosen**: Ekky Novriza Alam  
**GitHub Org**: [IAE-2026-48-08](https://github.com/IAE-2026-48-08)

---

## 🏷️ Tentang Service Ini

**Peminjaman Service** adalah mini-service yang bertanggung jawab mengelola proses peminjaman buku pada sistem E-Library. Service ini mengekspos REST API dan GraphQL endpoint untuk berinteraksi dengan service lain dalam ekosistem IAE.

### Proses Bisnis yang Ditangani
- Pengajuan peminjaman akses E-book oleh anggota
- Pemrosesan dan penyimpanan data peminjaman
- Penampilan semua riwayat transaksi peminjaman aktif
- Akses detail peminjaman berdasarkan ID

---

## 🚀 Cara Menjalankan

### Metode 1: Local (Artisan)

```bash
# Clone repository
git clone https://github.com/IAE-2026-48-08/102022400314_Peminjaman-Service.git
cd 102022400314_Peminjaman-Service

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Jalankan migration
php artisan migrate

# Jalankan server
php artisan serve
```

Akses di: `http://localhost:8000`

### Metode 2: Docker

```bash
# Build dan jalankan
docker-compose up -d --build

# Jalankan migration di container
docker exec peminjaman-service php artisan migrate
```

Akses di: `http://localhost:8000`

---

## 📡 REST API Endpoints

Semua endpoint memerlukan header autentikasi:
```
X-IAE-KEY: 102022400314
```

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET`  | `/api/v1/loans` | Ambil semua data peminjaman |
| `GET`  | `/api/v1/loans/{id}` | Ambil detail peminjaman by ID |
| `POST` | `/api/v1/loans` | Buat peminjaman baru |

### Contoh Request

**GET /api/v1/loans**
```bash
curl -X GET http://localhost:8000/api/v1/loans \
  -H "X-IAE-KEY: 102022400314" \
  -H "Content-Type: application/json"
```

**POST /api/v1/loans**
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "X-IAE-KEY: 102022400314" \
  -H "Content-Type: application/json" \
  -d '{
    "member_id": "MBR-001",
    "book_id": "BOOK-123",
    "book_title": "Clean Code",
    "member_name": "Budi Santoso",
    "loan_date": "2025-01-15",
    "due_date": "2025-01-29"
  }'
```

### Format Respons (Standard Integration Contract)

```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [...],
  "meta": {
    "service_name": "Peminjaman-Service",
    "api_version": "v1"
  }
}
```

---

## 📖 Swagger / API Documentation

Akses Swagger UI di: `http://localhost:8000/api/documentation`

---

## 🔷 GraphQL

Akses GraphQL Playground di: `http://localhost:8000/graphql-playground`

**Contoh Query:**
```graphql
query {
  loans {
    id
    member_id
    book_title
    member_name
    loan_date
    due_date
    status
  }
}
```

---

## 🔐 Keamanan

- **Metode**: API Key via Request Header
- **Header Key**: `X-IAE-KEY`
- **Value**: NIM Mahasiswa (`102022400314`)

---

## 🛠️ Tech Stack

- **Framework**: Laravel 12 (PHP 8.2)
- **Database**: SQLite (dev) / MySQL (Docker)
- **API Doc**: L5-Swagger (OpenAPI 3.0)
- **GraphQL**: Lighthouse v6
- **Container**: Docker + Docker Compose
