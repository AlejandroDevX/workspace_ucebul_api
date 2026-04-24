<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceUserApplicationPermission;
use App\Models\WorkspaceUserApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class WorkspaceUserApplicationController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplication::query()
                ->with([
                    'workspaceUser',
                    'workspaceApplication',
                    'workspaceApplicationRole.applicationPermissions.applicationModule',
                    'userApplicationPermissions.workspaceApplicationPermission.applicationModule',
                ])
                ->orderByDesc('workspace_user_application_id')
                ->get(),
            'Workspace user applications retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_application_workspace_user_id' => ['required', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_user_application_workspace_application_id' => [
                'required',
                'integer',
                'exists:workspace_applications,workspace_application_id',
                Rule::unique('workspace_user_applications', 'workspace_user_application_workspace_application_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_application_workspace_user_id',
                        $request->input('workspace_user_application_workspace_user_id')
                    )),
            ],
            'workspace_user_application_workspace_application_role_id' => [
                'nullable',
                'integer',
                'exists:workspace_application_roles,workspace_application_role_id',
            ],
            'workspace_user_application_is_active' => ['required', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceApplicationRoleId = $request->input('workspace_user_application_workspace_application_role_id');
            $workspaceApplicationId = $request->input('workspace_user_application_workspace_application_id');

            if (! $workspaceApplicationRoleId || ! $workspaceApplicationId) {
                return;
            }

            $workspaceApplicationRole = WorkspaceApplicationRole::query()->find($workspaceApplicationRoleId);

            if (
                $workspaceApplicationRole
                && (int) $workspaceApplicationRole->workspace_application_role_workspace_application_id !== (int) $workspaceApplicationId
            ) {
                $validator->errors()->add(
                    'workspace_user_application_workspace_application_role_id',
                    'The selected application role does not belong to the selected application.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserApplication = WorkspaceUserApplication::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceUserApplication->load([
                'workspaceUser',
                'workspaceApplication',
                'workspaceApplicationRole.applicationPermissions.applicationModule',
                'userApplicationPermissions.workspaceApplicationPermission.applicationModule',
            ]),
            'Workspace user application created successfully',
            201
        );
    }

    public function show(string $workspace_user_application): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplication::query()
                ->with([
                    'workspaceUser',
                    'workspaceApplication',
                    'workspaceApplicationRole.applicationPermissions.applicationModule',
                    'userApplicationPermissions.workspaceApplicationPermission.applicationModule',
                ])
                ->findOrFail($workspace_user_application),
            'Workspace user application retrieved successfully'
        );
    }

    public function effectivePermissionsTree(string $workspace_user_application): JsonResponse
    {
        $workspaceUserApplication = WorkspaceUserApplication::query()
            ->with([
                'workspaceUser',
                'workspaceApplication',
                'workspaceApplicationRole',
            ])
            ->findOrFail($workspace_user_application);

        return $this->successResponse(
            $this->buildEffectivePermissionsTree($workspaceUserApplication),
            'Workspace user effective permissions tree retrieved successfully'
        );
    }

    public function updatePermissionOverrides(Request $request, string $workspace_user_application): JsonResponse
    {
        $workspaceUserApplication = WorkspaceUserApplication::query()
            ->with('workspaceApplicationRole.applicationPermissions')
            ->findOrFail($workspace_user_application);

        $validator = Validator::make($request->all(), [
            'allowed_permission_ids' => ['sometimes', 'array'],
            'allowed_permission_ids.*' => ['integer', 'distinct', 'exists:workspace_application_permissions,workspace_application_permission_id'],
            'denied_permission_ids' => ['sometimes', 'array'],
            'denied_permission_ids.*' => ['integer', 'distinct', 'exists:workspace_application_permissions,workspace_application_permission_id'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $workspaceUserApplication): void {
            $allowedPermissionIds = collect($request->input('allowed_permission_ids', []))
                ->map(fn ($permissionId) => (int) $permissionId)
                ->unique()
                ->values();
            $deniedPermissionIds = collect($request->input('denied_permission_ids', []))
                ->map(fn ($permissionId) => (int) $permissionId)
                ->unique()
                ->values();
            $overlap = $allowedPermissionIds->intersect($deniedPermissionIds);

            if ($overlap->isNotEmpty()) {
                $validator->errors()->add(
                    'permission_ids',
                    'A permission cannot be allowed and denied at the same time.'
                );
            }

            $permissionIds = $allowedPermissionIds->merge($deniedPermissionIds)->unique()->values();

            if ($permissionIds->isEmpty()) {
                return;
            }

            $validPermissionsCount = WorkspaceApplicationPermission::query()
                ->whereIn('workspace_application_permission_id', $permissionIds)
                ->where(
                    'workspace_application_permission_workspace_application_id',
                    $workspaceUserApplication->workspace_user_application_workspace_application_id
                )
                ->count();

            if ($validPermissionsCount !== $permissionIds->count()) {
                $validator->errors()->add(
                    'permission_ids',
                    'All selected permissions must belong to the same application as the selected user access.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $rolePermissionIds = $this->getRolePermissionIds($workspaceUserApplication);
        $rolePermissionIdSet = $rolePermissionIds->flip();
        $allowedPermissionIds = collect($request->input('allowed_permission_ids', []))
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->reject(fn (int $permissionId) => $rolePermissionIdSet->has($permissionId))
            ->values();
        $deniedPermissionIds = collect($request->input('denied_permission_ids', []))
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->filter(fn (int $permissionId) => $rolePermissionIdSet->has($permissionId))
            ->values();

        DB::transaction(function () use ($workspaceUserApplication, $allowedPermissionIds, $deniedPermissionIds): void {
            WorkspaceUserApplicationPermission::query()
                ->where(
                    'workspace_user_application_permission_wua_id',
                    $workspaceUserApplication->workspace_user_application_id
                )
                ->delete();

            $now = now();
            $rows = $allowedPermissionIds
                ->map(fn (int $permissionId) => [
                    'workspace_user_application_permission_wua_id' => $workspaceUserApplication->workspace_user_application_id,
                    'workspace_user_application_permission_wap_id' => $permissionId,
                    'workspace_user_application_permission_effect' => 'allow',
                    'workspace_user_application_permission_created_at' => $now,
                    'workspace_user_application_permission_updated_at' => $now,
                ])
                ->merge($deniedPermissionIds->map(fn (int $permissionId) => [
                    'workspace_user_application_permission_wua_id' => $workspaceUserApplication->workspace_user_application_id,
                    'workspace_user_application_permission_wap_id' => $permissionId,
                    'workspace_user_application_permission_effect' => 'deny',
                    'workspace_user_application_permission_created_at' => $now,
                    'workspace_user_application_permission_updated_at' => $now,
                ]))
                ->values()
                ->all();

            if ($rows) {
                WorkspaceUserApplicationPermission::query()->insert($rows);
            }
        });

        return $this->successResponse(
            [
                'allowed_permission_ids' => $allowedPermissionIds,
                'denied_permission_ids' => $deniedPermissionIds,
            ],
            'Workspace user permission overrides updated successfully'
        );
    }

    public function update(Request $request, string $workspace_user_application): JsonResponse
    {
        $workspaceUserApplication = WorkspaceUserApplication::query()->findOrFail($workspace_user_application);

        $validator = Validator::make($request->all(), [
            'workspace_user_application_workspace_user_id' => ['required', 'integer', 'exists:workspace_users,workspace_user_id'],
            'workspace_user_application_workspace_application_id' => [
                'required',
                'integer',
                'exists:workspace_applications,workspace_application_id',
                Rule::unique('workspace_user_applications', 'workspace_user_application_workspace_application_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_application_workspace_user_id',
                        $request->input('workspace_user_application_workspace_user_id')
                    ))
                    ->ignore(
                        $workspaceUserApplication->workspace_user_application_id,
                        'workspace_user_application_id'
                    ),
            ],
            'workspace_user_application_workspace_application_role_id' => [
                'nullable',
                'integer',
                'exists:workspace_application_roles,workspace_application_role_id',
            ],
            'workspace_user_application_is_active' => ['required', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceApplicationRoleId = $request->input('workspace_user_application_workspace_application_role_id');
            $workspaceApplicationId = $request->input('workspace_user_application_workspace_application_id');

            if (! $workspaceApplicationRoleId || ! $workspaceApplicationId) {
                return;
            }

            $workspaceApplicationRole = WorkspaceApplicationRole::query()->find($workspaceApplicationRoleId);

            if (
                $workspaceApplicationRole
                && (int) $workspaceApplicationRole->workspace_application_role_workspace_application_id !== (int) $workspaceApplicationId
            ) {
                $validator->errors()->add(
                    'workspace_user_application_workspace_application_role_id',
                    'The selected application role does not belong to the selected application.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserApplication->update($validator->validated());

        return $this->successResponse(
            $workspaceUserApplication->fresh()->load([
                'workspaceUser',
                'workspaceApplication',
                'workspaceApplicationRole.applicationPermissions.applicationModule',
                'userApplicationPermissions.workspaceApplicationPermission.applicationModule',
            ]),
            'Workspace user application updated successfully'
        );
    }

    public function destroy(string $workspace_user_application): JsonResponse
    {
        $workspaceUserApplication = WorkspaceUserApplication::query()->findOrFail($workspace_user_application);
        $workspaceUserApplication->delete();

        return $this->successResponse(null, 'Workspace user application deleted successfully');
    }

    private function buildEffectivePermissionsTree(WorkspaceUserApplication $workspaceUserApplication): array
    {
        $workspaceApplicationId = $workspaceUserApplication->workspace_user_application_workspace_application_id;
        $rolePermissionIds = $this->getRolePermissionIds($workspaceUserApplication);
        $rolePermissionIdSet = $rolePermissionIds->flip();
        $overrides = WorkspaceUserApplicationPermission::query()
            ->where('workspace_user_application_permission_wua_id', $workspaceUserApplication->workspace_user_application_id)
            ->get()
            ->keyBy(fn (WorkspaceUserApplicationPermission $permission) => (int) $permission->workspace_user_application_permission_wap_id);
        $directAllowPermissionIds = $overrides
            ->filter(fn (WorkspaceUserApplicationPermission $permission) => $permission->workspace_user_application_permission_effect === 'allow')
            ->keys()
            ->map(fn ($permissionId) => (int) $permissionId)
            ->values();
        $directDenyPermissionIds = $overrides
            ->filter(fn (WorkspaceUserApplicationPermission $permission) => $permission->workspace_user_application_permission_effect === 'deny')
            ->keys()
            ->map(fn ($permissionId) => (int) $permissionId)
            ->values();
        $directAllowPermissionIdSet = $directAllowPermissionIds->flip();
        $directDenyPermissionIdSet = $directDenyPermissionIds->flip();
        $modules = WorkspaceApplicationModule::query()
            ->where('workspace_application_module_workspace_application_id', $workspaceApplicationId)
            ->with(['applicationPermissions' => fn ($query) => $query
                ->where('workspace_application_permission_workspace_application_id', $workspaceApplicationId)
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

        $serializePermission = fn (WorkspaceApplicationPermission $permission) => $this->serializeEffectivePermission(
            $permission,
            $rolePermissionIdSet,
            $directAllowPermissionIdSet,
            $directDenyPermissionIdSet
        );

        $effectivePermissionCount = $rolePermissionIds
            ->merge($directAllowPermissionIds)
            ->unique()
            ->reject(fn (int $permissionId) => $directDenyPermissionIdSet->has($permissionId))
            ->count();

        return [
            'access' => $this->serializeAccess($workspaceUserApplication),
            'summary' => [
                'role_permission_count' => $rolePermissionIds->count(),
                'direct_allow_count' => $directAllowPermissionIds->count(),
                'direct_deny_count' => $directDenyPermissionIds->count(),
                'effective_permission_count' => $effectivePermissionCount,
            ],
            'modules' => $modules
                ->map(fn (WorkspaceApplicationModule $module) => [
                    'workspace_application_module_id' => $module->workspace_application_module_id,
                    'workspace_application_module_name' => $module->workspace_application_module_name,
                    'workspace_application_module_slug' => $module->workspace_application_module_slug,
                    'workspace_application_module_order' => $module->workspace_application_module_order,
                    'permissions' => $module->applicationPermissions
                        ->unique('workspace_application_permission_id')
                        ->map($serializePermission)
                        ->values(),
                ])
                ->values(),
            'without_module' => $withoutModulePermissions
                ->unique('workspace_application_permission_id')
                ->map($serializePermission)
                ->values(),
        ];
    }

    private function getRolePermissionIds(WorkspaceUserApplication $workspaceUserApplication)
    {
        $workspaceUserApplication->loadMissing('workspaceApplicationRole.applicationPermissions');

        if (! $workspaceUserApplication->workspaceApplicationRole) {
            return collect();
        }

        return $workspaceUserApplication->workspaceApplicationRole->applicationPermissions
            ->pluck('workspace_application_permission_id')
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->values();
    }

    private function serializeAccess(WorkspaceUserApplication $workspaceUserApplication): array
    {
        $workspaceUserApplication->loadMissing(['workspaceUser', 'workspaceApplication', 'workspaceApplicationRole']);

        return [
            'workspace_user_application_id' => $workspaceUserApplication->workspace_user_application_id,
            'workspace_user_application_is_active' => $workspaceUserApplication->workspace_user_application_is_active,
            'workspace_user' => $workspaceUserApplication->workspaceUser ? [
                'workspace_user_id' => $workspaceUserApplication->workspaceUser->workspace_user_id,
                'workspace_user_name' => $workspaceUserApplication->workspaceUser->workspace_user_name,
                'workspace_user_last_name' => $workspaceUserApplication->workspaceUser->workspace_user_last_name,
                'workspace_user_email' => $workspaceUserApplication->workspaceUser->workspace_user_email,
            ] : null,
            'workspace_application' => $workspaceUserApplication->workspaceApplication ? [
                'workspace_application_id' => $workspaceUserApplication->workspaceApplication->workspace_application_id,
                'workspace_application_name' => $workspaceUserApplication->workspaceApplication->workspace_application_name,
                'workspace_application_slug' => $workspaceUserApplication->workspaceApplication->workspace_application_slug,
            ] : null,
            'workspace_application_role' => $workspaceUserApplication->workspaceApplicationRole ? [
                'workspace_application_role_id' => $workspaceUserApplication->workspaceApplicationRole->workspace_application_role_id,
                'workspace_application_role_name' => $workspaceUserApplication->workspaceApplicationRole->workspace_application_role_name,
                'workspace_application_role_slug' => $workspaceUserApplication->workspaceApplicationRole->workspace_application_role_slug,
            ] : null,
        ];
    }

    private function serializeEffectivePermission(
        WorkspaceApplicationPermission $permission,
        $rolePermissionIdSet,
        $directAllowPermissionIdSet,
        $directDenyPermissionIdSet
    ): array {
        $permissionId = (int) $permission->workspace_application_permission_id;
        $isFromRole = $rolePermissionIdSet->has($permissionId);
        $isDirectAllow = $directAllowPermissionIdSet->has($permissionId);
        $isDirectDeny = $directDenyPermissionIdSet->has($permissionId);

        return [
            'workspace_application_permission_id' => $permission->workspace_application_permission_id,
            'workspace_application_permission_workspace_application_id' => $permission->workspace_application_permission_workspace_application_id,
            'workspace_application_permission_workspace_application_module_id' => $permission->workspace_application_permission_workspace_application_module_id,
            'workspace_application_permission_name' => $permission->workspace_application_permission_name,
            'workspace_application_permission_slug' => $permission->workspace_application_permission_slug,
            'workspace_application_permission_description' => $permission->workspace_application_permission_description,
            'is_from_role' => $isFromRole,
            'is_direct_allow' => $isDirectAllow,
            'is_direct_deny' => $isDirectDeny,
            'is_effectively_allowed' => ($isFromRole || $isDirectAllow) && ! $isDirectDeny,
        ];
    }
}
