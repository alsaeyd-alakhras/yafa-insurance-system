<x-front-layout :title="'التقارير'">
    <div class="mb-4">
        <h4 class="mb-0">التقارير</h4>
        <p class="text-muted mb-0 mt-2">اختر نوع التقرير لعرض البيانات وتصفيتها، مع إمكانية التصدير إلى Excel أو PDF.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.employees') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none report-hub-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar avatar-initial rounded bg-label-primary">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <i class="fa-solid fa-arrow-left text-muted small"></i>
                    </div>
                    <h5 class="mb-1">تقرير الموظفين</h5>
                    <p class="text-muted small mb-0">ملخص وبيانات الموظفين حسب الفلاتر: الحالة، الوحدة التنظيمية، التابعون وغيرها.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.visits') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none report-hub-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar avatar-initial rounded bg-label-info">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                        <i class="fa-solid fa-arrow-left text-muted small"></i>
                    </div>
                    <h5 class="mb-1">تقرير الزيارات</h5>
                    <p class="text-muted small mb-0">ملخص وبيانات الزيارات حسب الفلاتر: التاريخ، المريض، العيادة والأقسام الطبية.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('dashboard.reports.income') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none report-hub-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar avatar-initial rounded bg-label-success">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <i class="fa-solid fa-arrow-left text-muted small"></i>
                    </div>
                    <h5 class="mb-1">تقرير الدخل حسب القسم الطبي</h5>
                    <p class="text-muted small mb-0">ملخص الدخل والخصومات لكل قسم طبي، بناءً على الزيارات التي أُدخل لها مبلغ.</p>
                </div>
            </a>
        </div>
    </div>

    <style>
        .report-hub-card {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .report-hub-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.5rem rgba(67, 89, 113, .12) !important;
        }
    </style>
</x-front-layout>
