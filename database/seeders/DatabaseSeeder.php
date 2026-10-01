<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sampleUsers = [
            ['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'password' => 'admin123', 'role' => 'admin'],
            ['name' => 'Avery Johnson', 'email' => 'avery@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Jordan Lee', 'email' => 'jordan@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Morgan Chen', 'email' => 'morgan@example.com', 'password' => 'password', 'role' => 'user'],
        ];

        $usersByEmail = [];

        foreach ($sampleUsers as $sampleUser) {
            $email = $sampleUser['email'];
            $usersByEmail[$email] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $sampleUser['name'],
                    'password' => $sampleUser['password'],
                    'role' => $sampleUser['role'],
                ]
            );
        }

        $statuses = ['pending', 'in_progress', 'completed'];
        $priorities = ['low', 'medium', 'high'];
        $userIds = array_map(fn(User $user) => $user->id, array_values($usersByEmail));
        $tasksByNumber = [];

        for ($number = 1; $number <= 15; $number++) {
            $title = sprintf('Sample task %02d', $number);
            $userIndex = ($number - 1) % count($userIds);

            $tasksByNumber[$number] = Task::updateOrCreate(
                ['title' => $title],
                [
                    'description' => "Sample task {$number} for the task management platform.",
                    'status' => $statuses[($number - 1) % count($statuses)],
                    'priority' => $priorities[($number - 1) % count($priorities)],
                    'assigned_user_id' => $userIds[$userIndex],
                    'created_by' => $usersByEmail['admin@gmail.com']->id,
                    'due_date' => now()->addDays($number),
                ]
            );
        }

        foreach (range(1, 10) as $number) {
            $task = $tasksByNumber[$number];
            $user = array_values($usersByEmail)[($number - 1) % count($usersByEmail)];
            $comment = "Sample comment {$number} for {$task->title}.";

            DB::table('task_comments')->updateOrInsert(
                [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'comment' => $comment,
                ],
                ['created_at' => now()]
            );
        }
    }
}
