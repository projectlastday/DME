<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRoleTable();
        $this->ensureUserTable();
        $this->seedRoles();

        if (! Schema::hasTable('users')) {
            return;
        }

        $this->migrateLegacyUsers();
        $this->rebuildNotesTable();

        Schema::dropIfExists('users');
    }

    public function down(): void
    {
        if (Schema::hasTable('users') || ! Schema::hasTable('user')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('role')->index();
            $table->string('password');
            $table->timestamps();
        });

        $roles = DB::table('role')->pluck('id_role', 'nama');

        DB::table('user')
            ->orderBy('id_user')
            ->get()
            ->each(function (object $user) use ($roles): void {
                $roleName = $roles->flip()->get((int) $user->id_role, User::ROLE_STUDENT);

                DB::table('users')->insert([
                    'id' => (int) $user->id_user,
                    'name' => (string) $user->nama,
                    'username' => null,
                    'role' => $roleName,
                    'password' => (string) $user->password,
                ]);
            });
    }

    private function ensureRoleTable(): void
    {
        if (Schema::hasTable('role')) {
            return;
        }

        Schema::create('role', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('nama')->unique();
        });
    }

    private function ensureUserTable(): void
    {
        if (Schema::hasTable('user')) {
            return;
        }

        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('nama');
            $table->string('password');
            $table->foreignId('id_role')->constrained('role', 'id_role')->restrictOnDelete();
        });
    }

    private function seedRoles(): void
    {
        DB::table('role')->upsert([
            ['id_role' => 1, 'nama' => User::ROLE_SUPER_ADMIN],
            ['id_role' => 2, 'nama' => User::ROLE_TEACHER],
            ['id_role' => 3, 'nama' => User::ROLE_STUDENT],
        ], ['id_role'], ['nama']);
    }

    private function migrateLegacyUsers(): void
    {
        $roleIds = DB::table('role')->pluck('id_role', 'nama');

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function (object $legacyUser) use ($roleIds): void {
                $roleId = $roleIds->get((string) $legacyUser->role, $roleIds->get(User::ROLE_STUDENT));

                DB::table('user')->updateOrInsert(
                    ['id_user' => (int) $legacyUser->id],
                    [
                        'nama' => (string) $legacyUser->name,
                        'password' => (string) $legacyUser->password,
                        'id_role' => (int) $roleId,
                    ]
                );
            });
    }

    private function rebuildNotesTable(): void
    {
        if (! Schema::hasTable('notes') && ! Schema::hasTable('notes_legacy_auth_refactor')) {
            return;
        }

        $sourceTable = Schema::hasTable('notes_legacy_auth_refactor')
            ? 'notes_legacy_auth_refactor'
            : 'notes';

        $rows = DB::table($sourceTable)
            ->select([
                'id',
                'student_id',
                'author_id',
                'author_name_snapshot',
                'author_role_snapshot',
                'body',
                'note_date',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('notes');
        Schema::dropIfExists('notes_legacy_auth_refactor');

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('user', 'id_user')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('user', 'id_user')->nullOnDelete();
            $table->string('author_name_snapshot');
            $table->string('author_role_snapshot');
            $table->text('body')->nullable();
            $table->date('note_date');
            $table->timestamps();

            $table->index('student_id');
            $table->index('author_id');
            $table->index(['student_id', 'note_date']);
            $table->index(['student_id', 'created_at']);
        });

        if ($rows !== []) {
            DB::table('notes')->insert($rows);
        }

        Schema::enableForeignKeyConstraints();
    }
};
