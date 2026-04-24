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
        Schema::table('workspace_users', function (Blueprint $table) {
            $table->string('workspace_user_document_number', 50)
                ->nullable()
                ->after('workspace_user_email');

            $table->unique(
                'workspace_user_document_number',
                'uk_workspace_users_document_number'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_users', function (Blueprint $table) {
            $table->dropUnique('uk_workspace_users_document_number');
            $table->dropColumn('workspace_user_document_number');
        });
    }
};
