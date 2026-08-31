<?php

namespace App\Console\Commands;

use App\Exports\EmployeesExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportEmployeesExcel extends Command
{
    protected $signature = 'export:employees {--path=employees_export.xlsx}';

    protected $description = 'تصدير جدول الموظفين كامل مع عدد التابعين وتاريخ تسجيل الاستبيان إلى ملف Excel';

    public function handle(): int
    {
        $path = $this->option('path');
        $export = new EmployeesExport();

        Excel::store($export, $path, 'local');

        $fullPath = storage_path('app/' . $path);
        $this->info("تم إنشاء الملف بنجاح: {$fullPath}");
        $this->info("عدد الموظفين المصدَّرين: {$export->count()}");

        return self::SUCCESS;
    }
}
