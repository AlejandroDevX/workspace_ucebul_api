<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceApplicationModule extends Model
{
    use HasFactory;

    protected $table = 'workspace_application_modules';

    protected $primaryKey = 'workspace_application_module_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const CREATED_AT = 'workspace_application_module_created_at';

    public const UPDATED_AT = 'workspace_application_module_updated_at';

    protected $fillable = [
        'workspace_application_module_workspace_application_id',
        'workspace_application_module_name',
        'workspace_application_module_slug',
        'workspace_application_module_description',
        'workspace_application_module_icon',
        'workspace_application_module_order',
        'workspace_application_module_is_active',
    ];

    protected function casts(): array
    {
        return [
            'workspace_application_module_order' => 'integer',
            'workspace_application_module_is_active' => 'boolean',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceApplication(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceApplication::class,
            'workspace_application_module_workspace_application_id',
            'workspace_application_id'
        );
    }

    public function applicationPermissions(): HasMany
    {
        return $this->hasMany(
            WorkspaceApplicationPermission::class,
            'workspace_application_permission_workspace_application_module_id',
            'workspace_application_module_id'
        );
    }

    public function permissions(): HasMany
    {
        return $this->applicationPermissions();
    }
}
