<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceUserController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUser::query()->orderByDesc('workspace_user_id')->get(),
            'Workspace users retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_email' => ['required', 'email', 'max:150', Rule::unique('workspace_users', 'workspace_user_email')],
            'workspace_user_document_number' => ['required', 'string', 'max:50', Rule::unique('workspace_users', 'workspace_user_document_number')],
            'workspace_user_name' => ['required', 'string', 'max:100'],
            'workspace_user_last_name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $validated['workspace_user_password'] = Hash::make(Str::password(16));

        $workspaceUser = WorkspaceUser::query()->create($validated);

        return $this->successResponse($workspaceUser, 'Workspace user created successfully', 201);
    }

    public function show(string $workspace_user): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUser::query()->findOrFail($workspace_user),
            'Workspace user retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_user): JsonResponse
    {
        $workspaceUser = WorkspaceUser::query()->findOrFail($workspace_user);

        $validator = Validator::make($request->all(), [
            'workspace_user_email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('workspace_users', 'workspace_user_email')->ignore(
                    $workspaceUser->workspace_user_id,
                    'workspace_user_id'
                ),
            ],
            'workspace_user_document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('workspace_users', 'workspace_user_document_number')->ignore(
                    $workspaceUser->workspace_user_id,
                    'workspace_user_id'
                ),
            ],
            'workspace_user_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'workspace_user_name' => ['required', 'string', 'max:100'],
            'workspace_user_last_name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if (! empty($validated['workspace_user_password'])) {
            $validated['workspace_user_password'] = Hash::make($validated['workspace_user_password']);
        } else {
            unset($validated['workspace_user_password']);
        }

        $workspaceUser->update($validated);

        return $this->successResponse($workspaceUser->fresh(), 'Workspace user updated successfully');
    }

    public function destroy(string $workspace_user): JsonResponse
    {
        $workspaceUser = WorkspaceUser::query()->findOrFail($workspace_user);
        $workspaceUser->delete();

        return $this->successResponse(null, 'Workspace user deleted successfully');
    }
}
