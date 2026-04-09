# DME

`DME` is being rebuilt from a browser-only `IndexedDB`/PWA app into an online Laravel application backed by `MySQL`.

The target product is a server-rendered app with three roles:

- `super_admin`
- `teacher`
- `student`

Authentication uses Laravel session auth with `username + password`.

## Product Shape

- `super_admin` manages teacher accounts, student accounts, password resets, and note moderation.
- `teacher` sees all students, opens each student record, and manages notes they authored.
- `student` sees only their own record with separate `Teacher Notes` and `My Notes` tabs.
- Notes support text-only, image-only, or mixed posts with up to `6` private images.

The online Laravel app replaces the old offline-first flow. Existing browser `IndexedDB` data is not migrated.

The legacy browser-only app is retired from the main product:

- `/` resolves into the server-rendered auth flow.
- `/manifest.webmanifest` and `/sw.js` are not product routes.
- `resources/js/app/*` is retained only as legacy code pending later removal, not as the main app entrypoint.

## Repository Notes

- The execution plan for the rework lives in `implementation_plan.md`.
- The product summary and assumptions live in `rework_plan.md`.
- Phase 8 is the final documentation and cleanup pass. It should not introduce schema changes or major behavior changes.

## Local Development

Prerequisites:

- `PHP 8.3+`
- `Composer`
- `Node.js` and `npm`
- `MySQL`

Typical setup:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Run the app locally:

```bash
composer run dev
```

Run the test suite:

```bash
composer test
```
# DME
