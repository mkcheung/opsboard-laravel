<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TaskControllerTest extends TestCase
{

    use DatabaseMigrations;
    protected TaskController $taskController;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->taskController = $this->app->make(TaskController::class);

        $unhashedPassword = 'testing123';
        $this->user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
    }

    public function test_index(): void
    {
        $tasks = Task::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);
        $response = $this->actingAs($this->user)->getJson("api/tasks?page_size=10");
        $response->assertStatus(200);
        $jsonRes = $response->json();
        $this->assertCount(3, $jsonRes['data']);
    }

    public function test_create(): void
    {
        $project = Project::factory()->create();
        $due_date = date('Y-m-d');
        $completed_at = Carbon::parse($due_date)->addDays(5)->format('Y-m-d');
        $request = Request::create(
            'api/tasks/',
            'POST',
            [
                'project_id' => $project->id,
                'title' => 'testing',
                'description' => 'testing description',
                'status' => 'done',
                'priority' => 'medium',
                'due_date' => $due_date,
                'completed_at' => $completed_at,
                'estimate_minutes' => 60,
            ]
        );
        $request->setUserResolver(fn() => $this->user);
        $response = $this->taskController->store($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($this->user->id, $data['task']['user_id']);
        $this->assertSame($project->id, $data['task']['project_id']);
        $this->assertSame('testing', $data['task']['title']);
        $this->assertSame('testing description', $data['task']['description']);
        $this->assertSame('done', $data['task']['status']);
        $this->assertSame('medium', $data['task']['priority']);
        $this->assertSame('2025-12-23', $data['task']['due_date']);
        $this->assertSame(60, $data['task']['estimate_minutes']);
        $this->assertSame("2025-12-28", $data['task']['completed_at']);
    }

    public function test_show(): void {}
}
