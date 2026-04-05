<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceOtp extends Model
{
    use HasFactory;

    protected $table = 'workspace_otps';

    protected $primaryKey = 'workspace_otp_id';

    public const CREATED_AT = 'workspace_otp_created_at';

    public const UPDATED_AT = 'workspace_otp_updated_at';

    protected $fillable = [
        'workspace_otp_workspace_user_id',
        'workspace_otp_email',
        'workspace_otp_code',
        'workspace_otp_purpose',
        'workspace_otp_expires_at',
        'workspace_otp_used_at',
    ];

    protected function casts(): array
    {
        return [
            'workspace_otp_expires_at' => 'datetime',
            'workspace_otp_used_at' => 'datetime',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
        ];
    }

    public function workspaceUser(): BelongsTo
    {
        return $this->belongsTo(
            WorkspaceUser::class,
            'workspace_otp_workspace_user_id',
            'workspace_user_id'
        );
    }
}
