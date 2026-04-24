<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceApplicationPermission extends Model
{
    use HasFactory;

    protected $table = 'workspace_application_permissions';

    protected $primaryKey = 'workspace_application_permission_id';

    public const CREATED_AT = 'workspace_application_permission_created_at';

    public const UPDATED_AT = 'workspace_application_permission_updated_at';

    protected $fillable = [
        'workspace_application_permission_workspace_application_id',
        'workspace_application_permission_workspace_application_module_id',
        'workspace_application_permission_name',
        'workspace_application_permission_slug',
        'workspace_application_permission_description',
    ];

    protected function casts(): array
    {
        return [
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplication::class,
            'workspace_application_permission_workspace_application_id',
            'workspace_application_id'
        );
    }

    public function applicationModule(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplicationModule::class,
            'workspace_application_permission_workspace_application_module_id',
            'workspace_application_module_id'
        );
    }

    public function userApplicationPermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplicationPermission::class,
            'workspace_user_application_permission_wap_id',
            'workspace_application_permission_id'
        );
    }

    public function applicationRolePermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceApplicationRolePermission::class,
            'workspace_application_role_permission_wap_id',
            'workspace_application_permission_id'
        );
    }

    public function applicationRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceApplicationRole::class,
            'workspace_application_role_permissions',
            'workspace_application_role_permission_wap_id',
            'workspace_application_role_permission_war_id',
            'workspace_application_permission_id',
            'workspace_application_role_id'
        );
    }
}
