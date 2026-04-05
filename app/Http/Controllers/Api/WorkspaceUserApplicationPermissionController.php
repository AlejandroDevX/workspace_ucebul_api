<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceUserApplicationPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceUserApplicationPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplicationPermission::query()
                ->with(['workspaceUserApplication', 'workspaceApplicationPermission'])
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
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserApplicationPermission = WorkspaceUserApplicationPermission::query()->create($validator->validated());

        return $this->successResponse(
            $workspaceUserApplicationPermission->load([
                'workspaceUserApplication',
                'workspaceApplicationPermission',
            ]),
            'Workspace user application permission created successfully',
            201
        );
    }

    public function show(string $workspace_uap): JsonResponse
    {
        return $this->successResponse(
            WorkspaceUserApplicationPermission::query()
                ->with(['workspaceUserApplication', 'workspaceApplicationPermission'])
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
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceUserApplicationPermission->update($validator->validated());

        return $this->successResponse(
            $workspaceUserApplicationPermission->fresh()->load([
                'workspaceUserApplication',
                'workspaceApplicationPermission',
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
