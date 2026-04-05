<?php

namespace Database\Seeders;

use App\Models\WorkspaceRole;
use App\Models\WorkspaceUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
    }
}
