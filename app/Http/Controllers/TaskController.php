<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = min($request->integer('page_size', 10), 100);
        $allowedSorts = ['due_date', 'priority', 'status', 'created_at'];
        $allowedDirs = ['asc', 'desc'];
        $priority = $request->query('priority');
        $status = $request->query('status');
        $search = $request->query('search');
        $sort = $request->query('sort');
        $dir = $request->query('dir', 'asc');
        $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when(
                $search,
                fn($q) =>
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                })
            )
            ->when(
                $sort && in_array($sort, $allowedSorts) && in_array($dir, $allowedDirs),
                fn($q) => $q->orderBy($sort, $dir)
            )->paginate($perPage);


        return response()->json([
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => [
                'required',
                Rule::in(['todo', 'doing', 'done']),
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high']),
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'estimate_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'completed_at' => [
                'nullable',
                'date',
            ],
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => ($data['description'] ?? null),
            'status' => $data['status'],
            'priority' => $data['priority'],
            'due_date' => ($data['due_date'] ?? null),
            'estimate_minutes' => ($data['estimate_minutes'] ?? null),
            'completed_at' => ($data['completed_at'] ?? null)
        ]);

        return response()->json([
            'data' => $task
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {

        $task = Task::where('id', $id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found'
            ], 400);
        }

        if ($request->user()->id !== $task->user_id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return response()->json([
            'data' => $task
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        if ($request->user()->id !== $task->user_id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }
        $data = $request->validate([
            'project_id' => ['sometimes', 'integer'],
            'title' => ['sometimes', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => [
                'sometimes',
                Rule::in(['todo', 'doing', 'done']),
            ],
            'priority' => [
                'sometimes',
                Rule::in(['low', 'medium', 'high']),
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'estimate_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'completed_at' => [
                'nullable',
                'date',
            ],
        ]);

        $task->update($data);

        return response()->json([
            'data' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id)
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }
}
