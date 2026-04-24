<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspace_roles', function (Blueprint $table) {
            $table->string('workspace_role_slug', 100)->nullable()->after('workspace_role_name');
            $table->string('workspace_role_description', 255)->nullable()->after('workspace_role_slug');
        });

        $existingRoles = DB::table('workspace_roles')
            ->orderBy('workspace_role_id')
            ->get([
                'workspace_role_id',
                'workspace_role_name',
            ]);

        $usedSlugs = [];

        foreach ($existingRoles as $existingRole) {
            $baseSlug = Str::slug($existingRole->workspace_role_name, '_');
            $resolvedSlug = $baseSlug !== '' ? $baseSlug : 'workspace_role_'.$existingRole->workspace_role_id;
            $suffix = 1;

            while (in_array($resolvedSlug, $usedSlugs, true)) {
                $resolvedSlug = $baseSlug.'_'.$suffix;
                $suffix++;
            }

            $usedSlugs[] = $resolvedSlug;

            DB::table('workspace_roles')
                ->where('workspace_role_id', $existingRole->workspace_role_id)
                ->update([
                    'workspace_role_slug' => $resolvedSlug,
                ]);
        }

        Schema::table('workspace_roles', function (Blueprint $table) {
            $table->unique('workspace_role_slug', 'uk_workspace_roles_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_roles', function (Blueprint $table) {
            $table->dropUnique('uk_workspace_roles_slug');
            $table->dropColumn([
                'workspace_role_slug',
                'workspace_role_description',
            ]);
        });
    }
};
