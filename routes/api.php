<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceApplicationController;
use App\Http\Controllers\Api\WorkspaceApplicationModuleController;
use App\Http\Controllers\Api\WorkspaceApplicationPermissionController;
use App\Http\Controllers\Api\WorkspaceApplicationRoleController;
use App\Http\Controllers\Api\WorkspaceApplicationRolePermissionController;
use App\Http\Controllers\Api\WorkspaceOtpController;
use App\Http\Controllers\Api\WorkspaceRoleController;
use App\Http\Controllers\Api\WorkspaceUserApplicationController;
use App\Http\Controllers\Api\WorkspaceUserApplicationPermissionController;
use App\Http\Controllers\Api\WorkspaceUserController;
use App\Http\Controllers\Api\WorkspaceUserRoleController;
use App\Http\Controllers\Api\WorkspaceUserSummaryController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('validate-token', [AuthController::class, 'validateToken']);
    });
});

Route::middleware('auth:sanctum')->get('workspace-user-summaries', [WorkspaceUserSummaryController::class, 'index'])
    ->name('workspace-user-summaries.index');

Route::middleware(['auth:sanctum', 'workspace.role:admin,super_admin'])->group(function (): void {
    Route::apiResource('workspace-users', WorkspaceUserController::class)->except(['destroy']);
    Route::get('workspace-roles', [WorkspaceRoleController::class, 'index']);
    Route::get('workspace-roles/{workspace_role}', [WorkspaceRoleController::class, 'show']);
    Route::get('workspace-applications', [WorkspaceApplicationController::class, 'index']);
    Route::get('workspace-applications/{workspace_application}', [WorkspaceApplicationController::class, 'show']);
    Route::apiResource('workspace-user-roles', WorkspaceUserRoleController::class)->except(['destroy']);
    Route::get(
        'workspace-user-applications/{workspace_user_application}/effective-permissions-tree',
        [WorkspaceUserApplicationController::class, 'effectivePermissionsTree']
    )->name('workspace-user-applications.effective-permissions-tree');
    Route::put(
        'workspace-user-applications/{workspace_user_application}/permission-overrides',
        [WorkspaceUserApplicationController::class, 'updatePermissionOverrides']
    )->name('workspace-user-applications.permission-overrides.update');
    Route::apiResource('workspace-user-applications', WorkspaceUserApplicationController::class)->except(['destroy']);
    Route::apiResource('workspace-application-modules', WorkspaceApplicationModuleController::class)->except(['destroy']);
    Route::apiResource('workspace-application-permissions', WorkspaceApplicationPermissionController::class)->except(['destroy']);
    Route::get(
        'workspace-application-roles/{workspace_application_role}/permissions-tree',
        [WorkspaceApplicationRoleController::class, 'permissionsTree']
    )->name('workspace-application-roles.permissions-tree');
    Route::put(
        'workspace-application-roles/{workspace_application_role}/permissions',
        [WorkspaceApplicationRoleController::class, 'updatePermissions']
    )->name('workspace-application-roles.permissions.update');
    Route::apiResource('workspace-application-roles', WorkspaceApplicationRoleController::class)->except(['destroy']);
    Route::apiResource('workspace-application-role-permissions', WorkspaceApplicationRolePermissionController::class)
        ->parameters(['workspace-application-role-permissions' => 'workspace_arp'])
        ->except(['destroy']);
    Route::apiResource('workspace-user-application-permissions', WorkspaceUserApplicationPermissionController::class)
        ->parameters(['workspace-user-application-permissions' => 'workspace_uap'])
        ->except(['destroy']);
});

Route::middleware(['auth:sanctum', 'workspace.role:super_admin'])->group(function (): void {
    Route::delete('workspace-users/{workspace_user}', [WorkspaceUserController::class, 'destroy'])
        ->name('workspace-users.destroy');
    Route::delete('workspace-user-roles/{workspace_user_role}', [WorkspaceUserRoleController::class, 'destroy'])
        ->name('workspace-user-roles.destroy');
    Route::delete('workspace-user-applications/{workspace_user_application}', [WorkspaceUserApplicationController::class, 'destroy'])
        ->name('workspace-user-applications.destroy');
    Route::delete(
        'workspace-application-roles/{workspace_application_role}',
        [WorkspaceApplicationRoleController::class, 'destroy']
    )->name('workspace-application-roles.destroy');
    Route::delete(
        'workspace-application-role-permissions/{workspace_arp}',
        [WorkspaceApplicationRolePermissionController::class, 'destroy']
    )->name('workspace-application-role-permissions.destroy');
    Route::delete(
        'workspace-application-modules/{workspace_application_module}',
        [WorkspaceApplicationModuleController::class, 'destroy']
    )->name('workspace-application-modules.destroy');
    Route::delete(
        'workspace-application-permissions/{workspace_application_permission}',
        [WorkspaceApplicationPermissionController::class, 'destroy']
    )->name('workspace-application-permissions.destroy');
    Route::delete(
        'workspace-user-application-permissions/{workspace_uap}',
        [WorkspaceUserApplicationPermissionController::class, 'destroy']
    )->name('workspace-user-application-permissions.destroy');

    Route::post('workspace-roles', [WorkspaceRoleController::class, 'store'])->name('workspace-roles.store');
    Route::put('workspace-roles/{workspace_role}', [WorkspaceRoleController::class, 'update'])->name('workspace-roles.update');
    Route::patch('workspace-roles/{workspace_role}', [WorkspaceRoleController::class, 'update']);
    Route::delete('workspace-roles/{workspace_role}', [WorkspaceRoleController::class, 'destroy'])->name('workspace-roles.destroy');
    Route::post('workspace-applications', [WorkspaceApplicationController::class, 'store'])->name('workspace-applications.store');
    Route::put('workspace-applications/{workspace_application}', [WorkspaceApplicationController::class, 'update'])->name('workspace-applications.update');
    Route::patch('workspace-applications/{workspace_application}', [WorkspaceApplicationController::class, 'update']);
    Route::delete('workspace-applications/{workspace_application}', [WorkspaceApplicationController::class, 'destroy'])->name('workspace-applications.destroy');
    Route::apiResource('workspace-otps', WorkspaceOtpController::class)->only(['index', 'show', 'destroy']);
});
