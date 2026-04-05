<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceUserRole extends Model
{
    use HasFactory;

    protected $table = 'workspace_user_roles';

    protected $primaryKey = 'workspace_user_role_id';

    public const CREATED_AT = 'workspace_user_role_created_at';

    public const UPDATED_AT = 'workspace_user_role_updated_at';

    protected $fillable = [
        'workspace_user_role_workspace_user_id',
        'workspace_user_role_workspace_role_id',
    ];

    protected function casts(): array
    {
        return [
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceUser(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceUser::class,
            'workspace_user_role_workspace_user_id',
            'workspace_user_id'
        );
    }

    public function workspaceRole(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceRole::class,
            'workspace_user_role_workspace_role_id',
            'workspace_role_id'
        );
    }
}
