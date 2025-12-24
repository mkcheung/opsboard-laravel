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
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_index_search_priority(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);

        $t1 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(3),
        ]);

        $t2 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(2),
        ]);

        $t3 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(1),
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'low',
            'created_at' => now()
        ]);

        Task::factory()->create([
            'user_id' => User::factory(),
            'priority' => 'high',
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user)->getJson("api/tasks?priority=high&sort=created_at&dir=asc&page_size=10");
        $response->assertJsonCount(3, 'data.data');
        $ids = array_column($response->json('data.data'), 'id');
        $this->assertSame([$t1->id, $t2->id, $t3->id], $ids);
    }

    public function test_index_created_date_desc(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);

        $t1 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(3),
        ]);

        $t2 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(2),
        ]);

        $t3 = Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->getJson("api/tasks?priority=high&sort=created_at&dir=desc&page_size=10");
        $response->assertJsonCount(3, 'data.data');
        $ids = array_column($response->json('data.data'), 'id');
        $this->assertSame([$t3->id, $t2->id, $t1->id], $ids);
    }

    public function test_index_search_status(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'status' => 'todo',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'status' => 'done',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->getJson("api/tasks?status=todo&priority=high&sort=created_at&dir=desc&page_size=10");
        $response->assertJsonCount(1, 'data.data');
    }

    public function test_index_search_title_desc(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'title' => 'Today is busy',
            'status' => 'todo',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'description' => 'Busy times expected',
            'status' => 'done',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->getJson("api/tasks?sort=created_at&dir=desc&search=busy&page_size=10");
        $response->assertJsonCount(2, 'data.data');
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
        $this->assertSame($this->user->id, $data['data']['user_id']);
        $this->assertSame($project->id, $data['data']['project_id']);
        $this->assertSame('testing', $data['data']['title']);
        $this->assertSame('testing description', $data['data']['description']);
        $this->assertSame('done', $data['data']['status']);
        $this->assertSame('medium', $data['data']['priority']);
        $this->assertSame($due_date, $data['data']['due_date']);
        $this->assertSame(60, $data['data']['estimate_minutes']);
        $this->assertSame($completed_at, $data['data']['completed_at']);
    }

    public function test_show(): void
    {
        $taskToCreate = Task::factory()->make();
        $inputData = $taskToCreate->toArray();
        $taskToCreate->save();
        $user = User::find($taskToCreate->user_id);
        $request = Request::create(
            "api/tasks/{$taskToCreate->id}"
        );

        $request->setUserResolver(fn() => $user);
        $response = $this->taskController->show($request, $taskToCreate->id);
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame($inputData['user_id'], $data['data']['user_id']);
        $this->assertSame($inputData['project_id'], $data['data']['project_id']);
        $this->assertSame($inputData['title'], $data['data']['title']);
        $this->assertSame($inputData['description'], $data['data']['description']);
        $this->assertSame($inputData['status'], $data['data']['status']);
        $this->assertSame($inputData['priority'], $data['data']['priority']);
        $this->assertSame($inputData['due_date'], $data['data']['due_date']);
        $this->assertSame($inputData['estimate_minutes'], $data['data']['estimate_minutes']);
    }

    public function test_show_nonexistent_task(): void
    {
        $nonexistentTaskId = Task::max('id') + 1;
        $request = Request::create(
            "api/tasks/{$nonexistentTaskId}"
        );
        $request->setUserResolver(fn() => $this->user);
        $response = $this->taskController->show($request, $nonexistentTaskId);
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
        $this->assertSame($originalInputData['title'], $data['data']['title']);
        $this->assertSame('New Description', $data['data']['description']);
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
