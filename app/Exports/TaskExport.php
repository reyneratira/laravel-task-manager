<?php

namespace App\Exports;

use App\Models\Task;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TaskExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    /**
     * Create a new export instance with query filters.
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build the query for exporting tasks with filters.
     */
    public function query()
    {
        return Task::query()
            ->with(['assignee', 'creator'])
            ->when(isset($this->filters['status']) && $this->filters['status'], function ($q) {
                return $q->byStatus(TaskStatus::from($this->filters['status']));
            })
            ->when(isset($this->filters['priority']) && $this->filters['priority'], function ($q) {
                return $q->byPriority(TaskPriority::from($this->filters['priority']));
            })
            ->when(isset($this->filters['user_id']) && $this->filters['user_id'], function ($q) {
                return $q->forUser($this->filters['user_id']);
            })
            ->when(isset($this->filters['search']) && $this->filters['search'], function ($q) {
                return $q->where('title', 'like', '%' . $this->filters['search'] . '%');
            })
            ->latest();
    }

    /**
     * Define the column headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Judul',
            'Deskripsi',
            'Status',
            'Prioritas',
            'Deadline',
            'Assignee',
            'Pembuat',
            'Tanggal Dibuat',
        ];
    }

    /**
     * Map each row of the query results.
     */
    public function map($task): array
    {
        return [
            $task->id,
            $task->title,
            $task->description ?? '—',
            $task->status ? $task->status->label() : '—',
            $task->priority ? $task->priority->label() : '—',
            $task->due_date ? $task->due_date->format('d M Y') : '—',
            $task->assignee?->name ?? '—',
            $task->creator?->name ?? '—',
            $task->created_at ? $task->created_at->format('d M Y H:i') : '—',
        ];
    }
}
