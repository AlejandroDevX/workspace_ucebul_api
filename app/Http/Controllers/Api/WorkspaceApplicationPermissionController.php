<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceApplicationPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationPermission::query()
                ->with(['workspaceApplication', 'applicationModule'])
                ->orderByDesc('workspace_application_permission_id')
                ->get(),
            'Workspace application permissions retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_application_permission_workspace_application_id' => ['required', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_permission_workspace_application_module_id' => ['nullable', 'integer', 'exists:workspace_application_modules,workspace_application_module_id'],
            'workspace_application_permission_name' => ['required', 'string', 'max:100'],
            'workspace_application_permission_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_application_permissions', 'workspace_application_permission_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_permission_workspace_application_id',
                        $request->input('workspace_application_permission_workspace_application_id')
                    )),
            ],
            'workspace_application_permission_description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateModuleBelongsToApplication($validator, $request);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceApplicationPermission->load(['workspaceApplication', 'applicationModule']),
            'Workspace application permission created successfully',
            201
        );
    }

    public function show(string $workspace_application_permission): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationPermission::query()
                ->with(['workspaceApplication', 'applicationModule'])
                ->findOrFail($workspace_application_permission),
            'Workspace application permission retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_application_permission): JsonResponse
    {
        $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->findOrFail($workspace_application_permission);

        $validator = Validator::make($request->all(), [
            'workspace_application_permission_workspace_application_id' => ['required', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_permission_workspace_application_module_id' => ['nullable', 'integer', 'exists:workspace_application_modules,workspace_application_module_id'],
            'workspace_application_permission_name' => ['required', 'string', 'max:100'],
            'workspace_application_permission_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_application_permissions', 'workspace_application_permission_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_permission_workspace_application_id',
                        $request->input('workspace_application_permission_workspace_application_id')
                    ))
                    ->ignore(
                        $workspaceApplicationPermission->workspace_application_permission_id,
                        'workspace_application_permission_id'
                    ),
            ],
            'workspace_application_permission_description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateModuleBelongsToApplication($validator, $request);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationPermission->update($validator->validated());

        return $this->successResponse(
            $workspaceApplicationPermission->fresh()->load(['workspaceApplication', 'applicationModule']),
            'Workspace application permission updated successfully'
        );
    }

    public function destroy(string $workspace_application_permission): JsonResponse
    {
        $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->findOrFail($workspace_application_permission);
        $workspaceApplicationPermission->delete();

        return $this->successResponse(null, 'Workspace application permission deleted successfully');
    }

    private function validateModuleBelongsToApplication($validator, Request $request): void
    {
        $workspaceApplicationModuleId = $request->input(
            'workspace_application_permission_workspace_application_module_id'
        );
        $workspaceApplicationId = $request->input('workspace_application_permission_workspace_application_id');

        if (! $workspaceApplicationModuleId || ! $workspaceApplicationId) {
            return;
        }

        $workspaceApplicationModule = WorkspaceApplicationModule::query()->find($workspaceApplicationModuleId);

        if (
            $workspaceApplicationModule
            && (int) $workspaceApplicationModule->workspace_application_module_workspace_application_id !== (int) $workspaceApplicationId
        ) {
            $validator->errors()->add(
                'workspace_application_permission_workspace_application_module_id',
                'The selected application module does not belong to the selected application.'
            );
        }
    }
}
