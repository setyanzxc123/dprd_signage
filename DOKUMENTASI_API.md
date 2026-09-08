# Dokumentasi REST API DPRD Sulawesi Tengah (v1)

## 1. Konfigurasi Dasar

### Base URL
* Development: `http://localhost:8081/api/v1`
* Production: `https://<domain-backend>/api/v1`

### Header Wajib
```http
Accept: application/json
Authorization: Bearer <access_token>
```
*(Header `Authorization` diperlukan untuk seluruh endpoint kecuali rute publik dan autentikasi).*

---

## 2. Ringkasan Endpoint

| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `POST` | `/auth/otp/request` | Publik | Minta kode OTP WhatsApp anggota |
| `POST` | `/auth/otp/verify` | Publik | Verifikasi OTP dan terbitkan access token anggota |
| `POST` | `/auth/login` | Publik | Login username dan password admin |
| `GET`  | `/auth/me` | Bearer Token | Cek profil pengguna dan grup hak akses |
| `POST` | `/auth/logout` | Bearer Token | Revoke token aktif |
| `GET`  | `/publik/jadwal` | Publik | Ambil daftar agenda publik |
| `GET`  | `/anggota/jadwal` | Bearer Anggota | Ambil agenda dewan terpersonalisasi |
| `GET`  | `/admin/agenda` | Bearer Admin | Ambil seluruh agenda dewan dan status bentrok |
| `GET`  | `/jadwal/{sumber}/{id}/materi` | Bearer Anggota/Admin | Resolve link materi rapat |
| `GET`  | `/jadwal/{sumber}/{id}/stream` | Bearer Anggota/Admin | Resolve link live stream rapat |
| `GET`  | `/jadwal/{sumber}/{id}/undangan` | Bearer Anggota/Admin | Unduh file PDF undangan rapat |
| `GET`  | `/jadwal/{sumber}/{id}/risalah` | Bearer Anggota/Admin | Ambil teks ringkasan risalah AI |
| `GET`  | `/jadwal/{sumber}/{id}/risalah-pdf` | Bearer Anggota/Admin | Unduh PDF resmi risalah rapat |
| `GET`  | `/dokumen-banmus/{id}` | Publik / Anggota | Unduh file SK Banmus |
| `GET`  | `/notulen/jobs` | Bearer Admin | Daftar antrean dan progres AI worker |
| `GET`  | `/notulen/jobs/{id}` | Bearer Admin | Detail antrean AI worker |
| `GET`  | `/notulen/jobs/{id}/transkrip` | Bearer Admin | Ambil transkrip audio percakapan |
| `POST` | `/notulen/upload/start` | Bearer Admin | Inisialisasi chunked upload audio |
| `POST` | `/notulen/upload/chunk` | Bearer Admin | Upload potongan file audio |
| `POST` | `/notulen/upload/commit` | Bearer Admin | Finalisasi upload audio dan buat job worker |
| `GET`  | `/notulen/risalah/{id}` | Bearer Admin | Ambil naskah risalah |
| `PUT`  | `/notulen/risalah/{id}` | Bearer Admin | Simpan perubahan draf risalah |
| `POST` | `/notulen/risalah/{id}/finalisasi` | Bearer Admin | Finalisasi risalah rapat |
| `GET`  | `/admin/pengaturan/whatsapp/status` | Bearer Admin | Status koneksi WhatsApp Gateway Baileys & kesiapan fallback |
| `POST` | `/admin/pengaturan/whatsapp/pair-code` | Bearer Admin | Minta kode pairing WhatsApp perangkat dinas |
| `POST` | `/admin/pengaturan/whatsapp/logout` | Bearer Admin | Putus sesi WhatsApp perangkat dinas |
| `GET`  | `/api/signage/jadwal` | Publik | Data jadwal TV signage |
| `GET`  | `/api/signage/cuaca` | Publik | Data cuaca BMKG |

---

