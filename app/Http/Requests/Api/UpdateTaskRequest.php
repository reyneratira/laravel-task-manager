<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = array_column(TaskStatus::cases(), 'value');
        $priorityValues = array_column(TaskPriority::cases(), 'value');

        // Admin bisa update semua field, user hanya status
        if ($this->user()->isAdmin()) {
            return [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'status' => ['sometimes', 'required', Rule::in($statusValues)],
                'priority' => ['sometimes', 'required', Rule::in($priorityValues)],
                'due_date' => ['nullable', 'date', 'after_or_equal:today'],
                'assigned_to' => ['nullable', 'exists:users,id'],
            ];
        }

        // User biasa: hanya boleh update status, dan tidak bisa set 'cancelled'
        $allowedStatuses = array_filter(
            $statusValues,
            fn($s) => $s !== TaskStatus::Cancelled->value
        );

        return [
            'status' => ['required', Rule::in($allowedStatuses)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Judul maksimal 255 karakter.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status tidak valid.',
            'priority.in' => 'Prioritas tidak valid.',
            'due_date.after_or_equal' => 'Tanggal deadline tidak boleh di masa lalu.',
            'assigned_to.exists' => 'User yang dipilih tidak ditemukan.',
        ];
    }
}
