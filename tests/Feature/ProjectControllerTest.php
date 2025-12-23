<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ProjectController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProjectControllerTest extends TestCase
{

    use DatabaseMigrations;
    protected ProjectController $projectController;
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->projectController = $this->app->make(ProjectController::class);
        $unhashedPassword = 'testing123';
        $this->user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
    }
    /**
     * A basic feature test example.
     */
    public function test_index(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);

        Project::factory()->count(3)->create([
            'user_id' => $user->id
        ]);

        Project::factory()->create([
            'user_id' => User::factory(),
        ]);

        $response = $this->actingAs($user)->getJson("api/projects?page_size=10");
        $response->assertStatus(200);
        $jsonRes = $response->json();
        $this->assertCount(3, $jsonRes['data']);
    }

    public function test_create(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $request = Request::create(
            'api/projects/',
            'POST',
            [
                'name' => 'testing123',
                'description' => "testing description"
            ]
        );
        $request->setUserResolver(fn() => $user);
        $response = $this->projectController->store($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_show(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->projectController->show($project->id);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('project', $data);
        $this->assertNotEmpty($data['project']);
        $this->assertEquals($data['project']['user_id'], $user->id);
        $this->assertEquals($project->id, $data['project']['id']);
    }

    public function test_show_invalid_id(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->projectController->show('a');
        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Invalid Project Id', $data['message']);
    }

    public function test_show_non_existent_project(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $nonExistentId = Project::max('id') + 1;
        $response = $this->projectController->show($nonExistentId);
        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Project not found', $data['message']);
    }

    public function test_update(): void
    {
        $projectToCreate = Project::factory()->make();
        $initialProjectData = $projectToCreate->toArray();
        $user = User::find($initialProjectData['user_id']);
        $projectToCreate->save();
        $request = Request::create(
            "api/projects/{$projectToCreate->id}",
            'PUT',
            [
                'description' => 'New Project Description'
            ]
        );
        $request->setUserResolver(fn() => $user);
        $response = $this->projectController->update($request, $projectToCreate);
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($projectToCreate['name'], $data['project']['name']);
        $this->assertNotSame($projectToCreate['description'], $initialProjectData['description']);
        $this->assertSame($projectToCreate['description'], 'New Project Description');
    }

    public function test_update_invalid_user(): void
    {
        $projectToCreate = Project::factory()->make();
        $initialProjectData = $projectToCreate->toArray();
        $user = User::find($initialProjectData['user_id']);
        $projectToCreate->save();
        $request = Request::create(
            "api/projects/{$projectToCreate->id}",
            'PUT',
            [
                'description' => 'New Project Description'
            ]
        );
        $request->setUserResolver(fn() => $this->user);
        $response = $this->projectController->update($request, $projectToCreate);
        $this->assertSame(403, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($data['message'], 'Forbidden');
    }

    public function test_delete_project(): void
    {
        $project = Project::factory()->create();
        $user = User::find($project->user_id);
        $request = Request::create(
            "api/projects/{$project->id}",
            'DELETE',
        );
        $request->setUserResolver(fn() => $user);
        $response = $this->projectController->destroy($request, $project->id);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_delete_project_invalid_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $project = Project::factory()->create();
        $user = User::find($project->user_id);
        $request = Request::create(
            "api/projects/{$project->id}",
            'DELETE',
        );
        $request->setUserResolver(fn() => $this->user);
        $invalidId = Project::max('id') + 1;
        $this->projectController->destroy($request, $invalidId);
    }

    public function test_delete_project_invalid_user(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $project = Project::factory()->create();
        $user = User::find($project->user_id);
        $request = Request::create(
            "api/projects/{$project->id}",
            'DELETE',
        );
        $request->setUserResolver(fn() => $this->user);
        $response = $this->projectController->destroy($request, $project->id);
        $this->assertSame(204, $response->getStatusCode());
    }
}
