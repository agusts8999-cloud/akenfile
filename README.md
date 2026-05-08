# AkenFile

Enterprise file management system built with Laravel + PostgreSQL.

## Tech Stack
- Laravel (PHP)
- PostgreSQL
- Blade + TailwindCSS + Alpine.js
- Policy-based authorization (admin bypass supported)
- Storage abstraction (`public` disk, S3/MinIO-ready)

## Main Features
- Authentication (register, login, logout, session)
- RBAC basic (`admin`, `user`)
- File management: upload, list, download, rename, move, delete
- Nested folder navigation with breadcrumb
- Bulk actions (copy/move/delete, restore/force delete in Trash)
- Upload queue with progress, pause/resume, retry
- Shared files and public links
- Trash management for files and folders

## New UI/UX Enhancements
- Image preview modal with:
  - fixed viewport + scroll behavior
  - zoom in/out/reset controls
  - dynamic preview dialog width from Control Center
- TinyMCE editor for supported editable files
- Thumbnail image in file list (table + grid)
- Editable file indicator badge in file list
- Configurable thumbnail size from Control Center
- Responsive bulk action bar in Trash page
- Recent Activity section can be hide/unhide (default hidden)
- Responsive folder display mode (grid/list toggle)

## Control Center Settings
Configurable system values include:
- Theme
- Allowed extensions
- Max upload size
- Storage limit
- Rows per page
- Preview dialog width (`preview_dialog_width_px`)
- File thumbnail size (`file_thumbnail_size_px`)
- SMTP settings for email link sharing

## Setup (Laragon / Linux Server)
1. Go to project folder:
   - `d:/development/laragon/www/AkenFile` (Laragon)
   - or your Linux deployment path
2. Install dependencies:
   - `composer install`
   - `npm install`
3. Configure `.env` (PostgreSQL):
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5432`
   - `DB_DATABASE=akenfile_db`
   - `DB_USERNAME=...`
   - `DB_PASSWORD=...`
4. Generate key:
   - `php artisan key:generate`
5. Run migration + seed:
   - `php artisan migrate --seed`
6. Link storage:
   - `php artisan storage:link`
7. Build frontend assets:
   - `npm run build`
8. Serve app from web server with document root pointing to `public`.

## Default Admin Seeder
- Email: `admin@aken.id`
- Password: `Password123!`

## Important Endpoints
- `GET /files`
- `POST /upload`
- `GET /download/{file}`
- `GET /file/{file}/preview`
- `GET /file/{file}/preview/image`
- `GET /file/{file}/content`
- `PATCH /file/{file}/content`
- `DELETE /file/{file}`
- `POST /folder`
- `DELETE /folder/{folder}`

## Notes
- Upload path: `storage/app/public/uploads/{user_id}/{folder_id_or_root}`
- Regular users are owner-scoped by policy.
- Admin can manage all users and all files.

## Handover and Continuation Docs
- Developer guide: `docs/DEVELOPER_GUIDE.md`
- AI/maintainer quick context: `AGENTS.md`
