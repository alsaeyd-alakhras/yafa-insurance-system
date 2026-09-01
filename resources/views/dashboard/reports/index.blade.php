<x-front-layout :title="'التقارير'">
    <div class="row g-4">
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.employees') }}" class="text-decoration-none text-body">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-users"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">تقرير الموظفين</h5>
                            <p class="mb-0 text-muted">ملخص وبيانات الموظفين حسب الفلاتر</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.visits') }}" class="text-decoration-none text-body">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="fa-solid fa-notes-medical"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">تقرير الزيارات</h5>
                            <p class="mb-0 text-muted">ملخص وبيانات الزيارات حسب الفلاتر</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.income') }}" class="text-decoration-none text-body">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">تقرير الدخل حسب القسم الطبي</h5>
                            <p class="mb-0 text-muted">ملخص الدخل والخصومات حسب القسم الطبي</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-front-layout>
