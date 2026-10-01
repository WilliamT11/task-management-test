<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $hidden = [
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
