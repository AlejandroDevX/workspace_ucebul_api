<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class WorkspaceApplicationPermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->query('grouped_by') === 'application_module') {
            return $this->successResponse(
                $this->applicationPermissionsTree(),
                'Workspace application permissions retrieved successfully'
            );
        }

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

    private function validateModuleBelongsToApplication(ValidationValidator $validator, Request $request): void
    {
        $validator->after(function (ValidationValidator $validator) use ($request): void {
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
        });
    }

    private function applicationPermissionsTree()
    {
        return WorkspaceApplication::query()
            ->with([
                'applicationModules' => fn ($query) => $query
                    ->orderBy('workspace_application_module_order')
                    ->orderBy('workspace_application_module_name'),
                'applicationModules.applicationPermissions' => fn ($query) => $query
                    ->orderBy('workspace_application_permission_name')
                    ->orderBy('workspace_application_permission_id'),
                'applicationPermissions' => fn ($query) => $query
                    ->whereNull('workspace_application_permission_workspace_application_module_id')
                    ->orderBy('workspace_application_permission_name')
                    ->orderBy('workspace_application_permission_id'),
            ])
            ->orderBy('workspace_application_name')
            ->get()
            ->map(fn (WorkspaceApplication $application) => [
                'workspace_application_id' => $application->workspace_application_id,
                'workspace_application_name' => $application->workspace_application_name,
                'workspace_application_slug' => $application->workspace_application_slug,
                'modules' => $application->applicationModules
                    ->map(fn (WorkspaceApplicationModule $module) => [
                        'workspace_application_module_id' => $module->workspace_application_module_id,
                        'workspace_application_module_name' => $module->workspace_application_module_name,
                        'workspace_application_module_slug' => $module->workspace_application_module_slug,
                        'workspace_application_module_icon' => $module->workspace_application_module_icon,
                        'workspace_application_module_order' => $module->workspace_application_module_order,
                        'permissions' => $module->applicationPermissions
                            ->unique('workspace_application_permission_id')
                            ->map(fn (WorkspaceApplicationPermission $permission) => $this->serializeTreePermission($permission))
                            ->values(),
                    ])
                    ->values(),
                'without_module' => $application->applicationPermissions
                    ->unique('workspace_application_permission_id')
                    ->map(fn (WorkspaceApplicationPermission $permission) => $this->serializeTreePermission($permission))
                    ->values(),
            ])
            ->values();
    }

    private function serializeTreePermission(WorkspaceApplicationPermission $permission): array
    {
        return [
            'workspace_application_permission_id' => $permission->workspace_application_permission_id,
            'workspace_application_permission_workspace_application_id' => $permission->workspace_application_permission_workspace_application_id,
            'workspace_application_permission_workspace_application_module_id' => $permission->workspace_application_permission_workspace_application_module_id,
            'workspace_application_permission_name' => $permission->workspace_application_permission_name,
            'workspace_application_permission_slug' => $permission->workspace_application_permission_slug,
            'workspace_application_permission_description' => $permission->workspace_application_permission_description,
        ];
    }
}
