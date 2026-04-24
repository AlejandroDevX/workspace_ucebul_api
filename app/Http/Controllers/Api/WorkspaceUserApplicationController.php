<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceUserApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
}
