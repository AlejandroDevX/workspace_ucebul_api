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
        Schema::table('workspace_application_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_application_permission_workspace_application_module_id')
                ->nullable()
                ->after('workspace_application_permission_workspace_application_id');

            $table->index(
                'workspace_application_permission_workspace_application_module_id',
                'idx_workspace_application_permissions_module_id'
            );

            $table->foreign(
                'workspace_application_permission_workspace_application_module_id',
                'fk_workspace_application_permissions_module_id'
            )
                ->references('workspace_application_module_id')
                ->on('workspace_application_modules')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_application_permissions', function (Blueprint $table) {
            $table->dropForeign('fk_workspace_application_permissions_module_id');
            $table->dropIndex('idx_workspace_application_permissions_module_id');
            $table->dropColumn('workspace_application_permission_workspace_application_module_id');
        });
    }
};
