# Rework DME Into An Online Role-Based Laravel App

## Summary
- Replace the current browser-only `IndexedDB`/PWA product with an online Laravel app backed by `MySQL`.
- Keep the current teacher workflow shape: searchable student list, student detail, chronological notes, image attachments.
- Add three role experiences: `super_admin`, `teacher`, and `student`, all using `username + password`.
- Start fresh: existing browser-only data is not migrated.

## Implementation Changes
- Authentication and routing:
  - Replace the hash-router shell with server-rendered Laravel pages and session auth.
  - Add `/login` and role home routes, with `/` redirecting guests to login and authenticated users to their role dashboard.
  - Use role middleware/policies for all note, student, teacher, and image actions.
- User/account model:
  - Extend `users` with `username` and `role` (`super_admin|teacher|student`); keep `name` and hashed password.
  - Do not add self-registration, forgot-password, or user-managed password changes in v1.
  - Seed or create the first `super_admin` outside the UI, then let that account create/reset teacher and student accounts.
  - Treat teacher CRUD and student CRUD as filtered `User` management, since student profiles are name-only in v1.
- Notes and images:
  - Add `notes` with `student_id`, `author_id`, `author_name_snapshot`, `author_role_snapshot`, `body`, `note_date`, and timestamps.
  - Add `note_images` with `note_id`, private storage path, original filename, mime type, size, and sort order.
  - Store uploads on a private Laravel disk and serve them through authorized controller routes so student data is not publicly exposed.
  - Keep current note behavior: note-only, image-only, or mixed posts, with up to 6 images per note.
- Role behavior:
  - `super_admin`: teacher CRUD, student CRUD, delete any note, delete any image, and reset any teacher/student password.
  - `teacher`: see all students, open any student detail page, read both teacher and student notes for that student, and create/edit/delete only teacher-authored notes created by that same teacher.
  - `student`: see only their own record, with two tabs `Teacher Notes` and `My Notes`; create/edit/delete only their own notes and images.
- Data lifecycle:
  - Deleting a student removes that student account plus all notes and images about that student.
  - Deleting a teacher does not delete existing notes; keep those notes readable via author snapshots and null out `author_id`.
  - Retire the current `IndexedDB`, service-worker, manifest, and offline-first app flow from the main product.

## Interfaces
- Replace `#/students...` navigation with normal authenticated routes such as teacher student pages and a student notes page with query params like `?tab=teacher|mine`.
- `User` becomes the core account type for admin, teacher, and student roles.
- `Note` becomes the shared content type for both teacher-authored and student-authored entries, with permissions derived from role plus ownership.

## Test Plan
- Feature tests for username login, logout, role redirects, guest blocking, and admin-only password reset.
- CRUD tests for teacher and student account management.
- Authorization tests for forbidden actions: student accessing another student’s data, teacher editing student notes, teacher editing another teacher’s notes, student deleting teacher notes, non-admin moderation actions.
- Note tests for teacher and student flows covering note-only, image-only, mixed notes, edit, delete, and per-image removal.
- Cleanup tests for deleting students, deleting notes, deleting images, and preserving notes when a teacher account is removed.
- UI flow tests for super admin moderation, teacher student list/detail navigation, and student two-tab behavior.

## Assumptions
- Existing browser `IndexedDB` data is discarded and re-entered manually.
- The app is online-only in v1; offline editing/installable PWA behavior is out of scope.
- The teacher UI stays close to the current app's flow, while admin pages can use simpler table/form layouts.
- Student records stay minimal in v1: `name`, `username`, `password`, and role.
