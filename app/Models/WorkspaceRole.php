<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceRole extends Model
{
    use HasFactory;

    protected $table = 'workspace_roles';

    protected $primaryKey = 'workspace_role_id';

    public const CREATED_AT = 'workspace_role_created_at';

    public const UPDATED_AT = 'workspace_role_updated_at';

    protected $fillable = [
        'workspace_role_name',
    ];

    protected function casts(): array
    {
        return [
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserRole::class,
            'workspace_user_role_workspace_role_id',
            'workspace_role_id'
        );
    }

    public function workspaceUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceUser::class,
            'workspace_user_roles',
            'workspace_user_role_workspace_role_id',
            'workspace_user_role_workspace_user_id',
            'workspace_role_id',
            'workspace_user_id'
        );
    }
}
