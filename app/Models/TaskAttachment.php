<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    protected $appends = [
        'formatted_size',
        'is_image',
    ];

    /**
     * Relasi ke Task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relasi ke User (uploader).
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Accessor untuk format ukuran file.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Accessor untuk cek apakah file adalah gambar.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
