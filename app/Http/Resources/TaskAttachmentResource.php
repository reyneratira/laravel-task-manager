<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,
            'is_image' => $this->is_image,
            'uploader' => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ],
            'download_url' => route('attachments.download', $this->id),
            'preview_url' => $this->is_image ? route('attachments.preview', $this->id) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
