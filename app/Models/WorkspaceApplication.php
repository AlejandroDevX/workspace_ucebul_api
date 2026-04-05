<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceApplication extends Model
{
    use HasFactory;

    protected $table = 'workspace_applications';

    protected $primaryKey = 'workspace_application_id';

    public const CREATED_AT = 'workspace_application_created_at';

    public const UPDATED_AT = 'workspace_application_updated_at';

    protected $fillable = [
        'workspace_application_name',
        'workspace_application_slug',
        'workspace_application_description',
        'workspace_application_logo_url',
        'workspace_application_url',
        'workspace_application_is_active',
    ];

    protected function casts(): array
    {
        return [
            'workspace_application_is_active' => 'boolean',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function userApplications(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplication::class,
            'workspace_user_application_workspace_application_id',
            'workspace_application_id'
        );
    }

    public function applicationPermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceApplicationPermission::class,
            'workspace_application_permission_workspace_application_id',
            'workspace_application_id'
        );
    }
}
