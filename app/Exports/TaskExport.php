<?php

namespace App\Exports;

use App\Models\Task;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TaskExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    use Exportable;

    private const COLOR_MAPS = [
        'gray' => [
            'bg' => 'F3F4F6',
            'text' => '374151',
        ],
        'blue' => [
            'bg' => 'DBEAFE',
            'text' => '1D4ED8',
        ],
        'green' => [
            'bg' => 'D1FAE5',
            'text' => '047857',
        ],
        'red' => [
            'bg' => 'FEE2E2',
            'text' => 'B91C1C',
        ],
        'yellow' => [
            'bg' => 'FEF3C7',
            'text' => 'B45309',
        ],
    ];

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

    public function map($task): array
    {
        $title = $task->title;
        if ($task->is_overdue) {
            $title = $title . ' (TERLAMBAT)';
        }

        return [
            $task->id,
            $title,
            $task->description ?? '—',
            $task->status ? $task->status->label() : '—',
            $task->priority ? $task->priority->label() : '—',
            $task->due_date ? $task->due_date->format('d M Y') : '—',
            $task->assignee?->name ?? '—',
            $task->creator?->name ?? '—',
            $task->created_at ? $task->created_at->format('d M Y H:i') : '—',
        ];
    }

    /**
     * Style the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        // Force gridlines to show
        $sheet->setShowGridlines(true);

        return [
            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '1B365D'], // Deep Navy Blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Set header row height
                $sheet->getRowDimension(1)->setRowHeight(28);

                if ($highestRow > 1) {
                    // Set wrap text for Judul and Deskripsi columns to make it readable
                    $sheet->getStyle('B2:B' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('C2:C' . $highestRow)->getAlignment()->setWrapText(true);

                    // Calculate max length of descriptions to set column C width capped at 80
                    $maxDescLength = 0;
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $descValue = $sheet->getCell("C{$row}")->getValue();
                        if ($descValue) {
                            $maxDescLength = max($maxDescLength, strlen($descValue));
                        }
                    }
                    $calculatedWidth = max(15, $maxDescLength + 5);
                    $finalWidth = min($calculatedWidth, 80);

                    $sheet->getColumnDimension('C')->setAutoSize(false);
                    $sheet->getColumnDimension('C')->setWidth($finalWidth);

                    // Helper to get status color mapping
                    $getStatusColors = function ($label) {
                        return match ($label) {
                            'Menunggu' => self::COLOR_MAPS['gray'],
                            'Sedang Dikerjakan' => self::COLOR_MAPS['blue'],
                            'Selesai' => self::COLOR_MAPS['green'],
                            'Dibatalkan' => self::COLOR_MAPS['red'],
                            default => null,
                        };
                    };

                    // Helper to get priority color mapping
                    $getPriorityColors = function ($label) {
                        return match ($label) {
                            'Rendah' => self::COLOR_MAPS['green'],
                            'Sedang' => self::COLOR_MAPS['yellow'],
                            'Tinggi' => self::COLOR_MAPS['red'],
                            default => null,
                        };
                    };

                    // Add thin gray borders, zebra striping, automatic row height, and badge colors for data cells
                    for ($row = 2; $row <= $highestRow; $row++) {
                        // Set automatic row height so wrapped text is fully visible
                        $sheet->getRowDimension($row)->setRowHeight(-1);

                        // Zebra striping for even rows
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('F9FAFB');
                        }

                        // Apply thin border to all cells in the row
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getBorders()
                            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                            ->getColor()->setARGB('E5E7EB');

                        // Apply status cell color
                        $statusValue = $sheet->getCell("D{$row}")->getValue();
                        if ($statusValue && $colors = $getStatusColors($statusValue)) {
                            $sheet->getStyle("D{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['argb' => $colors['text']],
                                    'bold' => true,
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $colors['bg']],
                                ],
                            ]);
                        }

                        // Apply priority cell color
                        $priorityValue = $sheet->getCell("E{$row}")->getValue();
                        if ($priorityValue && $colors = $getPriorityColors($priorityValue)) {
                            $sheet->getStyle("E{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['argb' => $colors['text']],
                                    'bold' => true,
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $colors['bg']],
                                ],
                            ]);
                        }

                        // Apply red color to Judul (Column B) if the task is late
                        $judulValue = $sheet->getCell("B{$row}")->getValue();
                        if ($judulValue && str_contains($judulValue, '(TERLAMBAT)')) {
                            $sheet->getStyle("B{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['argb' => 'B91C1C'],
                                ],
                            ]);
                        }
                    }

                    // Column Alignments
                    // Center align: ID (A), Status (D), Prioritas (E), Deadline (F), Tanggal Dibuat (I)
                    $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I2:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Left align: Judul (B), Deskripsi (C), Assignee (G), Pembuat (H)
                    $sheet->getStyle('B2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('C2:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('G2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('H2:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Apply vertical center alignment for all cells
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
