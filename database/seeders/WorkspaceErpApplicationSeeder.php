<?php

namespace Database\Seeders;

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceApplicationRolePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkspaceErpApplicationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the ERP application with its modules, permissions, roles, and role-permission assignments.
     * Safe to run multiple times — uses updateOrCreate throughout.
     */
    public function run(): void
    {
        $application = $this->seedApplication();

        $permissionIds = $this->seedModulesAndPermissions($application->workspace_application_id);

        $this->seedRolesAndPermissions($application->workspace_application_id, $permissionIds);
    }

    private function seedApplication(): WorkspaceApplication
    {
        return WorkspaceApplication::query()->updateOrCreate(
            [
                'workspace_application_slug' => 'ucebul-erp',
            ],
            [
                'workspace_application_name' => 'UCEBUL ERP',
                'workspace_application_description' => 'Sistema ERP para gestión de contratos, otrosíes, documentos, aprobaciones y procesos administrativos.',
                'workspace_application_logo_url' => null,
                'workspace_application_url' => 'http://localhost:5174',
                'workspace_application_is_active' => true,
            ]
        );
    }

    /**
     * @return array<string, int> Permission slug → permission ID map
     */
    private function seedModulesAndPermissions(int $applicationId): array
    {
        $permissionIds = [];

        foreach ($this->modules() as $moduleSlug => $moduleData) {
            $module = WorkspaceApplicationModule::query()->updateOrCreate(
                [
                    'workspace_application_module_workspace_application_id' => $applicationId,
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

            foreach ($moduleData['permissions'] as $permissionSlug => $permissionData) {
                $permission = WorkspaceApplicationPermission::query()->updateOrCreate(
                    [
                        'workspace_application_permission_workspace_application_id' => $applicationId,
                        'workspace_application_permission_slug' => $permissionSlug,
                    ],
                    [
                        'workspace_application_permission_workspace_application_module_id' => $module->workspace_application_module_id,
                        'workspace_application_permission_name' => $permissionData['name'],
                        'workspace_application_permission_description' => $permissionData['description'],
                    ]
                );

                $permissionIds[$permissionSlug] = $permission->workspace_application_permission_id;
            }
        }

        return $permissionIds;
    }

    /**
     * @param  array<string, int>  $permissionIds
     */
    private function seedRolesAndPermissions(int $applicationId, array $permissionIds): void
    {
        foreach ($this->roles($permissionIds) as $roleSlug => $roleData) {
            $role = WorkspaceApplicationRole::query()->updateOrCreate(
                [
                    'workspace_application_role_workspace_application_id' => $applicationId,
                    'workspace_application_role_slug' => $roleSlug,
                ],
                [
                    'workspace_application_role_name' => $roleData['name'],
                    'workspace_application_role_description' => $roleData['description'],
                    'workspace_application_role_is_active' => true,
                ]
            );

            foreach ($roleData['permissions'] as $permissionSlug) {
                if (! isset($permissionIds[$permissionSlug])) {
                    continue;
                }

                WorkspaceApplicationRolePermission::query()->updateOrCreate(
                    [
                        'workspace_application_role_permission_war_id' => $role->workspace_application_role_id,
                        'workspace_application_role_permission_wap_id' => $permissionIds[$permissionSlug],
                    ]
                );
            }
        }
    }

    /**
     * Module definitions with their associated permissions.
     *
     * @return array<string, array{name: string, description: string, icon: string, order: int, permissions: array<string, array{name: string, description: string}>}>
     */
    private function modules(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard',
                'description' => 'Panel principal del ERP',
                'icon' => 'layout-dashboard',
                'order' => 1,
                'permissions' => [
                    'erp.dashboard.view' => [
                        'name' => 'Ver dashboard',
                        'description' => 'Puede ver el panel principal del ERP',
                    ],
                ],
            ],

            'third_parties' => [
                'name' => 'Contratistas / Terceros',
                'description' => 'Gestión de contratistas, proveedores, clientes y terceros',
                'icon' => 'users',
                'order' => 2,
                'permissions' => [
                    'erp.third_parties.view' => [
                        'name' => 'Ver terceros',
                        'description' => 'Puede ver contratistas, proveedores, clientes y terceros',
                    ],
                    'erp.third_parties.create' => [
                        'name' => 'Crear terceros',
                        'description' => 'Puede registrar terceros',
                    ],
                    'erp.third_parties.update' => [
                        'name' => 'Editar terceros',
                        'description' => 'Puede editar terceros',
                    ],
                    'erp.third_parties.delete' => [
                        'name' => 'Eliminar terceros',
                        'description' => 'Puede eliminar terceros',
                    ],
                ],
            ],

            'contracts' => [
                'name' => 'Contratos',
                'description' => 'Gestión de contratos',
                'icon' => 'file-text',
                'order' => 3,
                'permissions' => [
                    'erp.contracts.view' => [
                        'name' => 'Ver contratos',
                        'description' => 'Puede ver contratos',
                    ],
                    'erp.contracts.create' => [
                        'name' => 'Crear contratos',
                        'description' => 'Puede crear contratos',
                    ],
                    'erp.contracts.update' => [
                        'name' => 'Editar contratos',
                        'description' => 'Puede editar contratos',
                    ],
                    'erp.contracts.delete' => [
                        'name' => 'Eliminar contratos',
                        'description' => 'Puede eliminar contratos',
                    ],
                    'erp.contracts.move_step' => [
                        'name' => 'Mover paso de contrato',
                        'description' => 'Puede avanzar o cambiar el paso del contrato',
                    ],
                    'erp.contracts.close' => [
                        'name' => 'Cerrar contratos',
                        'description' => 'Puede cerrar procesos de contrato',
                    ],
                    'erp.contracts.approve' => [
                        'name' => 'Aprobar contratos',
                        'description' => 'Puede aprobar contratos',
                    ],
                    'erp.contracts.reject' => [
                        'name' => 'Rechazar contratos',
                        'description' => 'Puede rechazar contratos',
                    ],
                ],
            ],

            'contract_addendums' => [
                'name' => 'Otrosíes',
                'description' => 'Gestión de otrosíes y modificaciones contractuales',
                'icon' => 'file-pen-line',
                'order' => 4,
                'permissions' => [
                    'erp.contract_addendums.view' => [
                        'name' => 'Ver otrosíes',
                        'description' => 'Puede ver otrosíes',
                    ],
                    'erp.contract_addendums.create' => [
                        'name' => 'Crear otrosíes',
                        'description' => 'Puede crear otrosíes',
                    ],
                    'erp.contract_addendums.update' => [
                        'name' => 'Editar otrosíes',
                        'description' => 'Puede editar otrosíes',
                    ],
                    'erp.contract_addendums.delete' => [
                        'name' => 'Eliminar otrosíes',
                        'description' => 'Puede eliminar otrosíes',
                    ],
                    'erp.contract_addendums.move_step' => [
                        'name' => 'Mover paso de otrosí',
                        'description' => 'Puede avanzar o cambiar el paso del otrosí',
                    ],
                    'erp.contract_addendums.close' => [
                        'name' => 'Cerrar otrosíes',
                        'description' => 'Puede cerrar procesos de otrosíes',
                    ],
                    'erp.contract_addendums.approve' => [
                        'name' => 'Aprobar otrosíes',
                        'description' => 'Puede aprobar otrosíes',
                    ],
                    'erp.contract_addendums.reject' => [
                        'name' => 'Rechazar otrosíes',
                        'description' => 'Puede rechazar otrosíes',
                    ],
                ],
            ],

            'documents' => [
                'name' => 'Documentos',
                'description' => 'Gestión de documentos y soportes',
                'icon' => 'folder-open',
                'order' => 5,
                'permissions' => [
                    'erp.documents.view' => [
                        'name' => 'Ver documentos',
                        'description' => 'Puede ver documentos y soportes',
                    ],
                    'erp.documents.upload' => [
                        'name' => 'Subir documentos',
                        'description' => 'Puede cargar documentos y soportes',
                    ],
                    'erp.documents.delete' => [
                        'name' => 'Eliminar documentos',
                        'description' => 'Puede eliminar documentos y soportes',
                    ],
                ],
            ],

            'approvals' => [
                'name' => 'Aprobaciones',
                'description' => 'Gestión de aprobaciones y rechazos',
                'icon' => 'badge-check',
                'order' => 6,
                'permissions' => [
                    'erp.approvals.view' => [
                        'name' => 'Ver aprobaciones',
                        'description' => 'Puede ver aprobaciones',
                    ],
                    'erp.approvals.create' => [
                        'name' => 'Crear aprobaciones',
                        'description' => 'Puede crear solicitudes de aprobación',
                    ],
                    'erp.approvals.approve' => [
                        'name' => 'Aprobar solicitudes',
                        'description' => 'Puede aprobar solicitudes',
                    ],
                    'erp.approvals.reject' => [
                        'name' => 'Rechazar solicitudes',
                        'description' => 'Puede rechazar solicitudes',
                    ],
                ],
            ],

            'comments' => [
                'name' => 'Comentarios',
                'description' => 'Gestión de comentarios internos',
                'icon' => 'messages-square',
                'order' => 7,
                'permissions' => [
                    'erp.comments.view' => [
                        'name' => 'Ver comentarios',
                        'description' => 'Puede ver comentarios',
                    ],
                    'erp.comments.create' => [
                        'name' => 'Crear comentarios',
                        'description' => 'Puede crear comentarios',
                    ],
                    'erp.comments.delete' => [
                        'name' => 'Eliminar comentarios',
                        'description' => 'Puede eliminar comentarios',
                    ],
                ],
            ],

            'workflows' => [
                'name' => 'Flujos',
                'description' => 'Consulta de flujos y pasos del ERP',
                'icon' => 'workflow',
                'order' => 8,
                'permissions' => [
                    'erp.workflows.view' => [
                        'name' => 'Ver flujos',
                        'description' => 'Puede ver flujos y pasos del ERP',
                    ],
                ],
            ],
        ];
    }

    /**
     * Role definitions with their permission slug assignments.
     *
     * @param  array<string, int>  $permissionIds  All seeded permission IDs keyed by slug
     * @return array<string, array{name: string, description: string, permissions: list<string>}>
     */
    private function roles(array $permissionIds): array
    {
        return [
            'erp_admin' => [
                'name' => 'Administrador ERP',
                'description' => 'Acceso completo al ERP',
                'permissions' => array_keys($permissionIds),
            ],

            'coordinator' => [
                'name' => 'Coordinador',
                'description' => 'Puede crear y gestionar contratos, otrosíes, documentos y comentarios',
                'permissions' => [
                    'erp.dashboard.view',
                    'erp.third_parties.view',
                    'erp.third_parties.create',
                    'erp.third_parties.update',
                    'erp.contracts.view',
                    'erp.contracts.create',
                    'erp.contracts.update',
                    'erp.contracts.move_step',
                    'erp.contracts.close',
                    'erp.contract_addendums.view',
                    'erp.contract_addendums.create',
                    'erp.contract_addendums.update',
                    'erp.contract_addendums.move_step',
                    'erp.contract_addendums.close',
                    'erp.documents.view',
                    'erp.documents.upload',
                    'erp.comments.view',
                    'erp.comments.create',
                    'erp.workflows.view',
                ],
            ],

            'administrative' => [
                'name' => 'Administrativo',
                'description' => 'Revisa documentación y avanza el proceso en la etapa administrativa',
                'permissions' => [
                    'erp.dashboard.view',
                    'erp.third_parties.view',
                    'erp.contracts.view',
                    'erp.contracts.update',
                    'erp.contracts.move_step',
                    'erp.contract_addendums.view',
                    'erp.contract_addendums.update',
                    'erp.contract_addendums.move_step',
                    'erp.documents.view',
                    'erp.documents.upload',
                    'erp.documents.delete',
                    'erp.comments.view',
                    'erp.comments.create',
                    'erp.workflows.view',
                ],
            ],

            'legal' => [
                'name' => 'Jurídica',
                'description' => 'Puede revisar, elaborar y aprobar información jurídica',
                'permissions' => [
                    'erp.dashboard.view',
                    'erp.third_parties.view',
                    'erp.contracts.view',
                    'erp.contracts.update',
                    'erp.contracts.move_step',
                    'erp.contracts.approve',
                    'erp.contracts.reject',
                    'erp.contract_addendums.view',
                    'erp.contract_addendums.update',
                    'erp.contract_addendums.move_step',
                    'erp.contract_addendums.approve',
                    'erp.contract_addendums.reject',
                    'erp.documents.view',
                    'erp.documents.upload',
                    'erp.comments.view',
                    'erp.comments.create',
                    'erp.workflows.view',
                ],
            ],

            'administrative_financial_direction' => [
                'name' => 'Dirección Administrativa y Financiera',
                'description' => 'Puede aprobar contratos, otrosíes y procesos administrativos',
                'permissions' => [
                    'erp.dashboard.view',
                    'erp.third_parties.view',
                    'erp.contracts.view',
                    'erp.contracts.approve',
                    'erp.contracts.reject',
                    'erp.contract_addendums.view',
                    'erp.contract_addendums.approve',
                    'erp.contract_addendums.reject',
                    'erp.approvals.view',
                    'erp.approvals.approve',
                    'erp.approvals.reject',
                    'erp.documents.view',
                    'erp.comments.view',
                    'erp.comments.create',
                    'erp.workflows.view',
                ],
            ],

            'viewer' => [
                'name' => 'Consulta',
                'description' => 'Solo puede consultar información del ERP',
                'permissions' => [
                    'erp.dashboard.view',
                    'erp.third_parties.view',
                    'erp.contracts.view',
                    'erp.contract_addendums.view',
                    'erp.documents.view',
                    'erp.approvals.view',
                    'erp.comments.view',
                    'erp.workflows.view',
                ],
            ],
        ];
    }
}
