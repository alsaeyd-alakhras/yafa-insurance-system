<x-front-layout :title="'تقرير الزيارات'">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard.reports.index') }}" class="text-muted" title="رجوع للتقارير">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <h4 class="mb-0">تقرير الزيارات</h4>
        </div>
        <p class="text-muted mb-0 mt-2">حدّد الفلاتر المطلوبة ثم اضغط "عرض النتائج" لعرض التقرير. افتراضياً يشمل التقرير زيارات الشهر الحالي إذا لم يُحدَّد مدى تاريخ.</p>
    </div>

    <form method="GET" action="{{ route('dashboard.reports.visits') }}" class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-4 pb-4 border-bottom">
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-calendar-days text-primary ms-2"></i>
                    <h6 class="mb-0 text-primary">الفترة الزمنية</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الزيارة من</label>
                        <input type="date" name="visit_from" class="form-control">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الزيارة إلى</label>
                        <input type="date" name="visit_to" class="form-control">
                    </div>
                </div>
            </div>

            <div class="mb-4 pb-4 border-bottom">
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-stethoscope text-primary ms-2"></i>
                    <h6 class="mb-0 text-primary">بيانات الزيارة والمريض</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">نوع المريض</label>
                        <select name="patient_type[]" class="form-select select2-searchable" multiple data-placeholder="الكل">
                            <option value="موظف">موظف</option>
                            <option value="تابع">تابع</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">العيادة</label>
                        <select name="clinic_name[]" class="form-select select2-searchable" multiple data-placeholder="كل العيادات">
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic }}">{{ $clinic }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الأقسام الطبية</label>
                        <select name="departments_list[]" class="form-select select2-searchable" multiple data-placeholder="كل الأقسام">
                            @foreach ($departmentLabels as $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-sitemap text-primary ms-2"></i>
                    <h6 class="mb-0 text-primary">الهيكل التنظيمي</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المركزية</label>
                        <select name="organization_center[]" class="form-select select2-searchable" multiple data-placeholder="كل المركزيات">
                            @foreach ($organizationCenters as $center)
                                <option value="{{ $center }}">{{ $center }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الدائرة</label>
                        <select name="organization_department[]" class="form-select select2-searchable" multiple data-placeholder="كل الدوائر">
                            @foreach ($organizationDepartments as $department)
                                <option value="{{ $department }}">{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <span class="text-muted small">اترك الفلاتر فارغة لعرض زيارات الشهر الحالي — يمكن البحث باسم المريض/الموظف من داخل صفحة النتائج.</span>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-eraser me-1"></i> مسح
                </button>
                <button type="submit" class="btn btn-outline-danger" formtarget="_blank" formaction="{{ route('dashboard.reports.visits.export-pdf') }}">
                    <i class="fa-solid fa-file-pdf me-1"></i> تصدير PDF
                </button>
                <button type="submit" class="btn btn-outline-success" formtarget="_blank" formaction="{{ route('dashboard.reports.visits.export-excel') }}">
                    <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> عرض النتائج
                </button>
            </div>
        </div>
    </form>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}">
        <link rel="stylesheet" href="{{ asset('css/searchable-select.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/vendor/libs/select2/select2.full.min.js') }}"></script>
        <script src="{{ asset('js/searchable-select.js') }}"></script>
    @endpush
</x-front-layout>
