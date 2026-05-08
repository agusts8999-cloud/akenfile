# AGENTS Quick Handover

This file gives AI coding agents and new maintainers a fast operational context.

## Project Snapshot
- Project: **AkenFile**
- Stack: Laravel + PostgreSQL + Blade + Alpine.js
- Main branch: `main`
- Main app routes: `routes/web.php`

## Where to Work for Common Tasks
- File manager UI/behavior: `resources/views/files/index.blade.php`
- Trash bulk actions: `resources/views/trash/index.blade.php`
- Dashboard widgets: `resources/views/dashboard.blade.php`
- Control Center settings UI: `resources/views/control-center/index.blade.php`
- File business rules: `app/Services/FileService.php`
- Settings read/write: `app/Services/ControlCenterService.php`

## Existing Custom Features to Preserve
- Image preview modal with zoom + scroll behavior
- TinyMCE editor for editable file types
- Configurable preview width (`preview_dialog_width_px`)
- Configurable image thumbnail size (`file_thumbnail_size_px`)
- Recent Activity hide/unhide on dashboard
- Responsive grid/list toggles in file manager
- Responsive trash bulk action bar

## Settings Keys Used in Runtime
- `preview_dialog_width_px`
- `file_thumbnail_size_px`
- `rows_per_page`
- `storage_limit_gb`
- `max_upload_size_mb`

If adding a new setting, update all:
1. validation request (`UpdateControlCenterRequest`)
2. save handler (`ControlCenterController@updateSettings`)
3. default seed (`SystemSettingSeeder`)
4. consuming view/controller logic

## Git Workflow Note
- Repository already has initial commit and remote origin configured.
- Use regular `git add/commit/push` flow unless explicitly asked for force push.

## Final Check Before Finishing a Task
- Validate updated PHP files (`php -l`)
- Check UI lint/diagnostics for edited views
- Verify target flow end-to-end in browser
