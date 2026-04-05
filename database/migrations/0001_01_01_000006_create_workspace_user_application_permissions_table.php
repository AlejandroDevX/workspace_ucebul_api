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
        Schema::create('workspace_user_application_permissions', function (Blueprint $table) {
            $table->bigIncrements('workspace_user_application_permission_id');
            $table->unsignedBigInteger('workspace_user_application_permission_wua_id');
            $table->unsignedBigInteger('workspace_user_application_permission_wap_id');
            $table->timestamp('workspace_user_application_permission_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_user_application_permission_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_user_application_permission_wua_id',
                    'workspace_user_application_permission_wap_id',
                ],
                'uk_workspace_user_application_permissions_unique'
            );

            $table->foreign(
                'workspace_user_application_permission_wua_id',
                'fk_workspace_uap_permissions_wua_id'
            )
                ->references('workspace_user_application_id')
                ->on('workspace_user_applications')
                ->cascadeOnDelete();

            $table->foreign(
                'workspace_user_application_permission_wap_id',
                'fk_workspace_uap_permissions_wap_id'
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
        Schema::dropIfExists('workspace_user_application_permissions');
    }
};
