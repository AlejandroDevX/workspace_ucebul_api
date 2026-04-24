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
        Schema::create('workspace_application_roles', function (Blueprint $table) {
            $table->bigIncrements('workspace_application_role_id');
            $table->unsignedBigInteger('workspace_application_role_workspace_application_id');
            $table->string('workspace_application_role_name', 100);
            $table->string('workspace_application_role_slug', 100);
            $table->string('workspace_application_role_description', 255)->nullable();
            $table->boolean('workspace_application_role_is_active')->default(true);
            $table->timestamp('workspace_application_role_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_application_role_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_application_role_workspace_application_id',
                    'workspace_application_role_slug',
                ],
                'uk_workspace_application_roles_app_slug'
            );

            $table->foreign(
                'workspace_application_role_workspace_application_id',
                'fk_workspace_application_roles_workspace_application_id'
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
        Schema::dropIfExists('workspace_application_roles');
    }
};
