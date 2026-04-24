<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_user_application_permissions', function (Blueprint $table) {
            $table->string('workspace_user_application_permission_effect', 20)
                ->default('allow')
                ->after('workspace_user_application_permission_wap_id');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_user_application_permissions', function (Blueprint $table) {
            $table->dropColumn('workspace_user_application_permission_effect');
        });
    }
};