## 3. Autentikasi (`/auth`)

### `POST /auth/otp/request`
* **Deskripsi**: Mengirimkan kode OTP 6 digit ke nomor WhatsApp anggota dewan. Sistem beroperasi dengan arsitektur Hybrid OTP:
  * **Jalur Primer (Baileys v7)**: Memastikan pesan terkirim dengan jaminan *Synchronous Server ACK* (Centang 1).
  * **Jalur Cadangan (Fazpass Cloud)**: Jika gateway utama mengalami penolakan server (HTTP 502), timeout ACK (HTTP 504), gateway offline (HTTP 503), nomor tidak terdaftar (HTTP 422), rate limit (HTTP 429), atau network timeout, backend otomatis melakukan failover instan ke Fazpass dalam alur sinkron yang sama tanpa memerlukan webhook klien.

Request body:
```json
{
  "no_wa": "081234567890"
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Kode OTP telah dikirimkan ke nomor WhatsApp Anda.",
  "data": {
    "retry_after": 60
  }
}
```

### `POST /auth/otp/verify`
Request body:
```json
{
  "no_wa": "081234567890",
  "otp": "123456",
  "device": "Device Name"
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Login berhasil.",
  "data": {
    "token_type": "Bearer",
    "access_token": "token_string",
    "user": {
      "id": 1,
      "username": "dewan_user",
      "name": "Nama Anggota Dewan",
      "groups": ["anggota"],
      "anggota": {
        "id": 1,
        "jabatan": "Ketua Komisi I",
        "fraksi": "Fraksi A",
        "komisi": "Komisi I"
      }
    }
  }
}
```

### `POST /auth/login`
Request body:
```json
{
  "username": "admin",
  "password": "password",
  "device": "Device Name"
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Login berhasil.",
  "data": {
    "token_type": "Bearer",
    "access_token": "token_string",
    "user": {
      "id": 1,
      "username": "admin",
      "name": "Administrator",
      "groups": ["operator"]
    }
  }
}
```

### `GET /auth/me`
Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "username": "dewan_user",
      "name": "Nama Anggota Dewan",
      "groups": ["anggota"],
      "anggota": {
        "id": 1,
        "jabatan": "Ketua Komisi I",
        "fraksi": "Fraksi A",
        "komisi": "Komisi I"
      }
    }
  }
}
```

### `POST /auth/logout`
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Logout berhasil."
}
```

---

## 4. Modul Publik (`/publik`)

### `GET /publik/jadwal`
Query params:
* `date`: `YYYY-MM-DD` (opsional)
* `month`: `YYYY-MM` (opsional)
* `unit`: integer (opsional)

Response `200 OK`:
```json
{
  "status": "success",
  "date": "2026-09-08",
  "month": null,
  "scope": "publik",
  "units": [
    { "id": 1, "nama": "Komisi I" }
  ],
  "data": [
    {
      "id": 1,
      "source_id": 1,
      "source": "jadwal_umum",
      "judul": "Rapat Paripurna",
      "keterangan": "Keterangan agenda",
      "tanggal": "2026-09-08",
      "waktu_mulai": "09:00",
      "waktu_selesai": "12:00",
      "status": "berlangsung",
      "ruangan": "Ruang Sidang",
      "komisi": "Komisi I",
      "is_public": true,
      "has_materi": true,
      "has_stream": true,
      "materi_url": "http://localhost:8081/go/jadwal-umum/1/berkas",
      "stream_url": "http://localhost:8081/go/jadwal-umum/1/live"
    }
  ]
}
```

---

## 5. Modul Anggota Dewan (`/anggota` & `/jadwal`)

### `GET /anggota/jadwal`
Query params:
* `date`: `YYYY-MM-DD` (opsional)
* `month`: `YYYY-MM` (opsional)
* `unit`: integer (opsional)
* `scope`: `saya` | `semua` (default: `saya`)

