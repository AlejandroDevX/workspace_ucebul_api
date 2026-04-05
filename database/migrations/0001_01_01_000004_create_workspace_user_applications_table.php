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
        Schema::create('workspace_user_applications', function (Blueprint $table) {
            $table->bigIncrements('workspace_user_application_id');
            $table->unsignedBigInteger('workspace_user_application_workspace_user_id');
            $table->unsignedBigInteger('workspace_user_application_workspace_application_id');
            $table->boolean('workspace_user_application_is_active')->default(true);
            $table->timestamp('workspace_user_application_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_user_application_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_user_application_workspace_user_id',
                    'workspace_user_application_workspace_application_id',
                ],
                'uk_workspace_user_applications_user_application'
            );

            $table->foreign(
                'workspace_user_application_workspace_user_id',
                'fk_workspace_user_applications_workspace_user_id'
            )
                ->references('workspace_user_id')
                ->on('workspace_users')
                ->cascadeOnDelete();

            $table->foreign(
                'workspace_user_application_workspace_application_id',
                'fk_workspace_user_applications_workspace_application_id'
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
        Schema::dropIfExists('workspace_user_applications');
    }
};
