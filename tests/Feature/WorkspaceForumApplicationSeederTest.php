<?php

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationModule;
use App\Models\WorkspaceApplicationPermission;
use App\Models\WorkspaceApplicationRole;
use App\Models\WorkspaceApplicationRolePermission;
use App\Models\WorkspaceUser;
use App\Models\WorkspaceUserApplication;
use Database\Seeders\WorkspaceErpApplicationSeeder;
use Database\Seeders\WorkspaceForumApplicationSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.forum.platform_url' => 'https://forum.example.test']);
});

it('registers the application, modules, exact permissions and role matrix', function () {
    $this->seed(WorkspaceForumApplicationSeeder::class);

    $application = WorkspaceApplication::query()->sole();
    expect($application->workspace_application_slug)->toBe('ucebul-forum')
        ->and($application->workspace_application_name)->toBe('UCEBUL Foro Técnico')
        ->and($application->workspace_application_description)->toBe('Plataforma educativa y comunitaria agropecuaria.')
        ->and($application->workspace_application_url)->toBe('https://forum.example.test')
        ->and($application->workspace_application_is_active)->toBeTrue();

    $expectedModules = [
        'courses' => ['view', 'create', 'update', 'delete', 'publish'],
        'lessons' => ['view', 'create', 'update', 'delete', 'publish'],
        'learning' => ['enroll', 'view', 'progress'],
        'community' => ['view', 'create', 'update', 'delete', 'reply', 'react', 'accept_answer'],
        'moderation' => ['view', 'manage', 'reports'],
    ];
    $modules = $application->applicationModules()->orderBy('workspace_application_module_order')->get();
    expect($modules->pluck('workspace_application_module_slug')->all())->toBe(array_keys($expectedModules));
    $allPermissions = [];
    foreach ($modules as $index => $module) {
        $slug = $module->workspace_application_module_slug;
        $expected = array_map(fn (string $action): string => "forum.$slug.$action", $expectedModules[$slug]);
        array_push($allPermissions, ...$expected);
        expect($module->workspace_application_module_order)->toBe($index + 1)
            ->and($module->workspace_application_module_is_active)->toBeTrue()
            ->and($module->workspace_application_module_name)->not->toBeEmpty()
            ->and($module->workspace_application_module_description)->not->toBeEmpty()
            ->and($module->workspace_application_module_icon)->not->toBeEmpty()
            ->and($module->applicationPermissions->pluck('workspace_application_permission_slug')->all())->toEqualCanonicalizing($expected);
        foreach ($module->applicationPermissions as $permission) {
            expect($permission->workspace_application_permission_workspace_application_id)->toBe($application->getKey())
                ->and($permission->workspace_application_permission_name)->not->toBeEmpty()
                ->and($permission->workspace_application_permission_description)->not->toBeEmpty();
        }
    }

    $student = [
        'forum.courses.view', 'forum.lessons.view', 'forum.learning.enroll',
        'forum.learning.view', 'forum.learning.progress', 'forum.community.view',
        'forum.community.create', 'forum.community.reply', 'forum.community.react',
        'forum.community.accept_answer',
    ];
    $matrix = [
        'student' => $student,
        'instructor' => array_merge($student, [
            'forum.courses.create', 'forum.courses.update', 'forum.courses.publish',
            'forum.lessons.create', 'forum.lessons.update', 'forum.lessons.publish',
        ]),
        'moderator' => array_merge($student, [
            'forum.moderation.view', 'forum.moderation.manage', 'forum.moderation.reports',
        ]),
        'admin' => $allPermissions,
    ];
    $roles = $application->applicationRoles()->with('applicationPermissions')->get();
    expect($roles->pluck('workspace_application_role_slug')->all())->toEqualCanonicalizing(array_keys($matrix));
    foreach ($roles as $role) {
        expect($role->workspace_application_role_is_active)->toBeTrue()
            ->and($role->applicationPermissions->pluck('workspace_application_permission_slug')->all())
            ->toEqualCanonicalizing($matrix[$role->workspace_application_role_slug]);
    }
    $this->assertDatabaseCount('workspace_application_permissions', 23);
    $this->assertDatabaseCount('workspace_application_role_permissions', 62);
    $this->assertDatabaseCount('workspace_users', 0);
    $this->assertDatabaseCount('workspace_user_applications', 0);
});

