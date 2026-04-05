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
        Schema::create('workspace_users', function (Blueprint $table) {
            $table->bigIncrements('workspace_user_id');
            $table->string('workspace_user_email', 150);
            $table->string('workspace_user_password', 255);
            $table->string('workspace_user_name', 100);
            $table->string('workspace_user_last_name', 100);
            $table->timestamp('workspace_user_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_user_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('workspace_user_email', 'uk_workspace_users_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_users');
    }
};
