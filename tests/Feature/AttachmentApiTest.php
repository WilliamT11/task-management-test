<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_and_download_an_attachment(): void
    {
        $disk = Storage::fake('local');
        $task = $this->createTask();

        $response = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('requirements.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.file_name', 'requirements.pdf')
            ->assertJsonMissingPath('data.file_path');

        $attachment = TaskAttachment::firstOrFail();
        $disk->assertExists($attachment->file_path);

        $this->getJson("/api/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertDownload('requirements.pdf');
    }

    public function test_upload_rejects_disallowed_file_types(): void
    {
        Storage::fake('local');
        $task = $this->createTask();

        $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_authenticated_user_can_delete_an_attachment(): void
    {
        $disk = Storage::fake('local');
        $task = $this->createTask();
        $path = 'tasks/' . $task->id . '/report.pdf';
        Storage::disk('local')->put($path, 'file contents');
        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'file_name' => 'report.pdf',
            'file_path' => $path,
            'file_size' => 13,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->deleteJson("/api/attachments/{$attachment->id}")->assertNoContent();

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        $disk->assertMissing($path);
    }

    public function test_attachment_endpoints_require_authentication(): void
    {
        $this->postJson('/api/tasks/1/attachments')->assertUnauthorized();
        $this->getJson('/api/attachments/1/download')->assertUnauthorized();
        $this->deleteJson('/api/attachments/1')->assertUnauthorized();
    }

    private function createTask(): Task
    {
        $user = User::factory()->create();
        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken($login->json('access_token'));

        return Task::create([
            'title' => 'Attachment test task',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $user->id,
        ]);
    }
}
