# DATABASE.md — Skema Database
## Task Manager Laravel

---

## ERD (Entity Relationship Diagram)

```
┌──────────────────────────────────────┐
│               users                  │
├──────────────────────────────────────┤
│ PK  id              BIGINT UNSIGNED  │
│     name            VARCHAR(255)     │
│     email           VARCHAR(255) UQ  │
│     password        VARCHAR(255)     │
│     role            ENUM(admin,user) │
│     avatar          VARCHAR(255) NUL │
│     email_verified  TIMESTAMP NUL    │
│     remember_token  VARCHAR(100) NUL │
│     created_at      TIMESTAMP        │
│     updated_at      TIMESTAMP        │
└──────────┬───────────────────────────┘
           │ 1
           │ ┌── created_by (1 admin bisa buat banyak task)
           │ │
           ▼ N
┌──────────────────────────────────────┐
│               tasks                  │
├──────────────────────────────────────┤
│ PK  id              BIGINT UNSIGNED  │
│     title           VARCHAR(255)     │
│     description     TEXT NUL         │
│     status          ENUM(...)        │
│     priority        ENUM(...)        │
│     due_date        DATE NUL         │
│ FK  created_by      BIGINT → users   │
│ FK  assigned_to     BIGINT → users NUL│
│     deleted_at      TIMESTAMP NUL    │
│     created_at      TIMESTAMP        │
│     updated_at      TIMESTAMP        │
└──────────────────────────────────────┘
           ▲ N
           │ └── assigned_to (1 user bisa punya banyak task)
           │
           │ 1
┌──────────┴───────────────────────────┐
│               users                  │
│          (user yang menerima task)   │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│       personal_access_tokens         │  ← dibuat otomatis oleh Sanctum
├──────────────────────────────────────┤
│ PK  id              BIGINT UNSIGNED  │
│     tokenable_type  VARCHAR(255)     │
│     tokenable_id    BIGINT UNSIGNED  │  → users.id
│     name            VARCHAR(255)     │
│     token           VARCHAR(64) UQ   │
│     abilities       TEXT NUL         │
│     last_used_at    TIMESTAMP NUL    │
│     expires_at      TIMESTAMP NUL    │
│     created_at      TIMESTAMP        │
│     updated_at      TIMESTAMP        │
└──────────────────────────────────────┘
```

---

## Detail Tabel

### `users`

| Kolom              | Tipe             | Keterangan                          |
|--------------------|------------------|-------------------------------------|
| `id`               | BIGINT UNSIGNED  | Primary key, auto increment         |
| `name`             | VARCHAR(255)     | Nama lengkap                        |
| `email`            | VARCHAR(255)     | Unique, dipakai untuk login         |
| `password`         | VARCHAR(255)     | Bcrypt hash                         |
| `role`             | ENUM             | `admin` atau `user`, default `user` |
| `avatar`           | VARCHAR(255)     | Nullable, path ke file avatar       |
| `email_verified_at`| TIMESTAMP        | Nullable                            |
| `remember_token`   | VARCHAR(100)     | Nullable, untuk "ingat saya"        |
| `created_at`       | TIMESTAMP        | Auto                                |
| `updated_at`       | TIMESTAMP        | Auto                                |

**Index:** `email` (unique)

---

### `tasks`

| Kolom         | Tipe            | Keterangan                                      |
|---------------|-----------------|-------------------------------------------------|
| `id`          | BIGINT UNSIGNED | Primary key, auto increment                     |
| `title`       | VARCHAR(255)    | Judul tugas, required                           |
| `description` | TEXT            | Nullable, deskripsi detail                      |
| `status`      | ENUM            | `pending`, `in_progress`, `done`, `cancelled`   |
| `priority`    | ENUM            | `low`, `medium`, `high`                         |
| `due_date`    | DATE            | Nullable, batas waktu pengerjaan                |
| `created_by`  | BIGINT UNSIGNED | FK → `users.id`, CASCADE on delete              |
| `assigned_to` | BIGINT UNSIGNED | FK → `users.id`, nullable, SET NULL on delete   |
| `deleted_at`  | TIMESTAMP       | Nullable, soft delete                           |
| `created_at`  | TIMESTAMP       | Auto                                            |
| `updated_at`  | TIMESTAMP       | Auto                                            |

**Index:**
- `(assigned_to, status)` — composite, untuk query tugas per user per status
- `(status, priority)` — untuk filter admin
- `due_date` — untuk query tugas overdue

---

### `personal_access_tokens`

Dibuat otomatis saat `php artisan migrate` setelah install Sanctum.
Tidak perlu migration manual.

