<x-front-layout :title="'تقرير الموظفين'">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard.reports.index') }}" class="text-muted" title="رجوع للتقارير">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <h4 class="mb-0">تقرير الموظفين</h4>
        </div>
        <p class="text-muted mb-0 mt-2">حدّد الفلاتر المطلوبة ثم اضغط "عرض النتائج" لعرض التقرير.</p>
    </div>

    <form method="GET" action="{{ route('dashboard.reports.employees') }}" class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-4 pb-4 border-bottom">
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-list-check text-primary ms-2"></i>
                    <h6 class="mb-0 text-primary">الحالة والتصنيف</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status[]" class="form-select select2-searchable" multiple data-placeholder="كل الحالات">
                            <option value="active">نشط</option>
                            <option value="pending">قيد الموافقة</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنس</label>
                        <select name="gender[]" class="form-select select2-searchable" multiple data-placeholder="الكل">
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مصدر التسجيل</label>
                        <select name="source[]" class="form-select select2-searchable" multiple data-placeholder="الكل">
                            <option value="survey">استبيان</option>
                            <option value="admin">إضافة مباشرة</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-4 pb-4 border-bottom">
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

            <div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-calendar-days text-primary ms-2"></i>
                    <h6 class="mb-0 text-primary">الفترات الزمنية</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الإنشاء من</label>
                        <input type="date" name="created_from" class="form-control">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الإنشاء إلى</label>
                        <input type="date" name="created_to" class="form-control">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الاعتماد من</label>
                        <input type="date" name="approved_from" class="form-control">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">تاريخ الاعتماد إلى</label>
                        <input type="date" name="approved_to" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <span class="text-muted small">يمكن ترك كل الفلاتر فارغة لعرض جميع الموظفين.</span>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-eraser me-1"></i> مسح
                </button>
                <button type="submit" class="btn btn-outline-danger" formtarget="_blank" formaction="{{ route('dashboard.reports.employees.export-pdf') }}">
                    <i class="fa-solid fa-file-pdf me-1"></i> تصدير PDF
                </button>
                <button type="submit" class="btn btn-outline-success" formtarget="_blank" formaction="{{ route('dashboard.reports.employees.export-excel') }}">
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
