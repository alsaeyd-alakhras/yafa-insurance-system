<?php

namespace App\Exports\Reports;

use App\Reports\VisitsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitsReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    private Collection $rows;

    public function __construct(private readonly Request $request)
    {
        $this->rows = VisitsReport::rows($this->request);
    }

    public function collection()
    {
        if ($this->rows->isEmpty()) {
            return $this->rows;
        }

        $totals = VisitsReport::totals($this->request);

        return $this->rows->push([
            'visit_date' => 'الإجمالي',
            'patient_name' => '',
            'patient_type' => '',
            'employee_name' => '',
            'organization_center' => '',
            'organization_department' => '',
            'clinic_name' => '',
            'departments_detail' => '',
            'total_before_discount' => number_format($totals['total_before_discount'], 2),
            'total_after_discount' => number_format($totals['total_after_discount'], 2),
            'recorded_by_name' => '',
            'last_updated_by_name' => '',
        ]);
    }

    public function headings(): array
    {
        return VisitsReport::headings();
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