| Kolom            | Keterangan                                             |
|------------------|--------------------------------------------------------|
| `tokenable_type` | Class model (`App\Models\User`)                        |
| `tokenable_id`   | ID user pemilik token                                  |
| `name`           | Nama device/token (contoh: `Postman`, `auth_token`)   |
| `token`          | Hash SHA-256 dari token (yang asli hanya muncul sekali)|
| `abilities`      | JSON array abilities: `["tasks:read","tasks:create"]`  |
| `expires_at`     | Nullable, diset ke `now()->addDays(30)` saat login     |

---

### `task_attachments`

| Kolom        | Tipe            | Keterangan                                         |
|--------------|-----------------|----------------------------------------------------|
| `id`         | BIGINT UNSIGNED | Primary key, auto increment                        |
| `task_id`    | BIGINT UNSIGNED | FK → `tasks.id`, CASCADE on delete                 |
| `user_id`    | BIGINT UNSIGNED | FK → `users.id`, nullable, SET NULL on delete      |
| `filename`   | VARCHAR(255)    | Nama asli berkas (tersanitasi)                     |
| `path`       | VARCHAR(500)    | Path relatif unik penyimpanan fisik di storage     |
| `mime_type`  | VARCHAR(100)    | MIME type berkas (contoh: `application/pdf`)       |
| `size`       | BIGINT UNSIGNED | Ukuran berkas dalam bytes                          |
| `created_at` | TIMESTAMP       | Auto                                               |
| `updated_at` | TIMESTAMP       | Auto                                               |

**Index:** `task_id`

---


## Relasi di Model

### `User` → `Task` (One to Many, dua arah)

```php
// Tugas yang DIBUAT oleh user ini (sebagai admin)
public function createdTasks(): HasMany
{
    return $this->hasMany(Task::class, 'created_by');
}

// Tugas yang DIBERIKAN ke user ini
public function assignedTasks(): HasMany
{
    return $this->hasMany(Task::class, 'assigned_to');
}
```

### `Task` → `User` (Many to One, dua FK)

```php
// Siapa yang membuat tugas ini
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

// Siapa yang mengerjakan tugas ini
public function assignee(): BelongsTo
{
    return $this->belongsTo(User::class, 'assigned_to');
}
```

---

## Enum Values

### TaskStatus

| Value        | Label (ID)          | Warna Tailwind |
|--------------|---------------------|----------------|
| `pending`    | Menunggu            | gray           |
| `in_progress`| Sedang Dikerjakan   | blue           |
| `done`       | Selesai             | green          |
| `cancelled`  | Dibatalkan          | red            |

### TaskPriority

| Value    | Label (ID) | Warna Tailwind |
|----------|------------|----------------|
| `low`    | Rendah     | green          |
| `medium` | Sedang     | yellow         |
| `high`   | Tinggi     | red            |

---

## Migrasi

### Urutan Migrasi

```
2024_01_01_000001_create_users_table.php
2024_01_01_000002_create_tasks_table.php
(otomatis) create_personal_access_tokens_table.php  ← dari Sanctum
```

### Menjalankan Migrasi

```bash
# Pertama kali
php artisan migrate

# Reset dan seed ulang (development only)
php artisan migrate:fresh --seed

# Cek status migrasi
php artisan migrate:status
```

---

## Query yang Sering Dipakai

### Tugas user yang overdue

```php
Task::forUser($userId)
    ->overdue()           // scope: due_date < now() AND status not done/cancelled
    ->with('creator')
    ->get();
```

### Statistik admin dashboard

```php
[
    'total'       => Task::count(),
    'pending'     => Task::where('status', TaskStatus::Pending)->count(),
    'in_progress' => Task::where('status', TaskStatus::InProgress)->count(),
    'done'        => Task::where('status', TaskStatus::Done)->count(),
    'overdue'     => Task::overdue()->count(),
]
```

### Top user berdasarkan tugas selesai

```php
User::withCount([
    'assignedTasks as done_count' => fn($q) => $q->where('status', TaskStatus::Done),
])
->where('role', 'user')
->orderByDesc('done_count')
->take(5)
->get();
```

---

## Rencana Penambahan Tabel (Backlog)

### `comments` (FR-COMMENT-01)

```sql
CREATE TABLE comments (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commentable_type VARCHAR(255) NOT NULL,  -- 'App\Models\Task'
  commentable_id  BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  body            TEXT NOT NULL,
  created_at      TIMESTAMP,
  updated_at      TIMESTAMP,
  INDEX (commentable_type, commentable_id)
);
```

### `task_attachments` (FR-ATTACH-01)

```sql
CREATE TABLE task_attachments (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id     BIGINT UNSIGNED NOT NULL,
  user_id     BIGINT UNSIGNED NOT NULL,
  filename    VARCHAR(255) NOT NULL,
  path        VARCHAR(500) NOT NULL,
  mime_type   VARCHAR(100),
  size        INT UNSIGNED,
  created_at  TIMESTAMP,
  updated_at  TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```
