<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceUserApplicationPermission extends Model
{
    use HasFactory;

    protected $table = 'workspace_user_application_permissions';

    protected $primaryKey = 'workspace_user_application_permission_id';

    public const CREATED_AT = 'workspace_user_application_permission_created_at';

    public const UPDATED_AT = 'workspace_user_application_permission_updated_at';

    protected $fillable = [
        'workspace_user_application_permission_wua_id',
        'workspace_user_application_permission_wap_id',
    ];

    protected function casts(): array
    {
        return [
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceUserApplication(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceUserApplication::class,
            'workspace_user_application_permission_wua_id',
            'workspace_user_application_id'
        );
    }

    public function workspaceApplicationPermission(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplicationPermission::class,
            'workspace_user_application_permission_wap_id',
            'workspace_application_permission_id'
        );
    }
}
