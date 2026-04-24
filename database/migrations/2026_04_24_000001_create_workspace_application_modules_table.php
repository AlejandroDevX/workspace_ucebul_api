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
        Schema::create('workspace_application_modules', function (Blueprint $table) {
            $table->bigIncrements('workspace_application_module_id');
            $table->unsignedBigInteger('workspace_application_module_workspace_application_id');
            $table->string('workspace_application_module_name', 100);
            $table->string('workspace_application_module_slug', 100);
            $table->string('workspace_application_module_description', 255)->nullable();
            $table->string('workspace_application_module_icon', 100)->nullable();
            $table->unsignedInteger('workspace_application_module_order')->default(0);
            $table->boolean('workspace_application_module_is_active')->default(true);
            $table->timestamp('workspace_application_module_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_application_module_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                [
                    'workspace_application_module_workspace_application_id',
                    'workspace_application_module_slug',
                ],
                'uk_workspace_application_modules_app_slug'
            );

            $table->foreign(
                'workspace_application_module_workspace_application_id',
                'fk_workspace_application_modules_workspace_application_id'
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
        Schema::dropIfExists('workspace_application_modules');
    }
};
