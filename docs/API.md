# API.md — Dokumentasi REST API v1
## Task Manager Laravel

**Base URL:** `http://localhost:8000/api/v1`  
**Format:** JSON  
**Auth:** Bearer Token (Laravel Sanctum)

---

## Cara Autentikasi

1. Hit endpoint `POST /login` dengan email & password
2. Simpan `token` dari response
3. Sertakan di setiap request berikutnya:

```
Authorization: Bearer {token}
Accept: application/json
```

---

## Format Response Standar

### Success — single object
```json
{
  "message": "Pesan berhasil.",
  "data": { "id": 1, "title": "..." }
}
```

### Success — list (dengan pagination)
```json
{
  "data": [ {...}, {...} ],
  "links": {
    "first": "http://localhost:8000/api/v1/tasks?page=1",
    "last":  "http://localhost:8000/api/v1/tasks?page=3",
    "prev":  null,
    "next":  "http://localhost:8000/api/v1/tasks?page=2"
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

### Error — validasi (422)
```json
{
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "email": ["Email wajib diisi."],
    "password": ["Password minimal 8 karakter."]
  }
}
```

### Error — lainnya (401, 403, 404, 500)
```json
{
  "message": "Pesan error di sini."
}
```

---

## Auth Endpoints

### POST `/register`

Daftar user baru. Role selalu `user` — tidak bisa daftar sebagai admin.

**Request body:**
```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response 201:**
```json
{
  "message": "Registrasi berhasil.",
  "token": "1|abc123...",
  "user": {
    "id": 2,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "user",
    "is_admin": false,
    "created_at": "2025-01-01T00:00:00+00:00"
  }
}
```

**Error 422:** email sudah terdaftar, password tidak cocok

---

### POST `/login`

Login dan dapatkan token. Token lama dengan nama device yang sama akan dihapus otomatis.

**Request body:**
```json
{
  "email": "admin@taskmanager.test",
  "password": "password",
  "device_name": "Postman"
}
```

> `device_name` opsional. Default: `auth_token`. Gunakan untuk multi-device.

**Response 200:**
```json
{
  "message": "Login berhasil.",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin Utama",
    "email": "admin@taskmanager.test",
    "role": "admin",
    "is_admin": true,
    "created_at": "2025-01-01T00:00:00+00:00"
  }
}
```

**Error 422:** email/password salah

---

### GET `/auth/me` 🔒

Data profil user yang sedang login.

