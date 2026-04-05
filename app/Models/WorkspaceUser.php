<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class WorkspaceUser extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'workspace_users';

    protected $primaryKey = 'workspace_user_id';

    public const CREATED_AT = 'workspace_user_created_at';

    public const UPDATED_AT = 'workspace_user_updated_at';

    protected $fillable = [
        'workspace_user_email',
        'workspace_user_password',
        'workspace_user_name',
        'workspace_user_last_name',
    ];

    protected $hidden = [
        'workspace_user_password',
    ];

    protected function casts(): array
    {
        return [
            'workspace_user_password' => 'hashed',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->workspace_user_password;
    }

    public function createToken(
        string $name,
        array $abilities = ['*'],
        ?DateTimeInterface $expiresAt = null
    ): NewAccessToken {
        $plainTextToken = $this->generateTokenString();

        $token = $this->tokens()->create([
            'workspace_personal_access_token_name' => $name,
            'workspace_personal_access_token_token' => hash('sha256', $plainTextToken),
            'workspace_personal_access_token_abilities' => $abilities,
            'workspace_personal_access_token_expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    public function tokens(): MorphMany
    {
        return $this->morphMany(
            WorkspacePersonalAccessToken::class,
            'tokenable',
            'workspace_personal_access_token_tokenable_type',
            'workspace_personal_access_token_tokenable_id'
        );
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserRole::class,
            'workspace_user_role_workspace_user_id',
            'workspace_user_id'
        );
    }

    public function workspaceRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceRole::class,
            'workspace_user_roles',
            'workspace_user_role_workspace_user_id',
            'workspace_user_role_workspace_role_id',
            'workspace_user_id',
            'workspace_role_id'
        );
    }

    public function userApplications(): HasMany
    {
        return $this->hasMany(
            WorkspaceUserApplication::class,
            'workspace_user_application_workspace_user_id',
            'workspace_user_id'
        );
    }

    public function otps(): HasMany
    {
        return $this->hasMany(
            WorkspaceOtp::class,
            'workspace_otp_workspace_user_id',
            'workspace_user_id'
        );
    }
}
