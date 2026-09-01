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
        return $this->rows;
    }

    public function headings(): array
    {
        return VisitsReport::headings();
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRightToLeft(true);
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        return [];
    }
}
