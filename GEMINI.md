# DME Project Overview

DME is a Laravel-based role-based application that serves as the online successor to a legacy browser-only PWA/IndexedDB system. It provides a platform for managing student notes, media, and transactions across three primary roles: Super Admin, Teacher, and Student.

## Core Architecture

- **Framework:** Laravel 13 (PHP 8.3)
- **Frontend:** Server-side rendered Blade templates with Tailwind CSS 4 and Vite.
- **Database:** MySQL with a custom schema (legacy compatibility, e.g., `id_user` as primary key).
- **Authentication:** Standard Laravel session-based authentication using `username` (mapped via roles) and passwords.

## User Roles & Capabilities

- **Super Admin (`super_admin`):**
  - Full management of Teacher and Student accounts.
  - System-wide note moderation (read/delete any note/image).
  - Password reset capabilities for all accounts.
- **Teacher (`guru`):**
  - Search and view all students.
  - Access student detail pages.
  - Create, edit, and delete notes they authored.
  - View all notes (including student-authored ones) for any student.
- **Student (`murid`):**
  - View their own profile and notes.
  - Two-tab interface: "Teacher Notes" (read-only) and "My Notes" (CRUD).
  - Manage their own notes and image attachments.

## Key Data Models

- **User (`app/Models/User.php`):** The core account model using custom `id_user` and Indonesia-based role names (`guru`, `murid`).
- **Note (`app/Models/Note.php`):** Chronological entries linked to a student and an author. Supports up to 6 images. Includes snapshotting for author details (name/role) to ensure data persistence if a teacher is deleted.
- **NoteImage (`app/Models/NoteImage.php`):** Media attachments for notes, stored privately and served through authorized routes.
- **Transaction (`app/Models/Transaction.php`) & DetailTransaction:** A system for tracking payments or participation by student, month, and year.

## Building and Running

### Prerequisites
- PHP 8.3+
- MySQL
- Node.js & npm
- Composer

### Setup Commands
```bash
# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate

# Install and build frontend assets
npm install
npm run build
```

### Development
```bash
# Run local development server (includes Vite, server, and queue)
composer run dev
```

### Testing
```bash
# Run PHPUnit tests
composer test

# Run Playwright E2E tests
npm run test:e2e
```

## Development Conventions

- **Auth & Authorization:** All routes are protected by the `auth` middleware. Role-based access is strictly enforced via Laravel Policies (e.g., `NotePolicy`, `UserPolicy`).
- **Data Integrity:** Teacher deletions must preserve notes (via snapshotting), while Student deletions cascade to all related notes and images.
- **Media Handling:** Images are uploaded to a private disk and served through a controller to ensure they are only accessible to authorized users.
- **UI/UX:** The application uses a "modern" mobile-friendly aesthetic with extensive use of Tailwind CSS, rounded corners (`rounded-3xl`), and backdrop blurs for interactive elements like lightboxes.
