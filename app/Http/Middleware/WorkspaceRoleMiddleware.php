<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$workspaceRoles): Response
    {
        $workspaceUser = $request->user();

        if (! $workspaceUser) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $allowedWorkspaceRoles = collect($workspaceRoles)
            ->flatMap(fn (string $workspaceRole) => explode(',', $workspaceRole))
            ->map(fn (string $workspaceRole) => trim($workspaceRole))
            ->filter()
            ->values();

        if ($allowedWorkspaceRoles->isEmpty()) {
            return $next($request);
        }

        $workspaceUser->loadMissing('userRoles.workspaceRole');

        $workspaceUserRoleIdentifiers = $workspaceUser->userRoles
            ->map(function ($workspaceUserRole) {
                $workspaceRole = $workspaceUserRole->workspaceRole;

                if (! $workspaceRole) {
                    return null;
                }

                return $workspaceRole->workspace_role_slug ?: $workspaceRole->workspace_role_name;
            })
            ->filter()
            ->values();

        $hasAllowedWorkspaceRole = $workspaceUserRoleIdentifiers
            ->intersect($allowedWorkspaceRoles)
            ->isNotEmpty();

        if (! $hasAllowedWorkspaceRole) {
            return new JsonResponse([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
