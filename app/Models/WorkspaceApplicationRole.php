<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceApplicationRole extends Model
{
    use HasFactory;

    protected $table = 'workspace_application_roles';

    protected $primaryKey = 'workspace_application_role_id';

    public const CREATED_AT = 'workspace_application_role_created_at';

    public const UPDATED_AT = 'workspace_application_role_updated_at';

    protected $fillable = [
        'workspace_application_role_workspace_application_id',
        'workspace_application_role_name',
        'workspace_application_role_slug',
        'workspace_application_role_description',
        'workspace_application_role_is_active',
    ];

    protected function casts(): array
    {
        return [
            'workspace_application_role_is_active' => 'boolean',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplication::class,
            'workspace_application_role_workspace_application_id',
            'workspace_application_id'
        );
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceApplicationRolePermission::class,
            'workspace_application_role_permission_war_id',
            'workspace_application_role_id'
        );
    }

    public function applicationPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceApplicationPermission::class,
            'workspace_application_role_permissions',
            'workspace_application_role_permission_war_id',
            'workspace_application_role_permission_wap_id',
            'workspace_application_role_id',
            'workspace_application_permission_id'
        );
    }

    public function userApplications(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplication::class,
            'workspace_user_application_workspace_application_role_id',
            'workspace_application_role_id'
        );
    }
}
