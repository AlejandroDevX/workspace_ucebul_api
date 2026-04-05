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
        Schema::create('workspace_application_permissions', function (Blueprint $table) {
            $table->bigIncrements('workspace_application_permission_id');
            $table->unsignedBigInteger('workspace_application_permission_workspace_application_id');
            $table->string('workspace_application_permission_name', 100);
            $table->string('workspace_application_permission_slug', 100);
            $table->string('workspace_application_permission_description', 255)->nullable();
            $table->timestamp('workspace_application_permission_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_application_permission_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_application_permission_workspace_application_id',
                    'workspace_application_permission_slug',
                ],
                'uk_workspace_application_permissions_app_slug'
            );

            $table->foreign(
                'workspace_application_permission_workspace_application_id',
                'fk_workspace_application_permissions_workspace_application_id'
            )
                ->references('workspace_application_id')
                ->on('workspace_applications')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_application_permissions');
    }
};
