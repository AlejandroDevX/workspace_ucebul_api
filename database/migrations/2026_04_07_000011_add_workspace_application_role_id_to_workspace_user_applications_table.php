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
        Schema::table('workspace_user_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_user_application_workspace_application_role_id')
                ->nullable()
                ->after('workspace_user_application_workspace_application_id');

            $table->foreign(
                'workspace_user_application_workspace_application_role_id',
                'fk_workspace_user_applications_workspace_application_role_id'
            )
                ->references('workspace_application_role_id')
                ->on('workspace_application_roles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_user_applications', function (Blueprint $table) {
            $table->dropForeign('fk_workspace_user_applications_workspace_application_role_id');
            $table->dropColumn('workspace_user_application_workspace_application_role_id');
        });
    }
};
