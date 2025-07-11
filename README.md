# DEPLOYMENT README

---

## 11/07/2025 — Laravel Updates

### Minor Updates

#### Removed `app/Http/Kernel.php`
- Laravel 11 no longer uses this configuration.
- Middleware setup is now done in `bootstrap/app.php`.

#### Removed `statefulApi()`
- Removed to eliminate CSRF token validation intended for SPA Laravel frontends.
- System is API-based only; all calls are treated as stateless API requests, regardless of domain.
- Resolves CSRF mismatch errors when using Postman or external clients.

#### Moved Global Middleware to Specific Routes
- Global middleware (encryption, decryption) is now applied only to selected route groups, excluding `/` which is used for testing.
- Benefits:
  - Makes API testing easier without needing to disable middleware entirely.
  - New implementations and deployment testing are simpler.
- Note: Routes without encryption are for testing only — move them back to the secured group after testing.

---

## 08/07/2025 — Laravel Updates

### Added Encryption & Decryption Middleware
- Middleware added for full request/response security.
- `DecryptPayload`: Decrypts incoming encrypted payloads (usually the `ml` variable).
- `EncryptResponse`: Encrypts outgoing responses.
- Files are not encrypted — only payload variables.

---

## 03/07/2025 — Laravel Updates

### CORS Configuration
- File location: `config/cors.php`
- Important fields:
  - `allowed_methods`: Currently allows GET, POST, PATCH, DELETE.
  - `allowed_origins`, `allowed_headers`, `supports_credentials`: All properly configured for convenience.
- Note: Laravel 11 supports native CORS — no external packages needed.

---

## Database Updates

- Test data is retained for development and QA.
- No structural changes in the database at this time.

---

## Account Management

- Account creation, deletion, and updates are disabled.
- Authentication and user access are handled based on Sir Melner’s deployment plan.

---

## Notes for Maintenance / Deployment

- Authentication Used: Laravel Sanctum
- Logging: Laravel built-in logging system  
  - Logs path: `storage/logs/`

### Controller Convention
- Feature-specific controllers are inside feature folders.
- Shared/global controllers are in `app/Http/Controllers`.

### File Saving
- File storage uses Laravel Storage (`storage/app/public`).
- Changing the host/domain may break the public storage link.

#### How to fix storage link:
```bash
rm -rf public/storage
php artisan storage:link