Response `200 OK`:
```json
{
  "status": "success",
  "date": "2026-09-08",
  "month": null,
  "scope": "saya",
  "data": [
    {
      "id": 1,
      "source_id": 1,
      "source": "jadwal_umum",
      "judul": "Rapat Kerja",
      "tanggal": "2026-09-08",
      "waktu_mulai": "10:00",
      "waktu_selesai": "13:00",
      "status": "menunggu",
      "ruangan": "Ruang Komisi I",
      "komisi": "Komisi I",
      "is_participant": true,
      "has_materi": true,
      "has_stream": false,
      "has_undangan": true,
      "has_risalah": true,
      "risalah_status": "final",
      "risalah_tersedia": true,
      "materi_url": "http://localhost:8081/api/v1/jadwal/umum/1/materi",
      "undangan_url": "http://localhost:8081/api/v1/jadwal/umum/1/undangan",
      "risalah_url": "http://localhost:8081/api/v1/jadwal/umum/1/risalah",
      "risalah_pdf_url": "http://localhost:8081/api/v1/jadwal/umum/1/risalah-pdf"
    }
  ]
}
```

### `GET /jadwal/{sumber}/{id}/materi`
Path params:
* `sumber`: `umum` | `banmus`
* `id`: integer (`source_id`)

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "url": "https://drive.google.com/file/d/sample/view"
  }
}
```

### `GET /jadwal/{sumber}/{id}/stream`
Path params:
* `sumber`: `umum` | `banmus`
* `id`: integer (`source_id`)

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "url": "https://youtube.com/live/sample"
  }
}
```

### `GET /jadwal/{sumber}/{id}/undangan`
Path params:
* `sumber`: `umum` | `banmus`
* `id`: integer (`source_id`)

Response `200 OK`:
* Content-Type: `application/pdf`
* Body: Binary PDF

### `GET /jadwal/{sumber}/{id}/risalah`
Path params:
* `sumber`: `umum` | `banmus`
* `id`: integer (`source_id`)

Response `200 OK`:
```json
{
  "status": "success",
  "jadwal_type": "umum",
  "jadwal_id": 1,
  "risalah_tersedia": true,
  "status_verifikasi": "final",
  "judul_rapat": "Rapat Kerja",
  "tanggal_rapat": "2026-09-08",
  "ringkasan_eksekutif": "Teks ringkasan eksekutif risalah rapat...",
  "verified_at": "2026-09-08 14:00:00"
}
```

### `GET /jadwal/{sumber}/{id}/risalah-pdf`
Path params:
* `sumber`: `umum` | `banmus`
* `id`: integer (`source_id`)

Response `200 OK`:
* Content-Type: `application/pdf`
* Body: Binary PDF

### `GET /dokumen-banmus/{id}`
Path params:
* `id`: integer

Response `200 OK`:
* Content-Type: `application/pdf` (atau redirect ke URL dokumen)

---

## 6. Modul Admin & Notulensi (`/admin` & `/notulen`)

### `GET /admin/agenda`
Query params:
* `month`: `YYYY-MM` (wajib)
* `source`: `banmus` | `jadwal_umum` (opsional)
* `status`: `menunggu` | `persiapan` | `berlangsung` | `selesai` | `ditunda` | `dibatalkan` (opsional)

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "month": "2026-09",
    "counts": {
      "total": 10,
      "banmus": 4,
      "jadwal_umum": 6,
      "conflicts": 0
    },
    "data": [
      {
        "id": 1,
        "source": "jadwal_umum",
        "source_id": 1,
        "judul": "Rapat Komisi",
        "tanggal": "2026-09-08",
        "waktu_mulai": "09:00",
        "waktu_selesai": "12:00",
        "status": "menunggu",
        "lokasi": "Ruang Rapat",
        "has_conflict": false
      }
    ]
  }
}
```

### `GET /notulen/jobs`
Query params:
* `page`: integer (default: 1)
* `per_page`: integer (default: 15)
* `q`: string (opsional)

Response `200 OK`:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "status": "completed",
      "progress_percent": 100,
      "current_step": "Selesai",
      "audio_filename": "rekaman.mp3",
      "ai_model": "gemini-3.6-flash",
      "jadwal_type": "umum",
      "jadwal_id": 1,
      "judul_rapat": "Rapat Komisi",
      "created_at": "2026-09-08 10:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 15,
    "total": 1,
    "total_pages": 1
  }
}
```

