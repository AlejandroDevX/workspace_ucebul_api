<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceApplicationRolePermission extends Model
{
    use HasFactory;

    protected $table = 'workspace_application_role_permissions';

    protected $primaryKey = 'workspace_application_role_permission_id';

    public const CREATED_AT = 'workspace_application_role_permission_created_at';

    public const UPDATED_AT = 'workspace_application_role_permission_updated_at';

    protected $fillable = [
        'workspace_application_role_permission_war_id',
        'workspace_application_role_permission_wap_id',
    ];

    protected function casts(): array
    {
        return [
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceApplicationRole(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplicationRole::class,
            'workspace_application_role_permission_war_id',
            'workspace_application_role_id'
        );
    }

    public function workspaceApplicationPermission(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplicationPermission::class,
            'workspace_application_role_permission_wap_id',
            'workspace_application_permission_id'
        );
    }
}
