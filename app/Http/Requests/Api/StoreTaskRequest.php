<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = array_column(TaskStatus::cases(), 'value');
        $priorityValues = array_column(TaskPriority::cases(), 'value');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in($statusValues)],
            'priority' => ['required', Rule::in($priorityValues)],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul tugas wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status tidak valid.',
            'priority.required' => 'Prioritas wajib diisi.',
            'priority.in' => 'Prioritas tidak valid.',
            'due_date.after_or_equal' => 'Tanggal deadline tidak boleh di masa lalu.',
            'assigned_to.exists' => 'User yang dipilih tidak ditemukan.',
        ];
    }
}
