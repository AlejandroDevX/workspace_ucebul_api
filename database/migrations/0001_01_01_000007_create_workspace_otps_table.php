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
        Schema::create('workspace_otps', function (Blueprint $table) {
            $table->bigIncrements('workspace_otp_id');
            $table->unsignedBigInteger('workspace_otp_workspace_user_id')->nullable();
            $table->string('workspace_otp_email', 150);
            $table->string('workspace_otp_code', 10);
            $table->string('workspace_otp_purpose', 50);
            $table->timestamp('workspace_otp_expires_at');
            $table->timestamp('workspace_otp_used_at')->nullable()->default(null);
            $table->timestamp('workspace_otp_created_at')->nullable()->useCurrent();
            $table->timestamp('workspace_otp_updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('workspace_otp_workspace_user_id', 'idx_workspace_otps_workspace_user_id');
            $table->index('workspace_otp_email', 'idx_workspace_otps_email');
            $table->index('workspace_otp_code', 'idx_workspace_otps_code');
            $table->index('workspace_otp_purpose', 'idx_workspace_otps_purpose');

            $table->foreign(
                'workspace_otp_workspace_user_id',
                'fk_workspace_otps_workspace_user_id'
            )
                ->references('workspace_user_id')
                ->on('workspace_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_otps');
    }
};
