<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceUserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceUserRoleController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserRole::query()
                ->with(['workspaceUser', 'workspaceRole'])
                ->orderByDesc('workspace_user_role_id')
                ->get(),
            'Workspace user roles retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_role_workspace_user_id' => ['required', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_user_role_workspace_role_id' => [
                'required',
                'integer',
                'exists:workspace_roles,workspace_role_id',
                Rule::unique('workspace_user_roles', 'workspace_user_role_workspace_role_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_role_workspace_user_id',
                        $request->input('workspace_user_role_workspace_user_id')
                    )),
            ],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserRole = WorkspaceUserRole::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceUserRole->load(['workspaceUser', 'workspaceRole']),
            'Workspace user role created successfully',
            201
        );
    }

    public function show(string $workspace_user_role): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserRole::query()
                ->with(['workspaceUser', 'workspaceRole'])
                ->findOrFail($workspace_user_role),
            'Workspace user role retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_user_role): JsonResponse
    {
        $workspaceUserRole = WorkspaceUserRole::query()->findOrFail($workspace_user_role);

        $validator = Validator::make($request->all(), [
            'workspace_user_role_workspace_user_id' => ['required', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_user_role_workspace_role_id' => [
                'required',
                'integer',
                'exists:workspace_roles,workspace_role_id',
                Rule::unique('workspace_user_roles', 'workspace_user_role_workspace_role_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_role_workspace_user_id',
                        $request->input('workspace_user_role_workspace_user_id')
                    ))
                    ->ignore($workspaceUserRole->workspace_user_role_id, 'workspace_user_role_id'),
            ],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserRole->update($validator->validated());

        return $this->successResponse(
            $workspaceUserRole->fresh()->load(['workspaceUser', 'workspaceRole']),
            'Workspace user role updated successfully'
        );
    }

    public function destroy(string $workspace_user_role): JsonResponse
    {
        $workspaceUserRole = WorkspaceUserRole::query()->findOrFail($workspace_user_role);
        $workspaceUserRole->delete();

        return $this->successResponse(null, 'Workspace user role deleted successfully');
    }
}
