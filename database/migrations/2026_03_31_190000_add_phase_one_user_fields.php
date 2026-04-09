<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('username');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! $this->hasIndex('users', 'users_username_unique')) {
                $table->unique('username');
            }

            if (! $this->hasIndex('users', 'users_role_index')) {
                $table->index('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
            }

            if ($this->hasIndex('users', 'users_role_index')) {
                $table->dropIndex('users_role_index');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $details) => ($details['name'] ?? null) === $index);
    }
};
