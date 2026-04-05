<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceUserApplication extends Model
{
    use HasFactory;

    protected $table = 'workspace_user_applications';

    protected $primaryKey = 'workspace_user_application_id';

    public const CREATED_AT = 'workspace_user_application_created_at';

    public const UPDATED_AT = 'workspace_user_application_updated_at';

    protected $fillable = [
        'workspace_user_application_workspace_user_id',
        'workspace_user_application_workspace_application_id',
        'workspace_user_application_is_active',
    ];

    protected function casts(): array
    {
        return [
            'workspace_user_application_is_active' => 'boolean',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceUser(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceUser::class,
            'workspace_user_application_workspace_user_id',
            'workspace_user_id'
        );
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplication::class,
            'workspace_user_application_workspace_application_id',
            'workspace_application_id'
        );
    }

    public function userApplicationPermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplicationPermission::class,
            'workspace_user_application_permission_wua_id',
            'workspace_user_application_id'
        );
    }
}
