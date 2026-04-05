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
        Schema::create('workspace_applications', function (Blueprint $table) {
            $table->bigIncrements('workspace_application_id');
            $table->string('workspace_application_name', 150);
            $table->string('workspace_application_slug', 100);
            $table->string('workspace_application_description', 255)->nullable();
            $table->string('workspace_application_logo_url', 500)->nullable();
            $table->string('workspace_application_url', 500);
            $table->boolean('workspace_application_is_active')->default(true);
            $table->timestamp('workspace_application_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_application_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('workspace_application_slug', 'uk_workspace_applications_slug');
            $table->unique('workspace_application_url', 'uk_workspace_applications_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_applications');
    }
};
