<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceApplicationRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationRole::query()
                ->with(['workspaceApplication', 'applicationPermissions.applicationModule'])
                ->when(
                    $request->filled('workspace_application_role_workspace_application_id'),
                    fn ($query) => $query->where(
                        'workspace_application_role_workspace_application_id',
                        $request->integer('workspace_application_role_workspace_application_id')
                    )
                )
                ->orderByDesc('workspace_application_role_id')
                ->get(),
            'Workspace application roles retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_application_role_workspace_application_id' => ['required', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_role_name' => ['required', 'string', 'max:100'],
            'workspace_application_role_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_application_roles', 'workspace_application_role_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_role_workspace_application_id',
                        $request->input('workspace_application_role_workspace_application_id')
                    )),
            ],
            'workspace_application_role_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_role_is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationRole = WorkspaceApplicationRole::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceApplicationRole->load(['workspaceApplication', 'applicationPermissions.applicationModule']),
            'Workspace application role created successfully',
            201
        );
    }

    public function show(string $workspace_application_role): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationRole::query()
                ->with(['workspaceApplication', 'applicationPermissions.applicationModule'])
                ->findOrFail($workspace_application_role),
            'Workspace application role retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_application_role): JsonResponse
    {
        $workspaceApplicationRole = WorkspaceApplicationRole::query()->findOrFail($workspace_application_role);

        $validator = Validator::make($request->all(), [
            'workspace_application_role_workspace_application_id' => ['required', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_role_name' => ['required', 'string', 'max:100'],
            'workspace_application_role_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_application_roles', 'workspace_application_role_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_role_workspace_application_id',
                        $request->input('workspace_application_role_workspace_application_id')
                    ))
                    ->ignore(
                        $workspaceApplicationRole->workspace_application_role_id,
                        'workspace_application_role_id'
                    ),
            ],
            'workspace_application_role_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_role_is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationRole->update($validator->validated());

        return $this->successResponse(
            $workspaceApplicationRole->fresh()->load(['workspaceApplication', 'applicationPermissions.applicationModule']),
            'Workspace application role updated successfully'
        );
    }

    public function destroy(string $workspace_application_role): JsonResponse
    {
        $workspaceApplicationRole = WorkspaceApplicationRole::query()->findOrFail($workspace_application_role);
        $workspaceApplicationRole->delete();

        return $this->successResponse(null, 'Workspace application role deleted successfully');
    }
}
