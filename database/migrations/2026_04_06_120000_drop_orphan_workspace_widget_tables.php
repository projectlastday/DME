<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('widget_reminders');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('workspaces');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // These tables belong to a different project and are intentionally not recreated here.
    }
};
