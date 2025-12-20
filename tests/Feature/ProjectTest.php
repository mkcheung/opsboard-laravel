<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ProjectController;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProjectTest extends TestCase
{

    use DatabaseMigrations;
    protected ProjectController $projectController;

    public function setUp(): void
    {
        parent::setUp();
        $this->projectController = $this->app->make(ProjectController::class);
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
}
