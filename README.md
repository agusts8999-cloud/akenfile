# AkenFile

Enterprise file management system built with Laravel + PostgreSQL, optimized for Laragon (Windows).

## Stack
- Laravel (latest)
- PostgreSQL
- Blade + TailwindCSS + Alpine.js
- Laravel Policy for access control
- Storage abstraction (`public` disk now, ready for S3/MinIO later)

## Core Features
- Authentication (register, login, logout, session)
- RBAC basic (`admin`, `user`)
- File operations: upload, list, download, rename, move, delete
- Nested folders with breadcrumb navigation
- Policy-based authorization with admin bypass
- Upload progress indicator per file in UI

## Setup in Laragon
1. Go to project folder:
   - `d:/development/laragon/www/AkenFile`
2. Install dependencies:
   - `composer install`
   - `npm install`
3. Configure environment (`.env`) to PostgreSQL:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5432`
   - `DB_DATABASE=akenfile_db`
   - `DB_USERNAME=postgres`
   - `DB_PASSWORD=postgres`
4. Generate key (if needed):
   - `php artisan key:generate`
5. Run migration and seed:
   - `php artisan migrate --seed`
6. Link storage:
   - `php artisan storage:link`
7. Build frontend:
   - `npm run build`
8. Serve app from Laragon Apache (document root points to `public`).

## Default Admin Seeder
- Email: `admin@aken.id`
- Password: `Password123!`

## Main Endpoints
- `POST /upload`
- `GET /files`
- `GET /download/{id}`
- `DELETE /file/{id}`
- `POST /folder`
- `DELETE /folder/{id}`

## Notes
- Upload path format: `storage/app/public/uploads/{user_id}/{folder_id_or_root}`
- Access policies enforce owner-only access for regular users.
- Admin can manage all users and files.
