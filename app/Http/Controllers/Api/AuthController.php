<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceOtp;
use App\Models\WorkspaceUser;
use App\Models\WorkspaceUserApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_email' => ['required', 'email', 'max:150'],
            'workspace_user_password' => ['required', 'string'],
            'workspace_personal_access_token_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_user_email'])
            ->first();

        if (! $workspaceUser || ! Hash::check($validated['workspace_user_password'], $workspaceUser->workspace_user_password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $tokenName = $validated['workspace_personal_access_token_name'] ?? 'workspace_access_token';
        $token = $workspaceUser->createToken($tokenName);

        return $this->successResponse([
            'workspace_user' => $this->buildAuthenticatedWorkspaceUser($workspaceUser),
            'workspace_access_token' => $token->plainTextToken,
            'workspace_access_token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $workspaceUser = $request->user();

        $workspaceUser?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    public function me(Request $request): JsonResponse
    {
        $workspaceUser = $request->user();

        if (! $request->filled('application_slug') && ! $request->filled('application_id')) {
            return $this->successResponse(
                $this->buildAuthenticatedWorkspaceUser($workspaceUser, true),
                'Authenticated workspace user'
            );
        }

        $workspaceApplication = $this->resolveRequestedApplication($request);

        if (! $workspaceApplication && $request->input('application_slug') !== 'workspace') {
            return $this->errorResponse('Workspace application not found', 404);
        }

        if ($workspaceApplication && ! $workspaceApplication->workspace_application_is_active) {
            return $this->errorResponse('Workspace application is inactive', 403);
        }

        $workspaceUser = $this->buildAuthenticatedWorkspaceUserWithApplicationContext($workspaceUser);

        if ($request->input('application_slug') === 'workspace' || $workspaceApplication?->workspace_application_slug === 'workspace') {
            return $this->successResponse(
                $this->enrichWorkspaceContext($workspaceUser, $workspaceApplication),
                'Authenticated workspace user'
            );
        }

        $workspaceUserApplication = $this->findActiveUserApplication($workspaceUser, $workspaceApplication);

        if (! $workspaceUserApplication) {
            return $this->errorResponse('Workspace user does not have access to this application', 403);
        }

        return $this->successResponse(
            $this->enrichApplicationContext($workspaceUser, $workspaceApplication, $workspaceUserApplication),
            'Authenticated workspace user'
        );
    }

    public function validateToken(Request $request): JsonResponse
    {
        return $this->successResponse([
            'is_valid' => (bool) $request->user(),
            'workspace_user' => $request->user(),
        ], 'Token is valid');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_user_email' => ['required', 'email', 'max:150'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_user_email'])
            ->first();

        if (! $workspaceUser) {
            return $this->errorResponse('Workspace user not found', 404);
        }

        $workspaceOtp = WorkspaceOtp::query()->create([
            'workspace_otp_workspace_user_id' => $workspaceUser->workspace_user_id,
            'workspace_otp_email' => $workspaceUser->workspace_user_email,
            'workspace_otp_code' => (string) random_int(100000, 999999),
            'workspace_otp_purpose' => 'password_reset',
            'workspace_otp_expires_at' => now()->addMinutes(10),
        ]);

        return $this->successResponse([
            'workspace_otp' => $workspaceOtp,
            'workspace_user' => $workspaceUser,
        ], 'OTP generated successfully');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'digits:6'],
            'workspace_otp_purpose' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $workspaceOtp = WorkspaceOtp::query()
            ->where('workspace_otp_email', $validated['workspace_otp_email'])
            ->where('workspace_otp_code', $validated['workspace_otp_code'])
            ->where('workspace_otp_purpose', $validated['workspace_otp_purpose'])
            ->whereNull('workspace_otp_used_at')
            ->where('workspace_otp_expires_at', '>', now())
            ->orderByDesc('workspace_otp_id')
            ->first();

        if (! $workspaceOtp) {
            return $this->errorResponse('OTP is invalid or expired', 422);
        }

        return $this->successResponse([
            'is_valid' => true,
            'workspace_otp' => $workspaceOtp,
        ], 'OTP is valid');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_otp_email' => ['required', 'email', 'max:150'],
            'workspace_otp_code' => ['required', 'digits:6'],
            'workspace_user_password' => ['required', 'string', 'min:8', 'max:255'],
            'workspace_otp_purpose' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $workspaceOtpPurpose = $validated['workspace_otp_purpose'] ?? 'password_reset';

        $workspaceUser = WorkspaceUser::query()
            ->where('workspace_user_email', $validated['workspace_otp_email'])
            ->first();

        if (! $workspaceUser) {
            return $this->errorResponse('Workspace user not found', 404);
        }

        $workspaceOtp = WorkspaceOtp::query()
            ->where('workspace_otp_email', $validated['workspace_otp_email'])
            ->where('workspace_otp_code', $validated['workspace_otp_code'])
            ->where('workspace_otp_purpose', $workspaceOtpPurpose)
            ->whereNull('workspace_otp_used_at')
            ->where('workspace_otp_expires_at', '>', now())
            ->orderByDesc('workspace_otp_id')
            ->first();

        if (! $workspaceOtp) {
            return $this->errorResponse('OTP is invalid or expired', 422);
        }

        DB::transaction(function () use ($workspaceUser, $workspaceOtp, $validated): void {
            $workspaceUser->update([
                'workspace_user_password' => Hash::make($validated['workspace_user_password']),
            ]);

            $workspaceOtp->update([
                'workspace_otp_used_at' => now(),
            ]);

            $workspaceUser->tokens()->delete();
        });

        return $this->successResponse([
            'workspace_user' => $workspaceUser->fresh(),
        ], 'Password reset successfully');
    }

    private function buildAuthenticatedWorkspaceUser(?WorkspaceUser $workspaceUser, bool $includeOtps = false): ?WorkspaceUser
    {
        if (! $workspaceUser) {
            return null;
        }

        $relations = [
            'userRoles.workspaceRole',
            'userApplications.workspaceApplication',
            'userApplications.workspaceApplicationRole.applicationPermissions',
            'userApplications.userApplicationPermissions.workspaceApplicationPermission',
        ];

        if ($includeOtps) {
            $relations[] = 'otps';
        }

        return $workspaceUser->load($relations);
    }

    private function buildAuthenticatedWorkspaceUserWithApplicationContext(?WorkspaceUser $workspaceUser): ?WorkspaceUser
    {
        if (! $workspaceUser) {
            return null;
        }

        return $workspaceUser->load([
            'userRoles.workspaceRole',
            'userApplications.workspaceApplication.applicationModules.applicationPermissions',
            'userApplications.workspaceApplicationRole.applicationPermissions.applicationModule',
            'userApplications.userApplicationPermissions.workspaceApplicationPermission.applicationModule',
            'otps',
        ]);
    }

    private function resolveRequestedApplication(Request $request): ?WorkspaceApplication
    {
        return WorkspaceApplication::query()
            ->with(['applicationModules.applicationPermissions'])
            ->when(
                $request->filled('application_id'),
                fn ($query) => $query->where('workspace_application_id', $request->integer('application_id')),
                fn ($query) => $query->where('workspace_application_slug', $request->input('application_slug'))
            )
            ->first();
    }

    private function enrichWorkspaceContext(
        ?WorkspaceUser $workspaceUser,
        ?WorkspaceApplication $workspaceApplication
    ): ?WorkspaceUser {
        if (! $workspaceUser) {
            return null;
        }

        $workspaceUser->userApplications->each(function (WorkspaceUserApplication $userApplication): void {
            $userApplication->setAttribute(
                'permissions_by_module',
                $this->buildPermissionsByModuleForApplication(
                    $userApplication->workspaceApplication,
                    $this->getEffectivePermissionsForUserApplication($userApplication)
                )
            );
        });

        $workspaceUser->setAttribute(
            'workspace_modules',
            $this->buildWorkspaceModules($workspaceUser, $workspaceApplication)
        );

        return $workspaceUser;
    }

    private function enrichApplicationContext(
        ?WorkspaceUser $workspaceUser,
        WorkspaceApplication $workspaceApplication,
        WorkspaceUserApplication $workspaceUserApplication
    ): ?WorkspaceUser {
        if (! $workspaceUser) {
            return null;
        }

        $workspaceUser->setAttribute('current_application', $workspaceApplication);
        $workspaceUser->setAttribute('current_application_access', $workspaceUserApplication);
        $workspaceUser->setAttribute(
            'current_application_permissions',
            $this->buildPermissionsByModuleForApplication(
                $workspaceApplication,
                $this->getEffectivePermissionsForUserApplication($workspaceUserApplication)
            )
        );

        return $workspaceUser;
    }

    private function findActiveUserApplication(
        WorkspaceUser $workspaceUser,
        WorkspaceApplication $workspaceApplication
    ): ?WorkspaceUserApplication {
        return $workspaceUser->userApplications
            ->first(function (WorkspaceUserApplication $userApplication) use ($workspaceApplication): bool {
                return (int) $userApplication->workspace_user_application_workspace_application_id === (int) $workspaceApplication->workspace_application_id
                    && (bool) $userApplication->workspace_user_application_is_active;
            });
    }

    private function buildWorkspaceModules(
        WorkspaceUser $workspaceUser,
        ?WorkspaceApplication $workspaceApplication
    ): array {
        if (! $workspaceApplication) {
            return $this->emptyPermissionsByModule();
        }

        $workspaceUserApplication = $this->findActiveUserApplication($workspaceUser, $workspaceApplication);

        if ($workspaceUserApplication) {
            return $this->buildPermissionsByModuleForApplication(
                $workspaceApplication,
                $this->getEffectivePermissionsForUserApplication($workspaceUserApplication)
            );
        }

        if ($this->userHasGlobalRole($workspaceUser, ['admin', 'super_admin'])) {
            return $this->buildPermissionsByModuleForApplication(
                $workspaceApplication,
                $workspaceApplication->applicationPermissions
            );
        }

        return $this->emptyPermissionsByModule($workspaceApplication);
    }

    private function getEffectivePermissionsForUserApplication(WorkspaceUserApplication $workspaceUserApplication): Collection
    {
        $workspaceUserApplication->loadMissing([
            'workspaceApplication',
            'workspaceApplicationRole.applicationPermissions.applicationModule',
            'userApplicationPermissions.workspaceApplicationPermission.applicationModule',
        ]);

        $rolePermissions = $workspaceUserApplication->workspaceApplicationRole?->applicationPermissions ?? collect();

        $userPermissions = $workspaceUserApplication->userApplicationPermissions
            ->map(fn ($permission) => $permission->workspaceApplicationPermission)
            ->filter();

        return new Collection(
            $this->deduplicatePermissions($rolePermissions->merge($userPermissions))->values()->all()
        );
    }

    private function buildPermissionsByModuleForApplication(
        ?WorkspaceApplication $workspaceApplication,
        Collection $permissions
    ): array {
        $groups = $this->emptyPermissionsByModule($workspaceApplication);
        $permissions = $this->deduplicatePermissions($permissions);

        $permissions->each(function (WorkspaceApplicationPermission $permission) use (&$groups): void {
            $permission->loadMissing('applicationModule');
            $module = $permission->applicationModule;
            $key = $module?->workspace_application_module_slug ?? 'without_module';

            if (! array_key_exists($key, $groups)) {
                $groups[$key] = [
                    'module_name' => $module?->workspace_application_module_name ?? 'Sin módulo',
                    'module_slug' => $module?->workspace_application_module_slug,
                    'permissions' => [],
                ];
            }

            $groups[$key]['permissions'][] = $permission;
        });

        return $groups;
    }

    private function emptyPermissionsByModule(?WorkspaceApplication $workspaceApplication = null): array
    {
        $groups = [];

        if ($workspaceApplication) {
            $workspaceApplication->loadMissing('applicationModules');

            $workspaceApplication->applicationModules
                ->sortBy([
                    ['workspace_application_module_order', 'asc'],
                    ['workspace_application_module_name', 'asc'],
                ])
                ->each(function ($module) use (&$groups): void {
                    $groups[$module->workspace_application_module_slug] = [
                        'module_name' => $module->workspace_application_module_name,
                        'module_slug' => $module->workspace_application_module_slug,
                        'permissions' => [],
                    ];
                });
        }

        $groups['without_module'] = [
            'module_name' => 'Sin módulo',
            'module_slug' => null,
            'permissions' => [],
        ];

        return $groups;
    }

    private function deduplicatePermissions(Collection $permissions): Collection
    {
        $seenIds = [];
        $seenSlugs = [];

        return $permissions
            ->filter()
            ->filter(function (WorkspaceApplicationPermission $permission) use (&$seenIds, &$seenSlugs): bool {
                $permissionId = $permission->workspace_application_permission_id;
                $permissionSlug = $permission->workspace_application_permission_slug;

                if (
                    ($permissionId && in_array($permissionId, $seenIds, true))
                    || ($permissionSlug && in_array($permissionSlug, $seenSlugs, true))
                ) {
                    return false;
                }

                if ($permissionId) {
                    $seenIds[] = $permissionId;
                }

                if ($permissionSlug) {
                    $seenSlugs[] = $permissionSlug;
                }

                return true;
            })
            ->values();
    }

    private function userHasGlobalRole(WorkspaceUser $workspaceUser, array $roles): bool
    {
        return $workspaceUser->userRoles
            ->contains(function ($userRole) use ($roles): bool {
                return $userRole->workspaceRole
                    && in_array($userRole->workspaceRole->workspace_role_name, $roles, true);
            });
    }
}
