<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\Reports\EmployeesReportExport;
use App\Exports\Reports\IncomeByDepartmentReportExport;
use App\Exports\Reports\VisitsReportExport;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Employee;
use App\Models\OrganizationUnit;
use App\Models\Visit;
use App\Reports\EmployeesReport;
use App\Reports\IncomeByDepartmentReport;
use App\Reports\VisitsReport;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('reports.view');

        return view('dashboard.reports.index');
    }

    public function employees(Request $request): JsonResponse|View
    {
        $this->authorize('reports.view');

        if ($request->ajax()) {
            $query = EmployeesReport::query($request);
            $this->applyEmployeesSort($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('organization_center', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['center']?->name ?? '-';
                })
                ->addColumn('organization_department', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['department']?->name ?? '-';
                })
                ->addColumn('organization_section', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['section']?->name ?? '-';
                })
                ->addColumn('approved_by_name', fn (Employee $employee) => $employee->approvedBy?->name ?? '-')
                ->addColumn('approved_at_formatted', fn (Employee $employee) => $employee->approved_at ? Carbon::parse($employee->approved_at)->format('Y-m-d H:i') : '-')
                ->addColumn('created_at_formatted', fn (Employee $employee) => $employee->created_at?->format('Y-m-d H:i') ?? '-')
                ->make(true);
        }

        return view('dashboard.reports.employees');
    }

    public function employeesSummary(Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(EmployeesReport::summary($request));
    }

    public function employeesFilterOptions(string $column, Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        abort_unless(in_array($column, EmployeesReport::FILTERABLE_COLUMNS, true), 404);

        return response()->json(EmployeesReport::filterOptions($column, $request));
    }

    public function employeesExportExcel(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الموظفين إلى Excel.', null, null);

        return Excel::download(new EmployeesReportExport($request), 'employees_report_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function employeesExportPdf(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الموظفين إلى PDF.', null, null);

        $filename = 'employees_report_' . now()->format('Y-m-d_His') . '.pdf';
        $rows = EmployeesReport::rows($request);

        return LaravelMpdf::loadView('dashboard.reports.pdf.employees', [
            'appName' => config('app.name', 'نظام بطاقات التأمين الطبي'),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'filtersDescription' => EmployeesReport::filtersDescription($request),
            'headings' => EmployeesReport::headings(),
            'rows' => $rows,
            'summary' => EmployeesReport::summary($request),
        ], [], [
            'orientation' => 'L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf'),
        ])->stream($filename);
    }

    public function visits(Request $request): JsonResponse|View
    {
        $this->authorize('reports.view');

        if ($request->ajax()) {
            $query = VisitsReport::query($request);
            $this->applyVisitsSort($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('patient_name', fn (Visit $visit) => $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name ?? '-')
                ->addColumn('patient_type_label', fn (Visit $visit) => $visit->patient_employee_id ? VisitsReport::PATIENT_TYPE_LABELS['employee'] : VisitsReport::PATIENT_TYPE_LABELS['dependent'])
                ->addColumn('employee_name', fn (Visit $visit) => $visit->employee?->full_name ?? '-')
                ->addColumn('clinic_name', fn (Visit $visit) => $visit->clinic?->name ?? '-')
                ->addColumn('departments_list', fn (Visit $visit) => $visit->visitDepartments->pluck('medicalDepartment.name')->filter()->map(fn ($name) => VisitsReport::DEPARTMENT_LABELS[$name] ?? $name)->implode('، ') ?: '-')
                ->addColumn('total_before_discount_formatted', fn (Visit $visit) => $visit->total_before_discount !== null ? number_format((float) $visit->total_before_discount, 2) : '-')
                ->addColumn('total_after_discount_formatted', fn (Visit $visit) => $visit->total_after_discount !== null ? number_format((float) $visit->total_after_discount, 2) : '-')
                ->addColumn('recorded_by_name', fn (Visit $visit) => $visit->recordedBy?->name ?? '-')
                ->addColumn('organization_center', fn (Visit $visit) => $visit->employee?->organizationUnit?->ancestryChain()['center']?->name ?? '-')
                ->addColumn('organization_department', fn (Visit $visit) => $visit->employee?->organizationUnit?->ancestryChain()['department']?->name ?? '-')
                ->make(true);
        }

        return view('dashboard.reports.visits');
    }

    public function visitsSummary(Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(VisitsReport::summary($request));
    }

    public function visitsFilterOptions(string $column, Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        abort_unless(in_array($column, VisitsReport::FILTERABLE_COLUMNS, true), 404);

        return response()->json(VisitsReport::filterOptions($column, $request));
    }

    public function income(Request $request): JsonResponse|View
    {
        $this->authorize('reports.view');

        if ($request->ajax()) {
            $query = IncomeByDepartmentReport::query($request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('department_name', fn ($department) => IncomeByDepartmentReport::DEPARTMENT_LABELS[$department->department_name] ?? $department->department_name)
                ->editColumn('visits_count', fn ($department) => (int) $department->visits_count)
                ->addColumn('total_before_formatted', fn ($department) => number_format((float) $department->total_before, 2))
                ->addColumn('total_after_formatted', fn ($department) => number_format((float) $department->total_after, 2))
                ->addColumn('total_discount_formatted', fn ($department) => number_format((float) $department->total_discount, 2))
                ->addColumn('avg_discount_percentage', function ($department) {
                    $totalBefore = (float) $department->total_before;

                    return $totalBefore > 0 ? number_format(((float) $department->total_discount / $totalBefore) * 100, 1) : '-';
                })
                ->make(true);
        }

        return view('dashboard.reports.income', [
            'departmentLabels' => IncomeByDepartmentReport::DEPARTMENT_LABELS,
            'clinics' => Clinic::query()->orderBy('name')->pluck('name'),
            'organizationCenters' => OrganizationUnit::where('level', 1)->orderBy('name')->pluck('name'),
            'organizationDepartments' => OrganizationUnit::where('level', 2)->orderBy('name')->pluck('name'),
        ]);
    }

    public function incomeSummary(Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(IncomeByDepartmentReport::summary($request));
    }

    public function incomeFilterOptions(string $column, Request $request): JsonResponse
    {
        $this->authorize('reports.view');

        abort_unless(in_array($column, IncomeByDepartmentReport::FILTERABLE_COLUMNS, true), 404);

        return response()->json(IncomeByDepartmentReport::filterOptions($column, $request));
    }

    public function visitsExportExcel(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الزيارات إلى Excel.', null, null);

        return Excel::download(new VisitsReportExport($request), 'visits_report_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function visitsExportPdf(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الزيارات إلى PDF.', null, null);

        $filename = 'visits_report_' . now()->format('Y-m-d_His') . '.pdf';
        $rows = VisitsReport::rows($request);

        return LaravelMpdf::loadView('dashboard.reports.pdf.visits', [
            'appName' => config('app.name', 'نظام بطاقات التأمين الطبي'),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'filtersDescription' => VisitsReport::filtersDescription($request),
            'headings' => VisitsReport::headings(),
            'rows' => $rows,
            'summary' => VisitsReport::summary($request),
        ], [], [
            'orientation' => 'L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf'),
        ])->stream($filename);
    }

    public function incomeExportExcel(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الدخل حسب القسم الطبي إلى Excel.', null, null);

        return Excel::download(new IncomeByDepartmentReportExport($request), 'income_by_department_report_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function incomeExportPdf(Request $request)
    {
        $this->authorize('reports.view');

        ActivityLogService::log('Exported', 'Report', 'تم تصدير تقرير الدخل حسب القسم الطبي إلى PDF.', null, null);

        $filename = 'income_by_department_report_' . now()->format('Y-m-d_His') . '.pdf';
        $rows = IncomeByDepartmentReport::rows($request);

        return LaravelMpdf::loadView('dashboard.reports.pdf.income', [
            'appName' => config('app.name', 'نظام بطاقات التأمين الطبي'),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'filtersDescription' => IncomeByDepartmentReport::filtersDescription($request),
            'headings' => IncomeByDepartmentReport::headings(),
            'rows' => $rows,
            'summary' => IncomeByDepartmentReport::summary($request),
        ], [], [
            'orientation' => 'L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf'),
        ])->stream($filename);
    }

    private function applyEmployeesSort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['id', 'full_name', 'national_id', 'status', 'gender', 'marital_status', 'source', 'approved_at', 'created_at'], true)) {
            $query->orderBy('id');

            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }

    private function applyVisitsSort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['visit_date'], true)) {
            $query->orderBy('visit_date');

            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }
}