**Response 200:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin Utama",
    "email": "admin@taskmanager.test",
    "role": "admin",
    "is_admin": true,
    "created_at": "2025-01-01T00:00:00+00:00"
  }
}
```

---

### POST `/auth/logout` 🔒

Hapus token yang sedang dipakai. User perlu login ulang.

**Response 200:**
```json
{ "message": "Logout berhasil." }
```

---

### POST `/auth/logout-all` 🔒

Hapus semua token dari semua perangkat.

**Response 200:**
```json
{ "message": "Logout dari semua perangkat berhasil." }
```

---

## Task Endpoints

### GET `/tasks` 🔒

Daftar tugas. Admin melihat semua, user hanya tugasnya sendiri.

**Query parameters:**

| Parameter  | Tipe   | Nilai yang valid                                | Default |
|------------|--------|-------------------------------------------------|---------|
| `status`   | string | `pending`, `in_progress`, `done`, `cancelled`   | -       |
| `priority` | string | `low`, `medium`, `high`                         | -       |
| `search`   | string | Pencarian bebas berdasarkan judul               | -       |
| `per_page` | int    | Jumlah item per halaman                         | 15      |

**Contoh request:**
```
GET /api/v1/tasks?status=pending&priority=high&per_page=10
Authorization: Bearer {token}
```

**Response 200:** list dengan pagination (lihat format standar di atas)

**Struktur item `data`:**
```json
{
  "id": 1,
  "title": "Setup repository Git",
  "description": "Buat repo dan dokumentasi README",
  "status": {
    "value": "pending",
    "label": "Menunggu",
    "color": "gray"
  },
  "priority": {
    "value": "high",
    "label": "Tinggi",
    "color": "red"
  },
  "due_date": "2025-12-31",
  "is_overdue": false,
  "assignee": {
    "id": 2,
    "name": "Budi Santoso",
    "email": "budi@taskmanager.test",
    "role": "user",
    "is_admin": false
  },
  "creator": {
    "id": 1,
    "name": "Admin Utama",
    "email": "admin@taskmanager.test",
    "role": "admin",
    "is_admin": true
  },
  "created_at": "2025-01-01T00:00:00+00:00",
  "updated_at": "2025-01-01T00:00:00+00:00"
}
```

---

### POST `/tasks` 🔒🔑 *(admin only)*

Buat tugas baru.

**Request body:**
```json
{
  "title": "Setup environment development",
  "description": "Install dependencies dan konfigurasi .env",
  "status": "pending",
  "priority": "high",
  "due_date": "2025-12-31",
  "assigned_to": 2
}
```

| Field         | Required | Keterangan                              |
|---------------|----------|-----------------------------------------|
| `title`       | ✅       | Max 255 karakter                        |
| `description` | ❌       | Nullable, max 5000 karakter             |
| `status`      | ✅       | Lihat enum TaskStatus                   |
| `priority`    | ✅       | Lihat enum TaskPriority                 |
| `due_date`    | ❌       | Format `Y-m-d`, harus >= hari ini       |
| `assigned_to` | ❌       | ID user yang ada di database            |

**Response 201:**
```json
{
  "message": "Tugas berhasil dibuat.",
  "data": { ... task object ... }
}
```

**Error 403:** jika dipanggil oleh token user biasa

---

### GET `/tasks/{id}` 🔒

Detail satu tugas. Admin bisa lihat semua, user hanya miliknya.

**Response 200:**
```json
{
  "data": { ... task object ... }
}
```

**Error 403:** user mencoba mengakses tugas orang lain  
**Error 404:** tugas tidak ditemukan

---

### PUT `/tasks/{id}` 🔒

Update tugas.
- **Admin:** bisa update semua field (validasi sama dengan POST)
- **User biasa:** hanya bisa update field `status` (dan tidak bisa set `cancelled`)

**Request body (admin):**
```json
{
  "title": "Judul baru",
  "status": "in_progress",
  "priority": "medium",
  "due_date": "2025-12-31",
  "assigned_to": 3
}
```

**Request body (user biasa):**
```json
{
  "status": "done"
}
```

**Response 200:**
```json
{
  "message": "Tugas berhasil diperbarui.",
  "data": { ... task object ... }
}
```

---

### DELETE `/tasks/{id}` 🔒🔑 *(admin only)*

Hapus tugas (soft delete). Data tetap ada di database dengan `deleted_at` terisi.

**Response 200:**
```json
{ "message": "Tugas berhasil dihapus." }
```

**Error 403:** jika dipanggil oleh token user biasa

---

## Task Attachment Endpoints

### GET `/tasks/{task_id}/attachments` 🔒

Mengambil daftar lampiran berkas pada tugas tertentu.

**Response 200:**
```json
{
  "message": "Daftar lampiran tugas berhasil diambil.",
  "data": [
    {
      "id": 1,
      "task_id": 5,
      "filename": "dokumen.pdf",
      "mime_type": "application/pdf",
      "size": 102400,
      "formatted_size": "100.0 KB",
      "is_image": false,
      "uploader": { "id": 1, "name": "Admin System" },
      "download_url": "http://localhost:8000/attachments/1/download",
      "preview_url": null,
      "created_at": "2026-07-27T10:00:00+00:00"
    }
  ]
}
```

---

### POST `/tasks/{task_id}/attachments` 🔒

Mengunggah berkas lampiran baru (`multipart/form-data`).

**Request body:**
- `file`: File (pdf, jpg, jpeg, png, docx, doc, xlsx, xls, zip — maks 10MB)

**Response 201:**
```json
{
  "message": "Lampiran berhasil diunggah.",
  "data": { "id": 1, "filename": "dokumen.pdf", "...": "..." }
}
```

---

### GET `/attachments/{id}/download` 🔒

Mengunduh berkas lampiran.

---

### DELETE `/attachments/{id}` 🔒

Menghapus berkas lampiran (Admin atau pengunggah berkas).

**Response 200:**
```json
{ "message": "Lampiran berhasil dihapus." }
```

---


## HTTP Status Codes

| Kode | Arti                                              |
|------|---------------------------------------------------|
| 200  | OK — request berhasil                             |
| 201  | Created — resource berhasil dibuat                |
| 401  | Unauthorized — token tidak ada atau tidak valid   |
| 403  | Forbidden — tidak punya izin untuk aksi ini       |
| 404  | Not Found — resource tidak ditemukan              |
| 422  | Unprocessable Entity — validasi gagal             |
| 500  | Internal Server Error — error tidak terduga       |

---

## Token Abilities

| Ability               | Deskripsi                       | Admin | User |
|-----------------------|---------------------------------|:-----:|:----:|
| `tasks:read`          | Lihat daftar dan detail tugas   | ✅    | ✅   |
| `tasks:create`        | Buat tugas baru                 | ✅    | ❌   |
| `tasks:update`        | Edit semua field tugas          | ✅    | ❌   |
| `tasks:update-status` | Update status tugas sendiri     | ✅    | ✅   |
| `tasks:delete`        | Hapus tugas                     | ✅    | ❌   |
| `users:read`          | Lihat daftar user               | ✅    | ❌   |
| `users:manage`        | Kelola user (toggle role, dll)  | ✅    | ❌   |

---

## Postman Collection

Import file `TaskManager_API.postman_collection.json` yang sudah disertakan.

Fitur collection:
- Variable `{{base_url}}` dan `{{token}}` sudah dikonfigurasi
- Script otomatis menyimpan token setelah login ke `{{token}}`
- Test assertions ada di setiap request
- Tersedia request untuk semua endpoint di atas
