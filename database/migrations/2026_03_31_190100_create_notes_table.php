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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