### `GET /notulen/jobs/{id}`
Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "status": "completed",
    "progress_percent": 100,
    "current_step": "Selesai",
    "audio_filename": "rekaman.mp3",
    "error_message": null
  }
}
```

### `GET /notulen/jobs/{id}/transkrip`
Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "job_id": 1,
    "chunks": [
      {
        "chunk_index": 0,
        "transkrip_teks": "Teks percakapan...",
        "start_time_seconds": 0,
        "end_time_seconds": 1800
      }
    ]
  }
}
```

### `POST /notulen/upload/start`
Request body:
```json
{
  "filename": "rekaman.m4a",
  "filesize": 52428800,
  "chunk_size": 2097152,
  "total_chunks": 25
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "upload_id": "upl_abc123"
  }
}
```

### `POST /notulen/upload/chunk`
Content-Type: `multipart/form-data`
* `upload_id`: string
* `chunk_index`: integer (0-indexed)
* `chunk_file`: binary

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "chunk_index": 0,
    "bytes_written": 2097152
  }
}
```

### `POST /notulen/upload/commit`
Request body:
```json
{
  "upload_id": "upl_abc123",
  "jadwal_type": "umum",
  "jadwal_id": 1
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Upload selesai, job transkripsi telah dibuat.",
  "data": {
    "job_id": 1,
    "status": "queued"
  }
}
```

### `GET /notulen/risalah/{id}`
Path params:
* `id`: integer (job_id)

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "job_id": 1,
    "ringkasan_eksekutif": "Teks risalah...",
    "status_verifikasi": "draft",
    "struktur_json": {
      "kesimpulan": ["Poin 1"]
    }
  }
}
```

### `PUT /notulen/risalah/{id}`
Path params:
* `id`: integer (job_id)

Request body:
```json
{
  "ringkasan_eksekutif": "Naskah hasil suntingan...",
  "struktur_json": {
    "kesimpulan": ["Poin 1 disetujui"]
  }
}
```
Response `200 OK`:
```json
{
  "status": "success",
  "message": "Risalah berhasil diperbarui."
}
```

### `POST /notulen/risalah/{id}/finalisasi`
Path params:
* `id`: integer (job_id)

Response `200 OK`:
```json
{
  "status": "success",
  "message": "Risalah berhasil difinalisasi."
}
```

---

## 7. Modul Display & Utilitas (`/api/signage`)

### `GET /api/signage/jadwal`
Response `200 OK`:
```json
{
  "date": "2026-09-08",
  "jadwal": [],
  "upcoming": []
}
```

### `GET /api/signage/cuaca`
Response `200 OK`:
```json
{
  "success": true,
  "data": {
    "suhu": 31,
    "kondisi": "Cerah Berawan",
    "kelembapan": 70,
    "angin_kecepatan_kmh": 12,
    "arah_angin": "Barat Laut",
    "icon": "partly-cloudy-day",
    "stale": false,
    "updated_at": "2026-09-08 17:00:00"
  }
}
```

---

## 8. Pengaturan & Monitoring WhatsApp Gateway (`/admin/pengaturan/whatsapp`)

Endpoint pada modul ini memerlukan token Bearer dengan hak akses `admin` atau `superadmin`.

### `GET /admin/pengaturan/whatsapp/status`
* **Alias**: `GET /admin/whatsapp/status`
* **Deskripsi**: Mengambil status koneksi WhatsApp Gateway (Baileys), kesiapan fallback OTP (Fazpass), dan QR code jika perangkat belum tertaut.
* **Query Params**:
  * `refresh`: `1` | `0` (opsional, jika `1` maka bypass cache offline gateway).

