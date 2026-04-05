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
        Schema::create('workspace_roles', function (Blueprint $table) {
            $table->bigIncrements('workspace_role_id');
            $table->string('workspace_role_name', 100);
            $table->timestamp('workspace_role_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_role_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('workspace_role_name', 'uk_workspace_roles_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_roles');
    }
};
