<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceApplicationRolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

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

    public function permissionsTree(string $workspace_application_role): JsonResponse
    {
        $workspaceApplicationRole = WorkspaceApplicationRole::query()
            ->with('workspaceApplication')
            ->findOrFail($workspace_application_role);

        return $this->successResponse(
            $this->buildPermissionsTree($workspaceApplicationRole),
            'Workspace application role permissions tree retrieved successfully'
        );
    }

    public function updatePermissions(Request $request, string $workspace_application_role): JsonResponse
    {
        $workspaceApplicationRole = WorkspaceApplicationRole::query()
            ->findOrFail($workspace_application_role);

        $validator = Validator::make($request->all(), [
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => [
                'integer',
                'distinct',
                'exists:workspace_application_permissions,workspace_application_permission_id',
            ],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $workspaceApplicationRole): void {
            $permissionIds = collect($request->input('permission_ids', []))
                ->map(fn ($permissionId) => (int) $permissionId)
                ->unique()
                ->values();

            if ($permissionIds->isEmpty()) {
                return;
            }

            $validPermissionsCount = WorkspaceApplicationPermission::query()
                ->whereIn('workspace_application_permission_id', $permissionIds)
                ->where(
                    'workspace_application_permission_workspace_application_id',
                    $workspaceApplicationRole->workspace_application_role_workspace_application_id
                )
                ->count();

            if ($validPermissionsCount !== $permissionIds->count()) {
                $validator->errors()->add(
                    'permission_ids',
                    'All selected permissions must belong to the same application as the selected application role.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $permissionIds = collect($validator->validated()['permission_ids'])
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->values();

        DB::transaction(function () use ($workspaceApplicationRole, $permissionIds): void {
            WorkspaceApplicationRolePermission::query()
                ->where(
                    'workspace_application_role_permission_war_id',
                    $workspaceApplicationRole->workspace_application_role_id
                )
                ->delete();

            if ($permissionIds->isEmpty()) {
                return;
            }

            $now = now();

            WorkspaceApplicationRolePermission::query()->insert(
                $permissionIds
                    ->map(fn (int $permissionId) => [
                        'workspace_application_role_permission_war_id' => $workspaceApplicationRole->workspace_application_role_id,
                        'workspace_application_role_permission_wap_id' => $permissionId,
                        'workspace_application_role_permission_created_at' => $now,
                        'workspace_application_role_permission_updated_at' => $now,
                    ])
                    ->all()
            );
        });

        return $this->successResponse(
            [
                'role' => $this->serializeRole($workspaceApplicationRole->fresh('workspaceApplication')),
                'assigned_permission_ids' => $permissionIds,
            ],
            'Workspace application role permissions updated successfully'
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

    private function buildPermissionsTree(WorkspaceApplicationRole $workspaceApplicationRole): array
    {
        $workspaceApplicationId = $workspaceApplicationRole->workspace_application_role_workspace_application_id;
        $assignedPermissionIds = WorkspaceApplicationRolePermission::query()
            ->where(
                'workspace_application_role_permission_war_id',
                $workspaceApplicationRole->workspace_application_role_id
            )
            ->pluck('workspace_application_role_permission_wap_id')
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->values();
        $assignedPermissionIdSet = $assignedPermissionIds->flip();
        $modules = WorkspaceApplicationModule::query()
            ->where('workspace_application_module_workspace_application_id', $workspaceApplicationId)
            ->with(['applicationPermissions' => fn ($query) => $query
                ->where(
                    'workspace_application_permission_workspace_application_id',
                    $workspaceApplicationId
                )
                ->orderBy('workspace_application_permission_name')
                ->orderBy('workspace_application_permission_id')])
            ->orderBy('workspace_application_module_order')
            ->orderBy('workspace_application_module_name')
            ->get();
        $withoutModulePermissions = WorkspaceApplicationPermission::query()
            ->where('workspace_application_permission_workspace_application_id', $workspaceApplicationId)
            ->whereNull('workspace_application_permission_workspace_application_module_id')
            ->orderBy('workspace_application_permission_name')
            ->orderBy('workspace_application_permission_id')
            ->get();

        return [
            'role' => $this->serializeRole($workspaceApplicationRole),
            'application' => $workspaceApplicationRole->workspaceApplication ? [
                'workspace_application_id' => $workspaceApplicationRole->workspaceApplication->workspace_application_id,
                'workspace_application_name' => $workspaceApplicationRole->workspaceApplication->workspace_application_name,
                'workspace_application_slug' => $workspaceApplicationRole->workspaceApplication->workspace_application_slug,
            ] : null,
            'modules' => $modules
                ->map(fn (WorkspaceApplicationModule $module) => [
                    'workspace_application_module_id' => $module->workspace_application_module_id,
                    'workspace_application_module_name' => $module->workspace_application_module_name,
                    'workspace_application_module_slug' => $module->workspace_application_module_slug,
                    'workspace_application_module_order' => $module->workspace_application_module_order,
                    'permissions' => $module->applicationPermissions
                        ->unique('workspace_application_permission_id')
                        ->map(fn (WorkspaceApplicationPermission $permission) => $this->serializePermissionWithAssignment($permission, $assignedPermissionIdSet))
                        ->values(),
                ])
                ->values(),
            'without_module' => $withoutModulePermissions
                ->unique('workspace_application_permission_id')
                ->map(fn (WorkspaceApplicationPermission $permission) => $this->serializePermissionWithAssignment($permission, $assignedPermissionIdSet))
                ->values(),
        ];
    }

    private function serializeRole(WorkspaceApplicationRole $workspaceApplicationRole): array
    {
        return [
            'workspace_application_role_id' => $workspaceApplicationRole->workspace_application_role_id,
            'workspace_application_role_name' => $workspaceApplicationRole->workspace_application_role_name,
            'workspace_application_role_slug' => $workspaceApplicationRole->workspace_application_role_slug,
            'workspace_application_role_workspace_application_id' => $workspaceApplicationRole->workspace_application_role_workspace_application_id,
        ];
    }

    private function serializePermissionWithAssignment(WorkspaceApplicationPermission $permission, $assignedPermissionIdSet): array
    {
        return [
            'workspace_application_permission_id' => $permission->workspace_application_permission_id,
            'workspace_application_permission_workspace_application_id' => $permission->workspace_application_permission_workspace_application_id,
            'workspace_application_permission_workspace_application_module_id' => $permission->workspace_application_permission_workspace_application_module_id,
            'workspace_application_permission_name' => $permission->workspace_application_permission_name,
            'workspace_application_permission_slug' => $permission->workspace_application_permission_slug,
            'workspace_application_permission_description' => $permission->workspace_application_permission_description,
            'is_assigned' => $assignedPermissionIdSet->has((int) $permission->workspace_application_permission_id),
        ];
    }
}
