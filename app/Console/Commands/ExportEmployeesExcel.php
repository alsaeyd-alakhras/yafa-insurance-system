<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\SurveySubmission;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportEmployeesExcel extends Command
{
    protected $signature = 'export:employees {--path=employees_export.xlsx}';

    protected $description = 'تصدير جدول الموظفين كامل مع عدد التابعين وتاريخ تسجيل الاستبيان إلى ملف Excel';

    public function handle(): int
    {
        $path = $this->option('path');

        $surveyDates = SurveySubmission::whereNotNull('created_employee_id')
            ->where('status', 'approved')
            ->orderBy('created_at')
            ->get(['created_employee_id', 'created_at'])
            ->keyBy('created_employee_id');

        $employees = Employee::with(['organizationUnit', 'dependents', 'approvedBy'])
            ->withCount('dependents')
            ->orderBy('id')
            ->get();

        $genderMap = ['male' => 'ذكر', 'female' => 'أنثى'];
        $maritalMap = [
            'single' => 'أعزب',
            'married' => 'متزوج',
            'polygamous' => 'متعدد الزوجات',
            'widowed' => 'أرمل',
            'divorced' => 'مطلق',
        ];
        $sourceMap = ['survey' => 'استبيان', 'admin' => 'إضافة مباشرة'];

        $rows = $employees->map(function (Employee $emp) use ($genderMap, $maritalMap, $sourceMap, $surveyDates) {
            $surveySubmission = $surveyDates->get($emp->id);
            $chain = $emp->organizationUnit?->ancestryChain() ?? [];

            return [
                'id' => $emp->id,
                'full_name' => $emp->full_name,
                'national_id' => $emp->national_id,
                'gender' => $genderMap[$emp->gender] ?? $emp->gender,
                'marital_status' => $maritalMap[$emp->marital_status] ?? $emp->marital_status,
                'center' => $chain['center']->name ?? '',
                'department' => $chain['department']->name ?? '',
                'section' => $chain['section']->name ?? '',
                'source' => $sourceMap[$emp->source] ?? $emp->source,
                'dependents_count' => $emp->dependents_count,
                'approved_by' => $emp->approvedBy->name ?? '',
                'approved_at' => $emp->approved_at ? \Carbon\Carbon::parse($emp->approved_at)->format('Y-m-d H:i') : '',
                'survey_registered_at' => $surveySubmission?->created_at?->format('Y-m-d H:i') ?? '',
                'created_at' => $emp->created_at?->format('Y-m-d H:i'),
                'updated_at' => $emp->updated_at?->format('Y-m-d H:i'),
            ];
        });

        Excel::store(new class($rows) implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize {
            private $rows;

            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            public function collection()
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return [
                    'المعرف',
                    'الاسم الكامل',
                    'رقم الهوية',
                    'الجنس',
                    'الحالة الاجتماعية',
                    'المركزية',
                    'الدائرة',
                    'القسم',
                    'مصدر التسجيل',
                    'عدد التابعين',
                    'اعتُمد بواسطة',
                    'تاريخ الاعتماد',
                    'تاريخ تسجيل الاستبيان',
                    'تاريخ الإنشاء',
                    'تاريخ آخر تعديل',
                ];
            }

            public function styles(Worksheet $sheet)
            {
                $sheet->getRightToLeft(true);
                $sheet->getStyle('1:1')->getFont()->setBold(true);

                return [];
            }
        }, $path, 'local');

        $fullPath = storage_path('app/' . $path);
        $this->info("تم إنشاء الملف بنجاح: {$fullPath}");
        $this->info("عدد الموظفين المصدَّرين: {$employees->count()}");

        return self::SUCCESS;
    }
}
