<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceRoleController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceRole::query()->orderByDesc('workspace_role_id')->get(),
            'Workspace roles retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_role_name' => ['required', 'string', 'max:100', Rule::unique('workspace_roles', 'workspace_role_name')],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceRole = WorkspaceRole::query()->create($validator->validated());

        return $this->successResponse($workspaceRole, 'Workspace role created successfully', 201);
    }

    public function show(string $workspace_role): JsonResponse
    {
        return $this->successResponse(
            WorkspaceRole::query()->findOrFail($workspace_role),
            'Workspace role retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_role): JsonResponse
    {
        $workspaceRole = WorkspaceRole::query()->findOrFail($workspace_role);

        $validator = Validator::make($request->all(), [
            'workspace_role_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_roles', 'workspace_role_name')->ignore(
                    $workspaceRole->workspace_role_id,
                    'workspace_role_id'
                ),
            ],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceRole->update($validator->validated());

        return $this->successResponse($workspaceRole->fresh(), 'Workspace role updated successfully');
    }

    public function destroy(string $workspace_role): JsonResponse
    {
        $workspaceRole = WorkspaceRole::query()->findOrFail($workspace_role);
        $workspaceRole->delete();

        return $this->successResponse(null, 'Workspace role deleted successfully');
    }
}
