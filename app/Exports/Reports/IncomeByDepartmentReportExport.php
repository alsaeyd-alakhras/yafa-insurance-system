<?php

namespace App\Exports\Reports;

use App\Reports\IncomeByDepartmentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeByDepartmentReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    private Collection $rows;

    public function __construct(private readonly Request $request)
    {
        $this->rows = IncomeByDepartmentReport::rows($this->request);
    }

    public function collection()
    {
        if ($this->rows->isEmpty()) {
            return $this->rows;
        }

        $totals = IncomeByDepartmentReport::totals($this->request);

        return $this->rows->push([
            'department_name' => 'الإجمالي',
            'visits_count' => $this->rows->sum('visits_count'),
            'total_before' => number_format($totals['total_before'], 2),
            'total_after' => number_format($totals['total_after'], 2),
            'total_discount' => number_format($totals['total_discount'], 2),
            'avg_discount_percentage' => '',
            'current_discount_percentage' => '',
            'current_max_discount_amount' => '',
        ]);
    }

    public function headings(): array
    {
        return IncomeByDepartmentReport::headings();
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRightToLeft(true);
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        if ($this->rows->isNotEmpty()) {
            $lastRow = $this->rows->count() + 1;
            $sheet->getStyle($lastRow . ':' . $lastRow)->getFont()->setBold(true);
        }

        return [];
    }
}
