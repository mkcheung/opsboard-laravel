<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\TaskController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TaskControllerTest extends TestCase
{

    use DatabaseMigrations;
    protected TaskController $taskController;
    protected User $user;

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

    public function test_show(): void
    {
        $taskToCreate = Task::factory()->make();
        $inputData = $taskToCreate->toArray();
        $taskToCreate->save();

        $response = $this->taskController->show($taskToCreate->id);
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($inputData['user_id'], $data['task']['user_id']);
        $this->assertSame($inputData['project_id'], $data['task']['project_id']);
        $this->assertSame($inputData['title'], $data['task']['title']);
        $this->assertSame($inputData['description'], $data['task']['description']);
        $this->assertSame($inputData['status'], $data['task']['status']);
        $this->assertSame($inputData['priority'], $data['task']['priority']);
        $this->assertSame($inputData['due_date'], $data['task']['due_date']);
        $this->assertSame($inputData['estimate_minutes'], $data['task']['estimate_minutes']);
    }

    public function test_show_invalid_id(): void
    {
        $response = $this->taskController->show('faulty');
        $this->assertSame(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('Invalid Task Id', $data['message']);
    }

    public function test_show_nonexistent_task(): void
    {
        $nonexistentTaskId = Task::max('id') + 1;
        $response = $this->taskController->show($nonexistentTaskId);
        $this->assertSame(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('Task not found', $data['message']);
    }

    public function test_update(): void
    {
        $taskToCreate = Task::factory()->make();
        $originalInputData = $taskToCreate->toArray();
        $user = User::find($originalInputData['user_id']);
        $taskToCreate->save();
        $request = Request::create(
            "api/tasks/{$taskToCreate->id}",
            "PUT",
            [
                'description' => 'New Description'
            ]
        );

        $request->setUserResolver(fn() => $user);
        $response = $this->taskController->update($request, $taskToCreate);
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($originalInputData['title'], $data['task']['title']);
        $this->assertSame('New Description', $data['task']['description']);
    }

    public function test_update_invalid_user(): void
    {
        $taskToCreate = Task::factory()->make();
        $originalInputData = $taskToCreate->toArray();
        $user = User::find($originalInputData['user_id']);
        $taskToCreate->save();
        $request = Request::create(
            "api/tasks/{$taskToCreate->id}",
            "PUT",
            [
                'description' => 'New Description'
            ]
        );

        $request->setUserResolver(fn() => $this->user);
        $response = $this->taskController->update($request, $taskToCreate);
        $this->assertSame(403, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('Forbidden', $data['message']);
    }

    public function test_update_invalid_priority(): void
    {
        $this->expectException(ValidationException::class);
        $taskToCreate = Task::factory()->make();
        $originalInputData = $taskToCreate->toArray();
        $user = User::find($originalInputData['user_id']);
        $taskToCreate->save();
        $request = Request::create(
            "api/tasks/{$taskToCreate->id}",
            "PUT",
            [
                'priority' => 'none'
            ]
        );

        $request->setUserResolver(fn() => $user);
        $response = $this->taskController->update($request, $taskToCreate);
    }

    public function test_update_invalid_status(): void
    {
        $this->expectException(ValidationException::class);
        $taskToCreate = Task::factory()->make();
        $originalInputData = $taskToCreate->toArray();
        $user = User::find($originalInputData['user_id']);
        $taskToCreate->save();
        $request = Request::create(
            "api/tasks/{$taskToCreate->id}",
            "PUT",
            [
                'status' => 'none'
            ]
        );

        $request->setUserResolver(fn() => $user);
        $response = $this->taskController->update($request, $taskToCreate);
    }

    public function test_destroy(): void
    {
        $task = Task::factory()->create();
        $user = User::find($task->user_id);
        $request = Request::create(
            "api/tasks/{$task->id}",
            'DELETE'
        );
        $request->setUserResolver(fn() => $user);
        $res = $this->taskController->destroy($request, $task->id);
        $this->assertSame(204, $res->getStatusCode());
    }

    public function test_delete_task_invalid_task(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $task = Task::factory()->create();
        $user = User::find($task->user_id);
        $request = Request::create(
            "api/tasks/{$task->id}",
            'DELETE',
        );
        $request->setUserResolver(fn() => $this->user);
        $invalidId = Task::max('id') + 1;
        $this->taskController->destroy($request, $invalidId);
    }

    public function test_destroy_with_invalid_user(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $task = Task::factory()->create();
        $user = User::find($task->user_id);
        $request = Request::create(
            "api/tasks/{$task->id}",
            'DELETE'
        );
        $request->setUserResolver(fn() => $this->user);
        $res = $this->taskController->destroy($request, $task->id);
        $this->assertSame(204, $res->getStatusCode());
    }
}
