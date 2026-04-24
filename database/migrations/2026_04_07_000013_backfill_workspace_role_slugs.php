<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $workspaceRoles = DB::table('workspace_roles')
            ->whereNull('workspace_role_slug')
            ->orWhere('workspace_role_slug', '')
            ->orderBy('workspace_role_id')
            ->get([
                'workspace_role_id',
                'workspace_role_name',
            ]);

        $usedSlugs = DB::table('workspace_roles')
            ->whereNotNull('workspace_role_slug')
            ->where('workspace_role_slug', '!=', '')
            ->pluck('workspace_role_slug')
            ->all();

        foreach ($workspaceRoles as $workspaceRole) {
            $baseSlug = Str::slug($workspaceRole->workspace_role_name, '_');
            $resolvedSlug = $baseSlug !== '' ? $baseSlug : 'workspace_role_'.$workspaceRole->workspace_role_id;
            $suffix = 1;

            while (in_array($resolvedSlug, $usedSlugs, true)) {
                $resolvedSlug = $baseSlug.'_'.$suffix;
                $suffix++;
            }

            $usedSlugs[] = $resolvedSlug;

            DB::table('workspace_roles')
                ->where('workspace_role_id', $workspaceRole->workspace_role_id)
                ->update([
                    'workspace_role_slug' => $resolvedSlug,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration only backfills missing slugs.
    }
};
