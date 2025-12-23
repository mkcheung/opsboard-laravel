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
        $tasks = Task::where('user_id', '=', $request->user()->id)->paginate($perPage);
        return response()->json($tasks);
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
            'description' => $data['description'],
            'status' => $data['status'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'estimate_minutes' => $data['estimate_minutes'],
            'completed_at' => $data['completed_at']
        ]);

        return response()->json([
            'task' => [
                'id' => $task->id,
                'project_id' => $task->project_id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'estimate_minutes' => $task->estimate_minutes,
                'completed_at' => $task->completed_at
            ]
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!ctype_digit($id)) {
            return response()->json([
                'message' => 'Invalid Task Id'
            ], 400);
        }
        $task = Task::where('id', $id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found'
            ], 400);
        }

        return response()->json([
            'task' => $task
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        if ($request->user()->id == $task->user_ud) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }
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

        $task->update($data);

        return response()->json([
            'task' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }
}
