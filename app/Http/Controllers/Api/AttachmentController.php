<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AttachmentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,mp4,webm',
            ],
        ]);

        $file = $validated['file'];
        $disk = Storage::disk('local');
        $path = $file->store("tasks/{$task->id}", 'local');

        if ($path === false) {
            return response()->json(['message' => 'File could not be stored.'], 500);
        }

        try {
            $attachment = TaskAttachment::create([
                'task_id' => $task->id,
                'file_name' => basename($file->getClientOriginalName()),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return response()->json([
            'data' => $attachment,
            'download_url' => route('api.attachments.download', $attachment),
        ], 201);
    }

    public function download(TaskAttachment $attachment): StreamedResponse
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($disk->exists($attachment->file_path), 404, 'Attachment file not found.');

        return $disk->download($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(TaskAttachment $attachment): JsonResponse
    {
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
