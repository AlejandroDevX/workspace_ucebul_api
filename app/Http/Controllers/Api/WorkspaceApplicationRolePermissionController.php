<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceApplicationRolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceApplicationRolePermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationRolePermission::query()
                ->with([
                    'workspaceApplicationRole.workspaceApplication',
                    'workspaceApplicationPermission.workspaceApplication',
                    'workspaceApplicationPermission.applicationModule',
                ])
                ->when(
                    $request->filled('workspace_application_role_permission_war_id'),
                    fn ($query) => $query->where(
                        'workspace_application_role_permission_war_id',
                        $request->integer('workspace_application_role_permission_war_id')
                    )
                )
                ->orderByDesc('workspace_application_role_permission_id')
                ->get(),
            'Workspace application role permissions retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_application_role_permission_war_id' => ['required', 'integer', 'exists:workspace_application_roles,workspace_application_role_id'],
            'workspace_application_role_permission_wap_id' => [
                'required',
                'integer',
                'exists:workspace_application_permissions,workspace_application_permission_id',
                Rule::unique(
                    'workspace_application_role_permissions',
                    'workspace_application_role_permission_wap_id'
                )
                    ->where(fn ($query) => $query->where(
                        'workspace_application_role_permission_war_id',
                        $request->input('workspace_application_role_permission_war_id')
                    )),
            ],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceApplicationRoleId = $request->input('workspace_application_role_permission_war_id');
            $workspaceApplicationPermissionId = $request->input('workspace_application_role_permission_wap_id');

            if (! $workspaceApplicationRoleId || ! $workspaceApplicationPermissionId) {
                return;
            }

            $workspaceApplicationRole = WorkspaceApplicationRole::query()->find($workspaceApplicationRoleId);
            $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->find($workspaceApplicationPermissionId);

            if (
                $workspaceApplicationRole
                && $workspaceApplicationPermission
                && (int) $workspaceApplicationRole->workspace_application_role_workspace_application_id !== (int) $workspaceApplicationPermission->workspace_application_permission_workspace_application_id
            ) {
                $validator->errors()->add(
                    'workspace_application_role_permission_wap_id',
                    'The selected permission does not belong to the same application as the selected application role.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationRolePermission = WorkspaceApplicationRolePermission::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceApplicationRolePermission->load([
                'workspaceApplicationRole.workspaceApplication',
                'workspaceApplicationPermission.workspaceApplication',
                'workspaceApplicationPermission.applicationModule',
            ]),
            'Workspace application role permission created successfully',
            201
        );
    }

    public function show(string $workspace_arp): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationRolePermission::query()
                ->with([
                    'workspaceApplicationRole.workspaceApplication',
                    'workspaceApplicationPermission.workspaceApplication',
                    'workspaceApplicationPermission.applicationModule',
                ])
                ->findOrFail($workspace_arp),
            'Workspace application role permission retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_arp): JsonResponse
    {
        $workspaceApplicationRolePermission = WorkspaceApplicationRolePermission::query()
            ->findOrFail($workspace_arp);

        $validator = Validator::make($request->all(), [
            'workspace_application_role_permission_war_id' => ['required', 'integer', 'exists:workspace_application_roles,workspace_application_role_id'],
            'workspace_application_role_permission_wap_id' => [
                'required',
                'integer',
                'exists:workspace_application_permissions,workspace_application_permission_id',
                Rule::unique(
                    'workspace_application_role_permissions',
                    'workspace_application_role_permission_wap_id'
                )
                    ->where(fn ($query) => $query->where(
                        'workspace_application_role_permission_war_id',
                        $request->input('workspace_application_role_permission_war_id')
                    ))
                    ->ignore(
                        $workspaceApplicationRolePermission->workspace_application_role_permission_id,
                        'workspace_application_role_permission_id'
                    ),
            ],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceApplicationRoleId = $request->input('workspace_application_role_permission_war_id');
            $workspaceApplicationPermissionId = $request->input('workspace_application_role_permission_wap_id');

            if (! $workspaceApplicationRoleId || ! $workspaceApplicationPermissionId) {
                return;
            }

            $workspaceApplicationRole = WorkspaceApplicationRole::query()->find($workspaceApplicationRoleId);
            $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->find($workspaceApplicationPermissionId);

            if (
                $workspaceApplicationRole
                && $workspaceApplicationPermission
                && (int) $workspaceApplicationRole->workspace_application_role_workspace_application_id !== (int) $workspaceApplicationPermission->workspace_application_permission_workspace_application_id
            ) {
                $validator->errors()->add(
                    'workspace_application_role_permission_wap_id',
                    'The selected permission does not belong to the same application as the selected application role.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationRolePermission->update($validator->validated());

        return $this->successResponse(
            $workspaceApplicationRolePermission->fresh()->load([
                'workspaceApplicationRole.workspaceApplication',
                'workspaceApplicationPermission.workspaceApplication',
                'workspaceApplicationPermission.applicationModule',
            ]),
            'Workspace application role permission updated successfully'
        );
    }

    public function destroy(string $workspace_arp): JsonResponse
    {
        $workspaceApplicationRolePermission = WorkspaceApplicationRolePermission::query()
            ->findOrFail($workspace_arp);
        $workspaceApplicationRolePermission->delete();

        return $this->successResponse(null, 'Workspace application role permission deleted successfully');
    }
}
