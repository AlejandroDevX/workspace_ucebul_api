<?php

namespace Database\Seeders;

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceUser;
use App\Models\WorkspaceUserApplication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkspaceErpUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create ERP workflow users and link them to the ERP application with their role.
     * Safe to run multiple times — uses updateOrCreate throughout.
     */
    public function run(): void
    {
        $application = WorkspaceApplication::query()
            ->where('workspace_application_slug', 'ucebul-erp')
            ->firstOrFail();

        foreach ($this->users() as $userData) {
            $user = WorkspaceUser::query()->updateOrCreate(
                ['workspace_user_email' => $userData['email']],
                [
                    'workspace_user_name' => $userData['name'],
                    'workspace_user_last_name' => $userData['last_name'],
                    'workspace_user_password' => Hash::make('Salmo91#'),
                ]
            );

            $role = WorkspaceApplicationRole::query()
                ->where('workspace_application_role_workspace_application_id', $application->workspace_application_id)
                ->where('workspace_application_role_slug', $userData['role_slug'])
                ->first();

            WorkspaceUserApplication::query()->updateOrCreate(
                [
                    'workspace_user_application_workspace_user_id' => $user->workspace_user_id,
                    'workspace_user_application_workspace_application_id' => $application->workspace_application_id,
                ],
                [
                    'workspace_user_application_workspace_application_role_id' => $role?->workspace_application_role_id,
                    'workspace_user_application_is_active' => true,
                ]
            );
        }
    }

    /**
     * @return list<array{email: string, name: string, last_name: string, role_slug: string}>
     */
    private function users(): array
    {
        return [
            [
                'email' => 'ucebul.erp.coordinador@ucebul.com',
                'name' => 'Coordinador',
                'last_name' => 'ERP',
                'role_slug' => 'coordinator',
            ],
            [
                'email' => 'ucebul.erp.administrativo@ucebul.com',
                'name' => 'Administrativo',
                'last_name' => 'ERP',
                'role_slug' => 'administrative',
            ],
            [
                'email' => 'ucebul.erp.juridica@ucebul.com',
                'name' => 'Jurídica',
                'last_name' => 'ERP',
                'role_slug' => 'legal',
            ],
            [
                'email' => 'ucebul.erp.direccion@ucebul.com',
                'name' => 'Dirección',
                'last_name' => 'Administrativa y Financiera',
                'role_slug' => 'administrative_financial_direction',
            ],
        ];
    }
}
