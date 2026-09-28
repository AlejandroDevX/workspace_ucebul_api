<?php

namespace Database\Seeders;

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceApplicationRolePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkspaceForumApplicationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $url = config('services.forum.platform_url');

        if (! is_string($url) || trim($url) === '' || strlen($url) > 500
            || ! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Configure FORUM_PLATFORM_URL con una URL HTTP o HTTPS válida (máximo 500 caracteres).');
        }

        if (WorkspaceApplication::query()
            ->where('workspace_application_url', $url)
            ->where('workspace_application_slug', '!=', 'ucebul-forum')
            ->exists()) {
            throw new InvalidArgumentException('FORUM_PLATFORM_URL ya pertenece a otra aplicación.');
        }

        DB::transaction(function () use ($url): void {
            $application = WorkspaceApplication::query()->updateOrCreate(
                ['workspace_application_slug' => 'ucebul-forum'],
                [
                    'workspace_application_name' => 'UCEBUL Foro Técnico',
                    'workspace_application_description' => 'Plataforma educativa y comunitaria agropecuaria.',
                    'workspace_application_url' => $url,
                    'workspace_application_is_active' => true,
                ]
            );

            $permissionIds = $this->seedModulesAndPermissions($application->workspace_application_id);
            $this->seedRolesAndPermissions($application->workspace_application_id, $permissionIds);
        });
    }

    /** @return array<string, int> */
    private function seedModulesAndPermissions(int $applicationId): array
    {
        $permissionIds = [];

        foreach ($this->modules() as $slug => $data) {
            $module = WorkspaceApplicationModule::query()->updateOrCreate(
                [
                    'workspace_application_module_workspace_application_id' => $applicationId,
                    'workspace_application_module_slug' => $slug,
                ],
                [
                    'workspace_application_module_name' => $data['name'],
                    'workspace_application_module_description' => $data['description'],
                    'workspace_application_module_icon' => $data['icon'],
                    'workspace_application_module_order' => $data['order'],
                    'workspace_application_module_is_active' => true,
                ]
            );

            foreach ($data['permissions'] as $permissionSlug => $name) {
                $permission = WorkspaceApplicationPermission::query()->updateOrCreate(
                    [
                        'workspace_application_permission_workspace_application_id' => $applicationId,
                        'workspace_application_permission_slug' => $permissionSlug,
                    ],
                    [
                        'workspace_application_permission_workspace_application_module_id' => $module->workspace_application_module_id,
                        'workspace_application_permission_name' => $name,
                        'workspace_application_permission_description' => $name,
                    ]
                );
                $permissionIds[$permissionSlug] = $permission->workspace_application_permission_id;
            }
        }

        return $permissionIds;
    }

    /** @param array<string, int> $permissionIds */
    private function seedRolesAndPermissions(int $applicationId, array $permissionIds): void
    {
        $student = [
            'forum.courses.view',
            'forum.lessons.view',
            'forum.learning.enroll',
            'forum.learning.view',
            'forum.learning.progress',
            'forum.community.view',
            'forum.community.create',
            'forum.community.reply',
            'forum.community.react',
            'forum.community.accept_answer',
        ];

        $roles = [
            'student' => [
                'name' => 'Estudiante',
                'description' => 'Aprendizaje y participación en la comunidad.',
                'permissions' => $student,
            ],
            'instructor' => [
                'name' => 'Instructor',
                'description' => 'Creación, edición y publicación de cursos y lecciones.',
                'permissions' => array_merge($student, [
                    'forum.courses.create', 'forum.courses.update', 'forum.courses.publish',
                    'forum.lessons.create', 'forum.lessons.update', 'forum.lessons.publish',
                ]),
            ],
            'moderator' => [
                'name' => 'Moderador',
                'description' => 'Consulta y gestión de moderación y reportes.',
                'permissions' => array_merge($student, [
                    'forum.moderation.view', 'forum.moderation.manage', 'forum.moderation.reports',
                ]),
            ],
            'admin' => [
                'name' => 'Administrador',
                'description' => 'Administración completa de Foro Técnico.',
                'permissions' => array_keys($permissionIds),
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = WorkspaceApplicationRole::query()->updateOrCreate(
                [
                    'workspace_application_role_workspace_application_id' => $applicationId,
                    'workspace_application_role_slug' => $slug,
                ],
                [
                    'workspace_application_role_name' => $data['name'],
                    'workspace_application_role_description' => $data['description'],
                    'workspace_application_role_is_active' => true,
                ]
            );

            foreach ($data['permissions'] as $permissionSlug) {
                WorkspaceApplicationRolePermission::query()->updateOrCreate([
                    'workspace_application_role_permission_war_id' => $role->workspace_application_role_id,
                    'workspace_application_role_permission_wap_id' => $permissionIds[$permissionSlug],
                ]);
            }
        }
    }

    /** @return array<string, array{name: string, description: string, icon: string, order: int, permissions: array<string, string>}> */
    private function modules(): array
    {
        return [
            'courses' => [
                'name' => 'Cursos',
                'description' => 'Gestión de cursos.',
                'icon' => 'book-open',
                'order' => 1,
                'permissions' => [
                    'forum.courses.view' => 'Ver cursos',
                    'forum.courses.create' => 'Crear cursos',
                    'forum.courses.update' => 'Editar cursos',
                    'forum.courses.delete' => 'Eliminar cursos',
                    'forum.courses.publish' => 'Publicar cursos',
                ],
            ],
            'lessons' => [
                'name' => 'Lecciones',
                'description' => 'Gestión de lecciones.',
                'icon' => 'play-circle',
                'order' => 2,
                'permissions' => [
                    'forum.lessons.view' => 'Ver lecciones',
                    'forum.lessons.create' => 'Crear lecciones',
                    'forum.lessons.update' => 'Editar lecciones',
                    'forum.lessons.delete' => 'Eliminar lecciones',
                    'forum.lessons.publish' => 'Publicar lecciones',
                ],
            ],
            'learning' => [
                'name' => 'Aprendizaje',
                'description' => 'Inscripciones y progreso de aprendizaje.',
                'icon' => 'graduation-cap',
                'order' => 3,
                'permissions' => [
                    'forum.learning.enroll' => 'Inscribirse en cursos',
                    'forum.learning.view' => 'Consultar aprendizaje',
                    'forum.learning.progress' => 'Registrar progreso',
                ],
            ],
            'community' => [
                'name' => 'Comunidad',
                'description' => 'Preguntas y respuestas de la comunidad.',
                'icon' => 'messages-square',
                'order' => 4,
                'permissions' => [
                    'forum.community.view' => 'Consultar comunidad',
                    'forum.community.create' => 'Crear preguntas',
                    'forum.community.update' => 'Editar publicaciones',
                    'forum.community.delete' => 'Eliminar publicaciones',
                    'forum.community.reply' => 'Responder preguntas',
                    'forum.community.react' => 'Reaccionar a publicaciones',
                    'forum.community.accept_answer' => 'Aceptar respuestas',
                ],
            ],
            'moderation' => [
                'name' => 'Moderación',
                'description' => 'Moderación de la comunidad y sus reportes.',
                'icon' => 'shield-check',
                'order' => 5,
                'permissions' => [
                    'forum.moderation.view' => 'Consultar moderación',
                    'forum.moderation.manage' => 'Gestionar moderación',
                    'forum.moderation.reports' => 'Consultar reportes',
                ],
            ],
        ];
    }
}
