<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplicationModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceApplicationModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationModule::query()
                ->with(['workspaceApplication', 'permissions'])
                ->when(
                    $request->filled('workspace_application_id'),
                    fn ($query) => $query->where(
                        'workspace_application_module_workspace_application_id',
                        $request->integer('workspace_application_id')
                    )
                )
                ->when($request->filled('application_slug'), function ($query) use ($request) {
                    $query->whereHas('workspaceApplication', fn ($applicationQuery) => $applicationQuery->where(
                        'workspace_application_slug',
                        $request->string('application_slug')->toString()
                    ));
                })
                ->orderBy('workspace_application_module_order')
                ->orderBy('workspace_application_module_name')
                ->get(),
            'Workspace application modules retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_application_module_workspace_application_id' => ['required', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_module_name' => ['required', 'string', 'max:100'],
            'workspace_application_module_slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('workspace_application_modules', 'workspace_application_module_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_module_workspace_application_id',
                        $request->input('workspace_application_module_workspace_application_id')
                    )),
            ],
            'workspace_application_module_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_module_icon' => ['nullable', 'string', 'max:100'],
            'workspace_application_module_order' => ['nullable', 'integer', 'min:0'],
            'workspace_application_module_is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $validated['workspace_application_module_order'] ??= 0;
        $validated['workspace_application_module_is_active'] ??= true;

        $workspaceApplicationModule = WorkspaceApplicationModule::query()->create($validated);

        return $this->successResponse(
            $workspaceApplicationModule->load(['workspaceApplication', 'permissions']),
            'Workspace application module created successfully',
            201
        );
    }

    public function show(string $workspace_application_module): JsonResponse
    {
        return $this->successResponse(
            WorkspaceApplicationModule::query()
                ->with(['workspaceApplication', 'permissions'])
                ->findOrFail($workspace_application_module),
            'Workspace application module retrieved successfully'
        );
    }

    public function update(Request $request, string $workspace_application_module): JsonResponse
    {
        $workspaceApplicationModule = WorkspaceApplicationModule::query()->findOrFail($workspace_application_module);
        $workspaceApplicationId = $request->input(
            'workspace_application_module_workspace_application_id',
            $workspaceApplicationModule->workspace_application_module_workspace_application_id
        );

        $validator = Validator::make($request->all(), [
            'workspace_application_module_workspace_application_id' => ['sometimes', 'integer', 'exists:workspace_applications,workspace_application_id'],
            'workspace_application_module_name' => ['sometimes', 'string', 'max:100'],
            'workspace_application_module_slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('workspace_application_modules', 'workspace_application_module_slug')
                    ->where(fn ($query) => $query->where(
                        'workspace_application_module_workspace_application_id',
                        $workspaceApplicationId
                    ))
                    ->ignore(
                        $workspaceApplicationModule->workspace_application_module_id,
                        'workspace_application_module_id'
                    ),
            ],
            'workspace_application_module_description' => ['nullable', 'string', 'max:255'],
            'workspace_application_module_icon' => ['nullable', 'string', 'max:100'],
            'workspace_application_module_order' => ['sometimes', 'integer', 'min:0'],
            'workspace_application_module_is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplicationModule->update($validator->validated());

        return $this->successResponse(
            $workspaceApplicationModule->fresh()->load(['workspaceApplication', 'permissions']),
            'Workspace application module updated successfully'
        );
    }

    public function destroy(string $workspace_application_module): JsonResponse
    {
        $workspaceApplicationModule = WorkspaceApplicationModule::query()->findOrFail($workspace_application_module);
        $workspaceApplicationModule->delete();

        return $this->successResponse(null, 'Workspace application module deleted successfully');
    }
}