it('is idempotent and updates the configured URL without replacing records', function () {
    $this->seed(WorkspaceForumApplicationSeeder::class);
    $models = [WorkspaceApplication::class, WorkspaceApplicationModule::class,
        WorkspaceApplicationPermission::class, WorkspaceApplicationRole::class,
        WorkspaceApplicationRolePermission::class];
    $snapshot = fn (): array => collect($models)->mapWithKeys(fn (string $model): array => [
        $model => $model::query()->orderBy((new $model)->getKeyName())->get()->toArray(),
    ])->all();
    $before = $snapshot();
    $this->travel(1)->hours();
    $this->seed(WorkspaceForumApplicationSeeder::class);
    expect($snapshot())->toBe($before);

    $applicationId = WorkspaceApplication::query()->sole()->getKey();
    config(['services.forum.platform_url' => 'https://new-forum.example.test']);
    $this->seed(WorkspaceForumApplicationSeeder::class);
    expect(WorkspaceApplication::query()->sole()->getKey())->toBe($applicationId)
        ->and(WorkspaceApplication::query()->sole()->workspace_application_url)->toBe('https://new-forum.example.test');
});

it('preserves ERP and existing user records and assignments', function () {
    $this->seed(WorkspaceErpApplicationSeeder::class);
    $erp = WorkspaceApplication::query()->where('workspace_application_slug', 'ucebul-erp')->sole();
    $user = WorkspaceUser::query()->create([
        'workspace_user_email' => 'existing@example.test',
        'workspace_user_password' => 'test-password',
        'workspace_user_name' => 'Existing',
        'workspace_user_last_name' => 'User',
    ]);
    WorkspaceUserApplication::query()->create([
        'workspace_user_application_workspace_user_id' => $user->getKey(),
        'workspace_user_application_workspace_application_id' => $erp->getKey(),
        'workspace_user_application_workspace_application_role_id' => $erp->applicationRoles()->firstOrFail()->getKey(),
        'workspace_user_application_is_active' => true,
    ]);
    $snapshot = [];
    foreach (Schema::getTableListing() as $table) {
        if (str_starts_with($table, 'workspace_')) {
            $snapshot[$table] = DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all();
        }
    }
    $this->travel(1)->hours();
    $this->seed(WorkspaceForumApplicationSeeder::class);
    $this->seed(WorkspaceForumApplicationSeeder::class);
    foreach ($snapshot as $table => $rows) {
        foreach ($rows as $row) {
            $this->assertDatabaseHas($table, $row);
        }
        if (! str_starts_with($table, 'workspace_application')) {
            $this->assertDatabaseCount($table, count($rows));
        }
    }
    expect($erp->fresh()->getAttributes())->toBe($erp->getAttributes());
    $this->assertDatabaseCount('workspace_applications', 2);
});

it('rejects missing or invalid URLs without writing records', function (mixed $url) {
    config(['services.forum.platform_url' => $url]);
    expect(fn () => $this->seed(WorkspaceForumApplicationSeeder::class))
        ->toThrow(InvalidArgumentException::class, 'FORUM_PLATFORM_URL');
    $this->assertDatabaseCount('workspace_applications', 0);
    $this->assertDatabaseCount('workspace_application_modules', 0);
})->with([null, '', '   ', 'not-a-url', 'ftp://forum.example.test', 'https://forum.example.test/'.str_repeat('a', 500)]);

it('rejects URLs owned by ERP without modifying it', function () {
    $this->seed(WorkspaceErpApplicationSeeder::class);
    $erp = WorkspaceApplication::query()->sole();
    config(['services.forum.platform_url' => $erp->workspace_application_url]);
    expect(fn () => $this->seed(WorkspaceForumApplicationSeeder::class))
        ->toThrow(InvalidArgumentException::class, 'ya pertenece a otra aplicación');
    expect($erp->fresh()->getAttributes())->toBe($erp->getAttributes());
    $this->assertDatabaseCount('workspace_applications', 1);
});
