<?php

use App\Models\WorkspaceApplication;
use App\Models\WorkspaceUser;
use App\Models\WorkspaceUserApplication;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function summaryApplication(string $slug = 'ucebul-forum', bool $active = true): WorkspaceApplication
{
    return WorkspaceApplication::query()->create([
        'workspace_application_name' => 'App '.$slug,
        'workspace_application_slug' => $slug,
        'workspace_application_url' => 'https://'.$slug.'.example.test',
        'workspace_application_is_active' => $active,
    ]);
}

function summaryUser(string $name, string $lastName): WorkspaceUser
{
    return WorkspaceUser::query()->create([
        'workspace_user_email' => strtolower($name.'.'.$lastName).'@example.test',
        'workspace_user_document_number' => (string) fake()->unique()->numberBetween(10000000, 99999999),
        'workspace_user_password' => 'secret-password',
        'workspace_user_name' => $name,
        'workspace_user_last_name' => $lastName,
    ]);
}

function linkUser(WorkspaceUser $user, WorkspaceApplication $application, bool $active = true): void
{
    WorkspaceUserApplication::query()->create([
        'workspace_user_application_workspace_user_id' => $user->workspace_user_id,
        'workspace_user_application_workspace_application_id' => $application->workspace_application_id,
        'workspace_user_application_is_active' => $active,
    ]);
}

function summaryQuery(array $ids, string $slug = 'ucebul-forum'): string
{
    return '/api/workspace-user-summaries?'.http_build_query(['application_slug' => $slug, 'workspace_user_ids' => $ids]);
}

it('returns only the basic data of users linked to the application', function () {
    $forum = summaryApplication();
    $erp = summaryApplication('ucebul-erp');
    $viewer = summaryUser('Ana', 'Pérez');
    $author = summaryUser('Luis', 'Gómez');
    $formerAuthor = summaryUser('Marta', 'Ruiz');
    $outsider = summaryUser('Pedro', 'Díaz');
    linkUser($viewer, $forum);
    linkUser($author, $forum);
    linkUser($formerAuthor, $forum, active: false);
    linkUser($outsider, $erp);
    Sanctum::actingAs($viewer);

    $response = $this->getJson(summaryQuery([
        $author->workspace_user_id, $formerAuthor->workspace_user_id, $outsider->workspace_user_id, 999999,
    ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertExactJson([
            'success' => true,
            'message' => 'Workspace user summaries',
            'data' => [
                ['workspace_user_id' => $author->workspace_user_id, 'workspace_user_name' => 'Luis', 'workspace_user_last_name' => 'Gómez'],
                ['workspace_user_id' => $formerAuthor->workspace_user_id, 'workspace_user_name' => 'Marta', 'workspace_user_last_name' => 'Ruiz'],
            ],
            'meta' => [],
        ]);
    expect($response->getContent())->not->toContain('@example.test')
        ->not->toContain('workspace_user_document_number');
});

it('requires authentication', function () {
    summaryApplication();

    $this->getJson(summaryQuery([1]))->assertUnauthorized();
});

it('denies users without active access to the application', function (bool $linked) {
    $forum = summaryApplication();
    $viewer = summaryUser('Ana', 'Pérez');
    if ($linked) {
        linkUser($viewer, $forum, active: false);
    }
    Sanctum::actingAs($viewer);

    $this->getJson(summaryQuery([$viewer->workspace_user_id]))
        ->assertForbidden()
        ->assertJsonPath('success', false);
})->with(['sin vínculo' => false, 'acceso inactivo' => true]);

it('rejects unknown or inactive applications', function () {
    $inactive = summaryApplication('ucebul-forum', active: false);
    $viewer = summaryUser('Ana', 'Pérez');
    linkUser($viewer, $inactive);
    Sanctum::actingAs($viewer);

    $this->getJson(summaryQuery([1], 'no-existe'))->assertNotFound();
    $this->getJson(summaryQuery([1]))->assertForbidden();
});

it('validates the requested identifiers', function (array $query, string $field) {
    $forum = summaryApplication();
    $viewer = summaryUser('Ana', 'Pérez');
    linkUser($viewer, $forum);
    Sanctum::actingAs($viewer);

    $this->getJson('/api/workspace-user-summaries?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => [$field]]);
})->with([
    'sin aplicación' => [['workspace_user_ids' => [1]], 'application_slug'],
    'sin IDs' => [['application_slug' => 'ucebul-forum'], 'workspace_user_ids'],
    'ID no entero' => [['application_slug' => 'ucebul-forum', 'workspace_user_ids' => ['abc']], 'workspace_user_ids.0'],
    'más de 100' => [['application_slug' => 'ucebul-forum', 'workspace_user_ids' => range(1, 101)], 'workspace_user_ids'],
]);
