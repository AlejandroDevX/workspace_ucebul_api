<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkspaceUserSummaryController extends Controller
{
    /**
     * Datos básicos (nombre y apellido) de usuarios vinculados a una aplicación, para que las
     * aplicaciones muestren autores a partir de su workspace_user_id. Exige que quien consulta
     * tenga acceso activo a esa aplicación y solo devuelve usuarios vinculados a ella.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_slug' => ['required', 'string', 'max:100'],
            'workspace_user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'workspace_user_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $workspaceApplication = WorkspaceApplication::query()
            ->where('workspace_application_slug', $request->input('application_slug'))
            ->first();

        if (! $workspaceApplication) {
            return $this->errorResponse('Workspace application not found', 404);
        }

        if (! $workspaceApplication->workspace_application_is_active) {
            return $this->errorResponse('Workspace application is inactive', 403);
        }

        $applicationId = $workspaceApplication->workspace_application_id;
        $hasActiveAccess = $request->user()->userApplications()
            ->where('workspace_user_application_workspace_application_id', $applicationId)
            ->where('workspace_user_application_is_active', true)
            ->exists();

        if (! $hasActiveAccess) {
            return $this->errorResponse('Workspace user does not have access to this application', 403);
        }

        // Incluye usuarios con acceso inactivo: siguen siendo autores de contenido existente.
        $summaries = WorkspaceUser::query()
            ->whereIn('workspace_user_id', array_map('intval', $request->input('workspace_user_ids')))
            ->whereHas('userApplications', fn ($query) => $query
                ->where('workspace_user_application_workspace_application_id', $applicationId))
            ->orderBy('workspace_user_id')
            ->get(['workspace_user_id', 'workspace_user_name', 'workspace_user_last_name'])
            ->map(fn (WorkspaceUser $workspaceUser): array => [
                'workspace_user_id' => $workspaceUser->workspace_user_id,
                'workspace_user_name' => $workspaceUser->workspace_user_name,
                'workspace_user_last_name' => $workspaceUser->workspace_user_last_name,
            ])
            ->values();

        return $this->successResponse($summaries, 'Workspace user summaries');
    }
}
