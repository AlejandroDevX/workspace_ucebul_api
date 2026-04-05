<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceOtp;
use App\Models\WorkspaceUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_email' => ['required', 'email', 'max:150'],
            'workspace_user_password' => ['required', 'string'],
            'workspace_personal_access_token_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_user_email'])
            ->first();

        if (! $workspaceUser || ! Hash::check($validated['workspace_user_password'], $workspaceUser->workspace_user_password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $tokenName = $validated['workspace_personal_access_token_name'] ?? 'workspace_access_token';
        $token = $workspaceUser->createToken($tokenName);

        return $this->successResponse([
            'workspace_user' => $workspaceUser->load([
                'userRoles.workspaceRole',
                'userApplications.workspaceApplication',
            ]),
            'workspace_access_token' => $token->plainTextToken,
            'workspace_access_token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $workspaceUser = $request->user();

        $workspaceUser?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    public function me(Request $request): JsonResponse
    {
        $workspaceUser = $request->user();

        return $this->successResponse(
            $workspaceUser?->load([
                'userRoles.workspaceRole',
                'userApplications.workspaceApplication',
                'otps',
            ]),
            'Authenticated workspace user'
        );
    }

    public function validateToken(Request $request): JsonResponse
    {
        return $this->successResponse([
            'is_valid' => (bool) $request->user(),
            'workspace_user' => $request->user(),
        ], 'Token is valid');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_email' => ['required', 'email', 'max:150'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_user_email'])
            ->first();

        if (! $workspaceUser) {
            return $this->errorResponse('Workspace user not found', 404);
        }

        $workspaceOtp = WorkspaceOtp::query()->create([
            'workspace_otp_workspace_user_id' => $workspaceUser->workspace_user_id,
            'workspace_otp_email' => $workspaceUser->workspace_user_email,
            'workspace_otp_code' => (string) random_int(100000, 999999),
            'workspace_otp_purpose' => 'password_reset',
            'workspace_otp_expires_at' => now()->addMinutes(10),
        ]);

        return $this->successResponse([
            'workspace_otp' => $workspaceOtp,
            'workspace_user' => $workspaceUser,
        ], 'OTP generated successfully');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'digits:6'],
            'workspace_otp_purpose' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceOtp = WorkspaceOtp::query()
            ->where('workspace_otp_email', $validated['workspace_otp_email'])
            ->where('workspace_otp_code', $validated['workspace_otp_code'])
            ->where('workspace_otp_purpose', $validated['workspace_otp_purpose'])
            ->whereNull('workspace_otp_used_at')
            ->where('workspace_otp_expires_at', '>', now())
            ->orderByDesc('workspace_otp_id')
            ->first();

        if (! $workspaceOtp) {
            return $this->errorResponse('OTP is invalid or expired', 422);
        }

        return $this->successResponse([
            'is_valid' => true,
            'workspace_otp' => $workspaceOtp,
        ], 'OTP is valid');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'digits:6'],
            'workspace_user_password' => ['required', 'string', 'min:8', 'max:255'],
            'workspace_otp_purpose' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $workspaceOtpPurpose = $validated['workspace_otp_purpose'] ?? 'password_reset';

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_otp_email'])
            ->first();

        if (! $workspaceUser) {
            return $this->errorResponse('Workspace user not found', 404);
        }

        $workspaceOtp = WorkspaceOtp::query()
            ->where('workspace_otp_email', $validated['workspace_otp_email'])
            ->where('workspace_otp_code', $validated['workspace_otp_code'])
            ->where('workspace_otp_purpose', $workspaceOtpPurpose)
            ->whereNull('workspace_otp_used_at')
            ->where('workspace_otp_expires_at', '>', now())
            ->orderByDesc('workspace_otp_id')
            ->first();

        if (! $workspaceOtp) {
            return $this->errorResponse('OTP is invalid or expired', 422);
        }

        DB::transaction(function () use ($workspaceUser, $workspaceOtp, $validated): void {
            $workspaceUser->update([
                'workspace_user_password' => Hash::make($validated['workspace_user_password']),
            ]);

            $workspaceOtp->update([
                'workspace_otp_used_at' => now(),
            ]);

            $workspaceUser->tokens()->delete();
        });

        return $this->successResponse([
            'workspace_user' => $workspaceUser->fresh(),
        ], 'Password reset successfully');
    }
}
