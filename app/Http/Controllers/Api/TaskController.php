<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', 'max:50'],
            'priority' => ['sometimes', 'string', 'max:50'],
            'assigned_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', Rule::in(['title', 'status', 'priority', 'due_date', 'created_at', 'assigned_user_id'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Task::query()->with([
            'assignedUser:id,name,email',
            'creator:id,name,email',
        ]);

        foreach (['status', 'priority', 'assigned_user_id'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:50'],
            'priority' => ['sometimes', 'string', 'max:50'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = Task::create(array_merge($validated, [
            'created_by' => $request->user('api')->id,
        ]));

        return response()->json([
            'data' => $task->load(['assignedUser:id,name,email', 'creator:id,name,email']),
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'priority' => ['sometimes', 'required', 'string', 'max:50'],
            'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $task->update($validated);

        return response()->json([
            'data' => $task->load(['assignedUser:id,name,email', 'creator:id,name,email']),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(null, 204);
    }
}
