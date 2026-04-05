<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken;

class WorkspacePersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'workspace_personal_access_tokens';

    protected $primaryKey = 'workspace_personal_access_token_id';

    public const CREATED_AT = 'workspace_personal_access_token_created_at';

    public const UPDATED_AT = 'workspace_personal_access_token_updated_at';

    protected $fillable = [
        'workspace_personal_access_token_tokenable_type',
        'workspace_personal_access_token_tokenable_id',
        'workspace_personal_access_token_name',
        'workspace_personal_access_token_token',
        'workspace_personal_access_token_abilities',
        'workspace_personal_access_token_last_used_at',
        'workspace_personal_access_token_expires_at',
    ];

    protected $hidden = [
        'workspace_personal_access_token_token',
        'token',
    ];

    protected $casts = [
        'workspace_personal_access_token_abilities' => 'array',
        'workspace_personal_access_token_last_used_at' => 'datetime',
        'workspace_personal_access_token_expires_at' => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
    ];

    public function tokenable(): MorphTo
    {
        return $this->morphTo(
            'tokenable',
            'workspace_personal_access_token_tokenable_type',
            'workspace_personal_access_token_tokenable_id'
        );
    }

    public static function findToken($token): ?static
    {
        if (strpos($token, '|') === false) {
            return static::query()
                ->where('workspace_personal_access_token_token', hash('sha256', $token))
                ->first();
        }

        [$id, $plainTextToken] = explode('|', $token, 2);

        $instance = static::query()->find($id);

        if (! $instance) {
            return null;
        }

        return hash_equals(
            $instance->workspace_personal_access_token_token,
            hash('sha256', $plainTextToken)
        ) ? $instance : null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->getAttributeValue('workspace_personal_access_token_name');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['workspace_personal_access_token_name'] = $value;
    }

    public function getTokenAttribute(): ?string
    {
        return $this->getAttributeValue('workspace_personal_access_token_token');
    }

    public function setTokenAttribute(?string $value): void
    {
        $this->attributes['workspace_personal_access_token_token'] = $value;
    }

    public function getAbilitiesAttribute(): array
    {
        return $this->getAttributeValue('workspace_personal_access_token_abilities') ?? [];
    }

    public function setAbilitiesAttribute(array|string|null $value): void
    {
        $this->attributes['workspace_personal_access_token_abilities'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

    public function getLastUsedAtAttribute(): mixed
    {
        return $this->getAttributeValue('workspace_personal_access_token_last_used_at');
    }

    public function setLastUsedAtAttribute(mixed $value): void
    {
        $this->attributes['workspace_personal_access_token_last_used_at'] = $value === null
            ? null
            : $this->fromDateTime($value);
    }

    public function getExpiresAtAttribute(): mixed
    {
        return $this->getAttributeValue('workspace_personal_access_token_expires_at');
    }

    public function setExpiresAtAttribute(mixed $value): void
    {
        $this->attributes['workspace_personal_access_token_expires_at'] = $value === null
            ? null
            : $this->fromDateTime($value);
    }

    public function getCreatedAtAttribute(): mixed
    {
        return $this->getAttributeValue(self::CREATED_AT);
    }

    public function getUpdatedAtAttribute(): mixed
    {
        return $this->getAttributeValue(self::UPDATED_AT);
    }
}
