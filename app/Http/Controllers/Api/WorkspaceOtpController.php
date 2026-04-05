<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkspaceOtpController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceOtp::query()->orderByDesc('workspace_otp_id')->get(),
            'Workspace OTPs retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_otp_workspace_user_id' => ['nullable', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'string', 'max:10'],
            'workspace_otp_purpose' => ['required', 'string', 'max:50'],
            'workspace_otp_expires_at' => ['required', 'date'],
            'workspace_otp_used_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceOtp = WorkspaceOtp::query()->create($validator->validated());

        return $this->successResponse($workspaceOtp, 'Workspace OTP created successfully', 201);
    }

    public function show(string $workspace_otp): JsonResponse
    {
        return $this->successResponse(
            WorkspaceOtp::query()->findOrFail($workspace_otp),
            'Workspace OTP retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_otp): JsonResponse
    {
        $workspaceOtp = WorkspaceOtp::query()->findOrFail($workspace_otp);

        $validator = Validator::make($request->all(), [
            'workspace_otp_workspace_user_id' => ['nullable', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'string', 'max:10'],
            'workspace_otp_purpose' => ['required', 'string', 'max:50'],
            'workspace_otp_expires_at' => ['required', 'date'],
            'workspace_otp_used_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceOtp->update($validator->validated());

        return $this->successResponse($workspaceOtp->fresh(), 'Workspace OTP updated successfully');
    }

    public function destroy(string $workspace_otp): JsonResponse
    {
        $workspaceOtp = WorkspaceOtp::query()->findOrFail($workspace_otp);
        $workspaceOtp->delete();

        return $this->successResponse(null, 'Workspace OTP deleted successfully');
    }
}
