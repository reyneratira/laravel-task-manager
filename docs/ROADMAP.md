# ROADMAP.md — Rencana Pengembangan
## Task Manager Laravel

---

## Status Legend

| Simbol | Arti                  |
|--------|-----------------------|
| ✅     | Selesai               |
| 🔄     | Sedang dikerjakan     |
| 📋     | Backlog (direncanakan)|
| ❌     | Dibatalkan            |

---

## Fase 1 — Core (Current)

### Database & Model
- ✅ Migration tabel `users` dengan kolom `role`
- ✅ Migration tabel `tasks` dengan soft delete
- ✅ Model `User` dengan `isAdmin()`, `isUser()`, relasi `createdTasks` dan `assignedTasks`
- ✅ Model `Task` dengan scopes `forUser`, `byStatus`, `byPriority`, `overdue`
- ✅ Enum `TaskStatus` dan `TaskPriority` dengan `label()` dan `color()`
- ✅ `DatabaseSeeder` dengan akun admin dan sample user + tugas

### Autentikasi & Otorisasi
- ✅ Auth web dengan Laravel Breeze (login, register, logout)
- ✅ `EnsureAdmin` middleware untuk route `/admin/*`
- ✅ `TaskPolicy` untuk otorisasi per-action
- ✅ Route group `admin.*` dan `user.*`

### Web Interface (Blade)
- ✅ Layout utama dengan navigasi berbasis role
- ✅ Dashboard admin (statistik + tugas terbaru + top user)
- ✅ Dashboard user (statistik tugas sendiri)
- ✅ Admin: list tugas dengan filter + pagination
- ✅ Admin: form create dan edit tugas
- ✅ Admin: detail tugas
- ✅ Admin: list user dengan toggle role
- ✅ User: list tugas sendiri
- ✅ User: detail tugas + update status

### REST API
- ✅ Install dan konfigurasi Laravel Sanctum
- ✅ `POST /api/v1/login` dengan token abilities berdasarkan role
- ✅ `POST /api/v1/register`
- ✅ `GET /api/v1/auth/me`
- ✅ `POST /api/v1/auth/logout` dan `logout-all`
- ✅ `GET|POST|PUT|DELETE /api/v1/tasks`
- ✅ `TaskResource` dan `UserResource`
- ✅ `ForceJsonResponse` middleware
- ✅ Exception handler untuk format JSON konsisten
- ✅ Postman collection siap import

### Testing
- ✅ `TaskTest` — web CRUD dan role authorization (8 test case)
- ✅ `AuthApiTest` — API auth dan token abilities (9 test case)

### Dokumentasi & Config
- ✅ `CLAUDE.md` — konteks project untuk Claude Code
- ✅ `docs/SRS.md` — Software Requirements Specification
- ✅ `docs/DATABASE.md` — skema database dan ERD
- ✅ `docs/API.md` — dokumentasi endpoint API
- ✅ `docs/GIT_WORKFLOW.md` — konvensi commit dan branching
- ✅ `.gitignore` + `.env.example`
- ✅ `README.md` dengan panduan setup

---

## Fase 2 — Notifikasi & Export (Backlog)

### Email Notifikasi
- ✅ `TaskAssigned` dan `TaskUnassigned` Mailable — kirim email saat tugas di-assign/dihapus
- ✅ `TaskDeadlineReminder` Mailable — reminder H-1 deadline
- ❌ Queue job `SendTaskNotification` (Diganti dengan native `ShouldQueue` pada Mailable)
- ✅ Konfigurasi `MAIL_*` di `.env.example`
- ✅ Feature test untuk notifikasi email

### Real-time Notification
- ✅ Install Laravel Reverb
- ✅ `TaskStatusChanged` broadcast event
- ✅ Frontend listener (Echo + WebSocket)
- ✅ Badge notifikasi di navbar

### Export
- 📋 Install Maatwebsite Laravel Excel
- 📋 `TaskExport` class dengan filter yang sama seperti di halaman list
- 📋 Button "Export Excel" di halaman admin list tugas
- 📋 `TaskReportPDF` menggunakan DomPDF
- 📋 Endpoint `GET /api/v1/tasks/export` untuk download via API

---

## Fase 3 — Kolaborasi (Backlog)

### Lampiran File
- 📋 Migration tabel `task_attachments`
- 📋 Model `TaskAttachment` dengan relasi ke `Task`
- 📋 Upload via form (validasi tipe: pdf, jpg, png, docx — max 10MB)
- 📋 Preview inline untuk file gambar
- 📋 Endpoint API untuk upload dan download lampiran

### Komentar
- 📋 Migration tabel `comments` (polymorphic)
- 📋 Model `Comment`
- 📋 CRUD komentar di halaman detail tugas
- 📋 Endpoint API `GET|POST|DELETE /api/v1/tasks/{id}/comments`

---

## Fase 4 — Frontend SPA Vue 3 (Backlog)

### Setup
- 📋 Inisialisasi project Vue 3 + Vite (via Inertia.js)
- 📋 Konfigurasi Axios dengan base URL dan interceptor token
- 📋 Setup Pinia store: `useAuthStore`, `useTaskStore`
- 📋 Vue Router dengan route guard berbasis role

### Halaman
- 📋 Login page
- 📋 Dashboard (statistik dengan Chart.js / Recharts)
- 📋 List tugas (filter, search, pagination)
- 📋 Form create/edit tugas
- 📋 Detail tugas dengan komentar dan lampiran
- 📋 Halaman profil user

### State Management (Pinia)
- 📋 `useAuthStore` — token, user, isAdmin, login(), logout()
- 📋 `useTaskStore` — tasks, fetch(), create(), updateStatus()
- 📋 `useNotifStore` — notifikasi real-time dari Reverb

---

## Fase 5 — DevOps (Backlog)

### CI/CD
- 📋 GitHub Actions: jalankan `php artisan test` setiap push
- 📋 GitHub Actions: Laravel Pint code style check
- 📋 GitHub Actions: deploy otomatis ke Railway saat merge ke `main`

### Deployment
- 📋 `Procfile` untuk Railway
- 📋 Docker: `Dockerfile` + `docker-compose.yml` untuk dev environment
- 📋 Health check endpoint `/up` (sudah ada di Laravel 11)
- 📋 Environment variable management via Railway dashboard

---

## Prioritas Pengerjaan Berikutnya

Berdasarkan dampak untuk portofolio internship:

1. **Fase 4 — Vue 3 SPA** — paling impactful, menunjukkan kemampuan fullstack
2. **Fase 2 — Email notifikasi** — menunjukkan pemahaman Queue dan async job
3. **Fase 5 — GitHub Actions CI** — menunjukkan pemahaman DevOps dasar
4. **Fase 3 — Komentar** — menunjukkan desain relasi polymorphic
5. **Fase 5 — Docker** — nilai plus untuk perusahaan tech
