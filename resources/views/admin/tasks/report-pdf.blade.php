<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tugas</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #1e3a8a;
        }
        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
        }
        .stats-container {
            margin-bottom: 20px;
            width: 100%;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table td {
            padding: 8px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            text-align: center;
            width: 25%;
        }
        .stats-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            color: #475569;
        }
        .stats-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .tasks-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            text-transform: uppercase;
        }
        .tasks-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-pending { background-color: #e2e8f0; color: #475569; }
        .badge-in_progress { background-color: #dbeafe; color: #1e40af; }
        .badge-done { background-color: #dcfce7; color: #166534; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

        .badge-low { background-color: #dcfce7; color: #166534; }
        .badge-medium { background-color: #fef9c3; color: #854d0e; }
        .badge-high { background-color: #fee2e2; color: #991b1b; }

        .overdue-tag {
            color: #ef4444;
            font-weight: bold;
            font-size: 8px;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Daftar Tugas</h1>
        <p>Dicetak pada: {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB | Total Tugas: {{ $tasks->count() }}</p>
    </div>

    @php
        $pendingCount = $tasks->filter(fn($t) => $t->status === \App\Enums\TaskStatus::Pending)->count();
        $progressCount = $tasks->filter(fn($t) => $t->status === \App\Enums\TaskStatus::InProgress)->count();
        $doneCount = $tasks->filter(fn($t) => $t->status === \App\Enums\TaskStatus::Done)->count();
        $cancelledCount = $tasks->filter(fn($t) => $t->status === \App\Enums\TaskStatus::Cancelled)->count();
    @endphp

    <div class="stats-container">
        <table class="stats-table">
            <tr>
                <td>
                    <div class="stats-label">Menunggu</div>
                    <div class="stats-value">{{ $pendingCount }}</div>
                </td>
                <td>
                    <div class="stats-label">Dikerjakan</div>
                    <div class="stats-value">{{ $progressCount }}</div>
                </td>
                <td>
                    <div class="stats-label">Selesai</div>
                    <div class="stats-value">{{ $doneCount }}</div>
                </td>
                <td>
                    <div class="stats-label">Dibatalkan</div>
                    <div class="stats-value">{{ $cancelledCount }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="tasks-table">
        <thead>
            <tr>
                <th style="width: 25%;">Tugas</th>
                <th style="width: 15%;">Assignee</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 15%;">Prioritas</th>
                <th style="width: 15%;">Deadline</th>
                <th style="width: 15%;">Pembuat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
                <tr>
                    <td>
                        <div style="font-weight: bold; color: #1e293b;">{{ $task->title }}</div>
                        @if($task->description)
                            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ Str::limit($task->description, 50) }}</div>
                        @endif
                    </td>
                    <td>{{ $task->assignee?->name ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $task->status->value }}">
                            {{ $task->status->label() }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $task->priority->value }}">
                            {{ $task->priority->label() }}
                        </span>
                    </td>
                    <td>
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                        @if($task->is_overdue)
                            <div class="overdue-tag">TERLAMBAT</div>
                        @endif
                    </td>
                    <td>{{ $task->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">
                        Tidak ada tugas yang sesuai filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
