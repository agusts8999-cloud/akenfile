# Developer Guide

## Purpose
This guide helps new developers continue AkenFile quickly without reverse-engineering the whole codebase.

## High-Level Architecture
- **Framework**: Laravel + Blade + Alpine.js
- **Backend domain**:
  - `FileController` + `FileService`: core file operations, preview/editor APIs
  - `FolderController` + `FolderService`: folder operations and hierarchy
  - `SharedController` + `ShareService`: user/public sharing
  - `TrashController`: restore/force delete
  - `ControlCenterController` + `ControlCenterService`: system settings
- **Authorization**:
  - `FilePolicy`, `FolderPolicy`
  - admin bypass, user owner-scoped access
- **Storage**:
  - default disk: `public`
  - upload path format: `uploads/{user_id}/{folder_id_or_root}`

## Key Folders
- `app/Http/Controllers`: web request handlers
- `app/Services`: business logic
- `app/Models`: Eloquent models
- `resources/views`: Blade UI
- `routes/web.php`: main app routes
- `database/migrations` + `database/seeders`: schema/default settings

## Critical Flows

### 1. File List, Preview, and Edit
- Main page: `resources/views/files/index.blade.php`
- Data source: `FileController@index`
- Preview endpoints:
  - `GET /file/{file}/preview`
  - `GET /file/{file}/preview/image`
- Editor endpoints:
  - `GET /file/{file}/content`
  - `PATCH /file/{file}/content`

### 2. System Settings
- UI: `resources/views/control-center/index.blade.php`
- Save handler: `ControlCenterController@updateSettings`
- Access service: `ControlCenterService::getSetting()`
- Important keys:
  - `preview_dialog_width_px`
  - `file_thumbnail_size_px`
  - `rows_per_page`
  - `storage_limit_gb`
  - `max_upload_size_mb`

### 3. Trash Bulk Actions
- UI + Alpine logic: `resources/views/trash/index.blade.php`
- Endpoints:
  - `POST /trash/bulk/restore`
  - `DELETE /trash/bulk/force` (method spoof on frontend for compatibility)

## Frontend State Notes (Alpine)
- File manager state is inline in `resources/views/files/index.blade.php`.
- It holds:
  - file/folder selection state
  - upload queue state
  - preview modal state and zoom calculations
  - editor modal state (TinyMCE)

When changing behavior, update both:
- Blade markup
- matching Alpine methods/properties

## Safe Change Workflow
1. Update service/controller logic first.
2. Update Blade and Alpine state second.
3. Run syntax/lint checks:
   - `php -l <updated_php_file>`
   - use IDE lint for Blade/JS
4. Verify major screens:
   - Files
   - Shared
   - Trash
   - Control Center
   - Dashboard

## Common Pitfalls
- **Route URL style mismatch**: prefer relative routes (`route(..., [], false)`) for fetch-based UI calls.
- **Method DELETE via fetch**: some environments block raw DELETE; use method spoof (`POST` + `_method=DELETE`) when needed.
- **Settings not persisting**: verify the key exists in `system_settings` and is validated/saved in request + controller.
- **Image preview UX**: avoid CSS transform-only zoom when scroll behavior is required; use real rendered dimensions for overflow.

## Suggested Next Improvements
- Extract large inline Alpine code into modular JS files.
- Add feature tests for preview/editor/settings persistence.
- Add API/resource transformers for JSON payload consistency.
- Add CI for lint + tests on PR.