Response `200 OK`:
```json
{
  "status": "success",
  "data": {
    "provider": "hybrid",
    "fallback": {
      "enabled": true,
      "provider": "fazpass",
      "configured": true,
      "can_fallback": true
    },
    "gateway": {
      "configured": true,
      "connected": true,
      "status": "connected",
      "phone": "6281234567890",
      "name": "Humas DPRD Sulteng",
      "error": null
    },
    "qr": {
      "available": false,
      "qr_data_url": null
    }
  }
}
```

Status Code:
* `200 OK`: Sukses mengambil status gateway dan fallback.
* `401 Unauthorized`: Token Bearer tidak valid atau belum disertakan.
* `403 Forbidden`: Token bukan milik akun dengan grup `admin` / `superadmin`.

### `POST /admin/pengaturan/whatsapp/pair-code`
* **Alias**: `POST /admin/whatsapp/pair-code`
* **Deskripsi**: Meminta 8 digit kode pairing WhatsApp untuk menautkan nomor telepon dinas ke Baileys tanpa perlu scan QR code fisik.
* **Request Body**:
```json
{
  "phone": "081234567890"
}
```

Response `200 OK`:
```json
{
  "status": "success",
  "message": "Kode pairing WhatsApp berhasil dibuat.",
  "data": {
    "pairing_code": "ABCD-1234",
    "phone": "6281234567890"
  }
}
```

Response `422 Unprocessable Entity`:
```json
{
  "status": "error",
  "message": "Nomor WhatsApp wajib diisi / Format nomor WhatsApp tidak valid."
}
```

Status Code:
* `200 OK`: Kode pairing berhasil diterbitkan dari gateway.
* `401 Unauthorized`: Token Bearer tidak valid atau belum disertakan.
* `403 Forbidden`: Token bukan milik akun dengan grup `admin` / `superadmin`.
* `422 Unprocessable Entity`: Validasi nomor gagal atau gateway sedang offline/gagal membuat kode pairing.

### `POST /admin/pengaturan/whatsapp/logout`
* **Alias**: `POST /admin/whatsapp/logout`
* **Deskripsi**: Memutuskan sesi WhatsApp perangkat dinas yang saat ini sedang tertaut.

Response `200 OK`:
```json
{
  "status": "success",
  "message": "Sesi WhatsApp telah diputus. Silakan tautkan ulang nomor untuk menghubungkan kembali.",
  "data": {
    "gateway": {
      "configured": true,
      "connected": false,
      "status": "disconnected"
    }
  }
}
```

Response `422 Unprocessable Entity`:
```json
{
  "status": "error",
  "message": "Gagal memutus sesi WhatsApp."
}
```

Status Code:
* `200 OK`: Sesi perangkat dinas berhasil diputuskan.
* `401 Unauthorized`: Token Bearer tidak valid atau belum disertakan.
* `403 Forbidden`: Token bukan milik akun dengan grup `admin` / `superadmin`.
* `422 Unprocessable Entity`: Gateway gagal memproses pemutusan sesi.

---

## 9. Format Error Response

Format respons saat terjadi kesalahan:
```json
{
  "status": "error",
  "message": "Deskripsi kesalahan",
  "errors": {}
}
```

| HTTP Status | Keterangan |
|---|---|
| `400 Bad Request` | Format payload atau parameter tidak valid |
| `401 Unauthorized` | Token tidak valid atau tidak disertakan |
| `403 Forbidden` | Hak akses token tidak mencukupi |
| `404 Not Found` | Data atau berkas tidak ditemukan |
| `422 Unprocessable Entity` | Data belum siap / proses belum selesai |
| `429 Too Many Requests` | Rate limit terlampaui |
| `500 Internal Server Error` | Kesalahan internal server |
