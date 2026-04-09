<?php

namespace Tests\Feature\Shared;

use App\Models\Note;
use App\Models\NoteImage;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('role'));
        $this->assertTrue(Schema::hasTable('user'));
        $this->assertTrue(Schema::hasTable('notes'));
        $this->assertTrue(Schema::hasTable('note_images'));
        $this->assertTrue(Schema::hasColumns('role', ['id_role', 'nama']));
        $this->assertTrue(Schema::hasColumns('user', ['id_user', 'nama', 'password', 'id_role']));
        $this->assertTrue(Schema::hasColumns('notes', [
            'student_id',
            'author_id',
            'author_name_snapshot',
            'author_role_snapshot',
            'body',
            'note_date',
        ]));
        $this->assertTrue(Schema::hasColumns('note_images', [
            'note_id',
            'disk',
            'path',
            'original_filename',
            'mime_type',
            'size_bytes',
            'sort_order',
        ]));

        $studentId = $this->insertUser('Schema Student', 'student');

        $this->assertDatabaseHas('user', [
            'id_user' => $studentId,
            'nama' => 'Schema Student',
            'id_role' => Role::idForName('student'),
        ]);
    }

    public function test_foreign_key_deletion_behavior_matches_the_phase_two_contract(): void
    {
        $studentId = $this->insertUser('Student One', 'student');
        $teacherId = $this->insertUser('Teacher One', 'teacher');

        $note = Note::factory()->create([
            'student_id' => $studentId,
            'author_id' => $teacherId,
            'author_name_snapshot' => 'Teacher One',
            'author_role_snapshot' => 'teacher',
        ]);

        $noteImage = NoteImage::factory()->forNote($note)->create();

        DB::table('user')->where('id_user', $teacherId)->delete();

        $note->refresh();

        $this->assertNull($note->author_id);
        $this->assertSame('Teacher One', $note->author_name_snapshot);
        $this->assertSame('teacher', $note->author_role_snapshot);
        $this->assertDatabaseHas('note_images', ['id' => $noteImage->id]);

        DB::table('user')->where('id_user', $studentId)->delete();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('note_images', ['id' => $noteImage->id]);
    }

    private function insertUser(string $name, string $role): int
    {
        return DB::table('user')->insertGetId([
            'nama' => $name,
            'id_role' => Role::idForName($role),
            'password' => Hash::make('password'),
        ]);
    }
}
