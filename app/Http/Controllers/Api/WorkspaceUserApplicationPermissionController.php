<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceUserApplication;
use App\Models\WorkspaceUserApplicationPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceUserApplicationPermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplicationPermission::query()
                ->with([
                    'workspaceUserApplication.workspaceUser',
                    'workspaceUserApplication.workspaceApplication',
                    'workspaceUserApplication.workspaceApplicationRole',
                    'workspaceApplicationPermission.workspaceApplication',
                ])
                ->when(
                    $request->filled('workspace_user_application_permission_wua_id'),
                    fn ($query) => $query->where(
                        'workspace_user_application_permission_wua_id',
                        $request->integer('workspace_user_application_permission_wua_id')
                    )
                )
                ->orderByDesc('workspace_user_application_permission_id')
                ->get(),
            'Workspace user application permissions retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_application_permission_wua_id' => ['required', 'integer', 'exists:workspace_user_applications,workspace_user_application_id'],
            'workspace_user_application_permission_wap_id' => [
                'required',
                'integer',
                'exists:workspace_application_permissions,workspace_application_permission_id',
                Rule::unique('workspace_user_application_permissions', 'workspace_user_application_permission_wap_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_application_permission_wua_id',
                        $request->input('workspace_user_application_permission_wua_id')
                    )),
            ],
            'workspace_user_application_permission_effect' => ['sometimes', 'string', Rule::in(['allow', 'deny'])],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceUserApplicationId = $request->input('workspace_user_application_permission_wua_id');
            $workspaceApplicationPermissionId = $request->input('workspace_user_application_permission_wap_id');

            if (! $workspaceUserApplicationId || ! $workspaceApplicationPermissionId) {
                return;
            }

            $workspaceUserApplication = WorkspaceUserApplication::query()->find($workspaceUserApplicationId);
            $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->find($workspaceApplicationPermissionId);

            if (
                $workspaceUserApplication
                && $workspaceApplicationPermission
                && (int) $workspaceUserApplication->workspace_user_application_workspace_application_id !== (int) $workspaceApplicationPermission->workspace_application_permission_workspace_application_id
            ) {
                $validator->errors()->add(
                    'workspace_user_application_permission_wap_id',
                    'The selected permission does not belong to the same application as the selected user access.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $validated['workspace_user_application_permission_effect'] ??= 'allow';

        $workspaceUserApplicationPermission = WorkspaceUserApplicationPermission::query()->create($validated);

        return $this->successResponse(
            $workspaceUserApplicationPermission->load([
                'workspaceUserApplication.workspaceUser',
                'workspaceUserApplication.workspaceApplication',
                'workspaceUserApplication.workspaceApplicationRole',
                'workspaceApplicationPermission.workspaceApplication',
            ]),
            'Workspace user application permission created successfully',
            201
        );
    }

    public function show(string $workspace_uap): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplicationPermission::query()
                ->with([
                    'workspaceUserApplication.workspaceUser',
                    'workspaceUserApplication.workspaceApplication',
                    'workspaceUserApplication.workspaceApplicationRole',
                    'workspaceApplicationPermission.workspaceApplication',
                ])
                ->findOrFail($workspace_uap),
            'Workspace user application permission retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_uap): JsonResponse
    {
        $workspaceUserApplicationPermission = WorkspaceUserApplicationPermission::query()->findOrFail($workspace_uap);

        $validator = Validator::make($request->all(), [
            'workspace_user_application_permission_wua_id' => ['required', 'integer', 'exists:workspace_user_applications,workspace_user_application_id'],
            'workspace_user_application_permission_wap_id' => [
                'required',
                'integer',
                'exists:workspace_application_permissions,workspace_application_permission_id',
                Rule::unique('workspace_user_application_permissions', 'workspace_user_application_permission_wap_id')
                    ->where(fn ($query) => $query->where(
                        'workspace_user_application_permission_wua_id',
                        $request->input('workspace_user_application_permission_wua_id')
                    ))
                    ->ignore(
                        $workspaceUserApplicationPermission->workspace_user_application_permission_id,
                        'workspace_user_application_permission_id'
                    ),
            ],
            'workspace_user_application_permission_effect' => ['sometimes', 'string', Rule::in(['allow', 'deny'])],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $workspaceUserApplicationId = $request->input('workspace_user_application_permission_wua_id');
            $workspaceApplicationPermissionId = $request->input('workspace_user_application_permission_wap_id');

            if (! $workspaceUserApplicationId || ! $workspaceApplicationPermissionId) {
                return;
            }

            $workspaceUserApplication = WorkspaceUserApplication::query()->find($workspaceUserApplicationId);
            $workspaceApplicationPermission = WorkspaceApplicationPermission::query()->find($workspaceApplicationPermissionId);

            if (
                $workspaceUserApplication
                && $workspaceApplicationPermission
                && (int) $workspaceUserApplication->workspace_user_application_workspace_application_id !== (int) $workspaceApplicationPermission->workspace_application_permission_workspace_application_id
            ) {
                $validator->errors()->add(
                    'workspace_user_application_permission_wap_id',
                    'The selected permission does not belong to the same application as the selected user access.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $validated['workspace_user_application_permission_effect'] ??= 'allow';

        $workspaceUserApplicationPermission->update($validated);

        return $this->successResponse(
            $workspaceUserApplicationPermission->fresh()->load([
                'workspaceUserApplication.workspaceUser',
                'workspaceUserApplication.workspaceApplication',
                'workspaceUserApplication.workspaceApplicationRole',
                'workspaceApplicationPermission.workspaceApplication',
            ]),
            'Workspace user application permission updated successfully'
        );
    }

    public function destroy(string $workspace_uap): JsonResponse
    {
        $workspaceUserApplicationPermission = WorkspaceUserApplicationPermission::query()->findOrFail($workspace_uap);
        $workspaceUserApplicationPermission->delete();

        return $this->successResponse(null, 'Workspace user application permission deleted successfully');
    }
}
