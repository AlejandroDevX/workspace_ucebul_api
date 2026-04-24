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
        Schema::create('workspace_application_role_permissions', function (Blueprint $table) {
            $table->bigIncrements('workspace_application_role_permission_id');
            $table->unsignedBigInteger('workspace_application_role_permission_war_id');
            $table->unsignedBigInteger('workspace_application_role_permission_wap_id');
            $table->timestamp('workspace_application_role_permission_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_application_role_permission_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_application_role_permission_war_id',
                    'workspace_application_role_permission_wap_id',
                ],
                'uk_workspace_application_role_permissions_unique'
            );

            $table->foreign(
                'workspace_application_role_permission_war_id',
                'fk_workspace_application_role_permissions_role_id'
            )
                ->references('workspace_application_role_id')
                ->on('workspace_application_roles')
                ->cascadeOnDelete();

            $table->foreign(
                'workspace_application_role_permission_wap_id',
                'fk_workspace_application_role_permissions_permission_id'
            )
                ->references('workspace_application_permission_id')
                ->on('workspace_application_permissions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_application_role_permissions');
    }
};
