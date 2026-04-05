<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function userApplicationPermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplicationPermission::class,
            'workspace_user_application_permission_wap_id',
            'workspace_application_permission_id'
        );
    }
}
