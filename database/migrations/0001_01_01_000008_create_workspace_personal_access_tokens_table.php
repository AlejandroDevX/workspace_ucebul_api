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
        Schema::create('workspace_personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('workspace_personal_access_token_id');
            $table->string('workspace_personal_access_token_tokenable_type');
            $table->unsignedBigInteger('workspace_personal_access_token_tokenable_id');
            $table->string('workspace_personal_access_token_name');
            $table->string('workspace_personal_access_token_token', 64);
            $table->text('workspace_personal_access_token_abilities')->nullable();
            $table->timestamp('workspace_personal_access_token_last_used_at')->nullable()->default(null);
            $table->timestamp('workspace_personal_access_token_expires_at')->nullable()->default(null);
            $table->timestamp('workspace_personal_access_token_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_personal_access_token_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                'workspace_personal_access_token_token',
                'uk_workspace_personal_access_tokens_token'
            );

            $table->index(
                [
                    'workspace_personal_access_token_tokenable_type',
                    'workspace_personal_access_token_tokenable_id',
                ],
                'idx_workspace_personal_access_tokens_tokenable'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_personal_access_tokens');
    }
};
