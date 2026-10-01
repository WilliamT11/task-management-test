<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_listed_with_filters_sorting_and_pagination(): void
    {
        $user = User::factory()->create();
        $this->authenticateAs($user);

        $this->createTask($user, ['title' => 'Beta task', 'status' => 'pending']);
        $this->createTask($user, ['title' => 'Alpha task', 'status' => 'pending']);
        $this->createTask($user, ['title' => 'Completed task', 'status' => 'completed']);

        $this->getJson('/api/tasks?status=pending&sort_by=title&sort_direction=asc&per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('data.0.title', 'Alpha task');
    }

    public function test_authenticated_user_can_create_a_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->authenticateAs($user);

        $this->postJson('/api/tasks', [
            'title' => 'Prepare interview',
            'description' => 'Review the project requirements.',
            'assigned_user_id' => $otherUser->id,
            'created_by' => $otherUser->id,
        ])->assertCreated()
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.assigned_user_id', $otherUser->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Prepare interview',
            'created_by' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_list_assignable_users(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create([
            'name' => 'Available Assignee',
            'email' => 'assignee@example.com',
        ]);
        $this->authenticateAs($user);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $assignee->id,
                'name' => $assignee->name,
                'email' => $assignee->email,
            ])
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_authenticated_user_can_update_and_delete_a_task(): void
    {
        $user = User::factory()->create();
        $this->authenticateAs($user);
        $task = $this->createTask($user);

        $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_endpoints_require_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->postJson('/api/tasks', ['title' => 'Unauthorized task'])->assertUnauthorized();
    }

    private function authenticateAs(User $user): void
    {
        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken($login->json('access_token'));
    }

    private function createTask(User $creator, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Sample task',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $creator->id,
        ], $attributes));
    }
}
