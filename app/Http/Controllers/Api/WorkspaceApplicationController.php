<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceApplicationController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplication::query()->orderByDesc('workspace_application_id')->get(),
            'Workspace applications retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_application_name' => ['required', 'string', 'max:150'],
            'workspace_application_slug' => ['required', 'string', 'max:100', Rule::unique('workspace_applications', 'workspace_application_slug')],
            'workspace_application_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_logo_url' => ['nullable', 'url', 'max:500'],
            'workspace_application_url' => ['required', 'url', 'max:500', Rule::unique('workspace_applications', 'workspace_application_url')],
            'workspace_application_is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplication = WorkspaceApplication::query()->create($validator->validated());

        return $this->successResponse($workspaceApplication, 'Workspace application created successfully', 201);
    }

    public function show(Request $request, string $workspace_application): JsonResponse
    {
        $query = WorkspaceApplication::query();

        if ($request->boolean('include_modules')) {
            $query->with(['applicationModules.applicationPermissions']);
        }

        return $this->successResponse(
            $query->findOrFail($workspace_application),
            'Workspace application retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_application): JsonResponse
    {
        $workspaceApplication = WorkspaceApplication::query()->findOrFail($workspace_application);

        $validator = Validator::make($request->all(), [
            'workspace_application_name' => ['required', 'string', 'max:150'],
            'workspace_application_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_applications', 'workspace_application_slug')->ignore(
                    $workspaceApplication->workspace_application_id,
                    'workspace_application_id'
                ),
            ],
            'workspace_application_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_logo_url' => ['nullable', 'url', 'max:500'],
            'workspace_application_url' => [
                'required',
                'url',
                'max:500',
                Rule::unique('workspace_applications', 'workspace_application_url')->ignore(
                    $workspaceApplication->workspace_application_id,
                    'workspace_application_id'
                ),
            ],
            'workspace_application_is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplication->update($validator->validated());

        return $this->successResponse($workspaceApplication->fresh(), 'Workspace application updated successfully');
    }

    public function destroy(string $workspace_application): JsonResponse
    {
        $workspaceApplication = WorkspaceApplication::query()->findOrFail($workspace_application);
        $workspaceApplication->delete();

        return $this->successResponse(null, 'Workspace application deleted successfully');
    }
}
