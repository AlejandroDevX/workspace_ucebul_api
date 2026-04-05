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
        Schema::create('workspace_user_roles', function (Blueprint $table) {
            $table->bigIncrements('workspace_user_role_id');
            $table->unsignedBigInteger('workspace_user_role_workspace_user_id');
            $table->unsignedBigInteger('workspace_user_role_workspace_role_id');
            $table->timestamp('workspace_user_role_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_user_role_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_user_role_workspace_user_id',
                    'workspace_user_role_workspace_role_id',
                ],
                'uk_workspace_user_roles_user_role'
            );

            $table->foreign(
                'workspace_user_role_workspace_user_id',
                'fk_workspace_user_roles_workspace_user_id'
            )
                ->references('workspace_user_id')
                ->on('workspace_users')
                ->cascadeOnDelete();

            $table->foreign(
                'workspace_user_role_workspace_role_id',
                'fk_workspace_user_roles_workspace_role_id'
            )
                ->references('workspace_role_id')
                ->on('workspace_roles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_user_roles');
    }
};
