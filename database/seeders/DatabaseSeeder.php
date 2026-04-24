<?php

namespace Database\Seeders;

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceRole;
use App\Models\WorkspaceUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['super_admin', 'admin'] as $workspaceRoleName) {
            WorkspaceRole::query()->updateOrCreate(
                [
                    'workspace_role_name' => $workspaceRoleName,
                ],
                [
                    'workspace_role_name' => $workspaceRoleName,
                    'workspace_role_slug' => Str::slug($workspaceRoleName, '_'),
                    'workspace_role_description' => match ($workspaceRoleName) {
                        'super_admin' => 'Acceso total al workspace.',
                        'admin' => 'Administracion operativa del workspace.',
                        default => null,
                    },
                ]
            );
        }

        WorkspaceUser::query()->updateOrCreate(
            [
                'workspace_user_email' => 'jose.alejandro.sabogal.loaiza@gmail.com',
            ],
            [
                'workspace_user_password' => Hash::make('Salmo91#'),
                'workspace_user_name' => 'Jose Alejandro',
                'workspace_user_last_name' => 'Sabogal Loaiza',
            ]
        );

        $ucebulApplication = WorkspaceApplication::query()->updateOrCreate(
            [
                'workspace_application_slug' => 'ucebul-app',
            ],
            [
                'workspace_application_name' => 'UCEBUL App',
                'workspace_application_description' => 'Aplicacion principal de UCEBUL.',
                'workspace_application_logo_url' => null,
                'workspace_application_url' => 'https://app.ucebul.com',
                'workspace_application_is_active' => true,
            ]
        );

        $modules = [
            'users' => [
                'name' => 'Usuarios',
                'description' => 'Gestion de usuarios',
                'icon' => 'users',
                'order' => 1,
                'permissions' => [
                    'users.view' => 'Ver usuarios',
                    'users.create' => 'Registrar usuarios',
                    'users.update' => 'Editar usuarios',
                    'users.delete' => 'Eliminar usuarios',
                ],
            ],
            'producers' => [
                'name' => 'Productores',
                'description' => 'Gestion de productores',
                'icon' => 'sprout',
                'order' => 2,
                'permissions' => [
                    'producers.view' => 'Ver productores',
                    'producers.create' => 'Registrar productores',
                    'producers.update' => 'Editar productores',
                    'producers.delete' => 'Eliminar productores',
                ],
            ],
            'activities' => [
                'name' => 'Actividades',
                'description' => 'Gestion de actividades',
                'icon' => 'calendar-check',
                'order' => 3,
                'permissions' => [
                    'activities.view' => 'Ver actividades',
                    'activities.create' => 'Registrar actividades',
                    'activities.approve' => 'Aprobar actividades',
                    'activities.reject' => 'Rechazar actividades',
                ],
            ],
            'visits' => [
                'name' => 'Visitas',
                'description' => 'Gestion de visitas',
                'icon' => 'map-pin',
                'order' => 4,
                'permissions' => [
                    'visits.view' => 'Ver visitas',
                    'visits.create' => 'Registrar visitas',
                    'visits.update' => 'Editar visitas',
                    'visits.cancel' => 'Cancelar visitas',
                ],
            ],
            'reports' => [
                'name' => 'Reportes',
                'description' => 'Reportes de la aplicacion',
                'icon' => 'bar-chart',
                'order' => 5,
                'permissions' => [
                    'reports.view' => 'Ver reportes',
                    'reports.export' => 'Exportar reportes',
                ],
            ],
        ];

        foreach ($modules as $moduleSlug => $moduleData) {
            $workspaceApplicationModule = WorkspaceApplicationModule::query()->updateOrCreate(
                [
                    'workspace_application_module_workspace_application_id' => $ucebulApplication->workspace_application_id,
                    'workspace_application_module_slug' => $moduleSlug,
                ],
                [
                    'workspace_application_module_name' => $moduleData['name'],
                    'workspace_application_module_description' => $moduleData['description'],
                    'workspace_application_module_icon' => $moduleData['icon'],
                    'workspace_application_module_order' => $moduleData['order'],
                    'workspace_application_module_is_active' => true,
                ]
            );

            foreach ($moduleData['permissions'] as $permissionSlug => $permissionName) {
                WorkspaceApplicationPermission::query()->updateOrCreate(
                    [
                        'workspace_application_permission_workspace_application_id' => $ucebulApplication->workspace_application_id,
                        'workspace_application_permission_slug' => $permissionSlug,
                    ],
                    [
                        'workspace_application_permission_workspace_application_module_id' => $workspaceApplicationModule->workspace_application_module_id,
                        'workspace_application_permission_name' => $permissionName,
                        'workspace_application_permission_description' => $permissionName,
                    ]
                );
            }
        }
    }
}
