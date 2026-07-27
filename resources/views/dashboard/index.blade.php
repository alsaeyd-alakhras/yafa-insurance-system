@php
    $roleLabels = [
        'admin' => 'أدمن النظام',
        'receptionist' => 'موظف استقبال',
        'super_admin' => 'مدير النظام',
    ];
@endphp

<x-front-layout :title="'الرئيسية'">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="mb-0">مرحباً، {{ auth()->user()?->name }}</h4>
            <span class="badge bg-label-primary">{{ $roleLabels[$role] ?? 'مستخدم' }}</span>
        </div>
        <p class="text-muted mb-0 mt-2">ملخص تشغيلي سريع لأهم الأعمال الحالية.</p>
    </div>

    <div class="row g-3 mb-4">
        @can('view', 'App\Models\Visit')
            <div class="col-md-6 col-xl">
                <a href="{{ route('dashboard.visits.index') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="avatar avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-calendar-day"></i>
                            </div>
                            <i class="fa-solid fa-arrow-left text-muted small"></i>
                        </div>
                        <div class="fs-3 fw-bold mb-1">{{ $visitsToday }}</div>
                        <div class="text-muted small">زيارات اليوم</div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl">
                <a href="{{ route('dashboard.visits.index') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="avatar avatar-initial rounded bg-label-info">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <i class="fa-solid fa-arrow-left text-muted small"></i>
                        </div>
                        <div class="fs-3 fw-bold mb-1">{{ $visitsThisMonth }}</div>
                        <div class="text-muted small">زيارات هذا الشهر</div>
                    </div>
                </a>
            </div>
        @endcan

        @can('view', 'App\Models\SurveySubmission')
            <div class="col-md-6 col-xl">
                <a href="{{ route('dashboard.survey-submissions.index') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="avatar avatar-initial rounded bg-label-warning">
                                <i class="fa-solid fa-file-circle-question"></i>
                            </div>
                            <i class="fa-solid fa-arrow-left text-muted small"></i>
                        </div>
                        <div class="fs-3 fw-bold mb-1">{{ $pendingSurveySubmissions }}</div>
                        <div class="text-muted small">طلبات استبيان قيد المراجعة</div>
                    </div>
                </a>
            </div>
        @endcan

        @can('view', 'App\Models\Employee')
            <div class="col-md-6 col-xl">
                <a href="{{ route('dashboard.employees.index') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="avatar avatar-initial rounded bg-label-danger">
                                <i class="fa-solid fa-user-clock"></i>
                            </div>
                            <i class="fa-solid fa-arrow-left text-muted small"></i>
                        </div>
                        <div class="fs-3 fw-bold mb-1">{{ $pendingEmployees }}</div>
                        <div class="text-muted small">موظفون قيد الموافقة</div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl">
                <a href="{{ route('dashboard.employees.index') }}" class="card h-100 border-0 shadow-sm text-body text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="avatar avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <i class="fa-solid fa-arrow-left text-muted small"></i>
                        </div>
                        <div class="fs-3 fw-bold mb-1">{{ $activeEmployeesCount }}</div>
                        <div class="text-muted small">إجمالي الموظفين النشطين</div>
                    </div>
                </a>
            </div>
        @endcan
    </div>

    @can('update', 'App\Models\SurveySubmission')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-semibold">نافذة الاستبيان:</span>
                    <span class="badge {{ $surveyWindowOpen ? 'bg-label-success' : 'bg-label-secondary' }}">
                        {{ $surveyWindowOpen ? 'مفتوحة حالياً' : 'مغلقة حالياً' }}
                    </span>
                </div>
                <a href="{{ route('dashboard.survey-submissions.index') }}" class="btn btn-sm btn-outline-primary">
                    إدارة النافذة
                </a>
            </div>
        </div>
    @endcan

    @if (
        auth()->user()?->can('create', 'App\Models\Employee')
        || auth()->user()?->can('view', 'App\Models\Employee')
        || auth()->user()?->can('view', 'App\Models\Visit')
        || auth()->user()?->can('view', 'App\Models\SurveySubmission')
        || auth()->user()?->can('view', 'App\Models\OrganizationUnit')
        || auth()->user()?->can('view', 'App\Models\User')
    )
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">إجراءات سريعة</h5>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                @can('create', 'App\Models\Employee')
                    <a href="{{ route('dashboard.employees.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus me-1"></i> إضافة موظف
                    </a>
                @endcan

                @can('view', 'App\Models\Employee')
                    <a href="{{ route('dashboard.employees.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-users me-1"></i> عرض الموظفين
                    </a>
                @endcan

                @can('view', 'App\Models\Visit')
                    <a href="{{ route('dashboard.visits.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-notes-medical me-1"></i> الزيارات
                    </a>
                @endcan

                @can('view', 'App\Models\SurveySubmission')
                    <a href="{{ route('dashboard.survey-submissions.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-file-circle-question me-1"></i> طلبات الاستبيان
                    </a>
                @endcan

                @can('view', 'App\Models\OrganizationUnit')
                    <a href="{{ route('dashboard.organization-units.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-sitemap me-1"></i> الوحدات التنظيمية
                    </a>
                @endcan

                @can('view', 'App\Models\User')
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-user-gear me-1"></i> المستخدمون
                    </a>
                @endcan
            </div>
        </div>
    @endif

    @can('view', 'App\Models\ActivityLog')
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">آخر النشاطات</h5>
                <span class="text-muted small">أحدث {{ $recentActivity->count() }} عمليات</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>النشاط</th>
                            <th>المنفذ</th>
                            <th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentActivity as $activity)
                            @php
                                $color = 'primary';
                                $type = $activity->event_type;

                                if ($activity->event_type == 'Created') {
                                    $color = 'success';
                                    $type = 'اضافة';
                                } elseif ($activity->event_type == 'Updated') {
                                    $color = 'info';
                                    $type = 'تعديل';
                                } elseif ($activity->event_type == 'Deleted') {
                                    $color = 'danger';
                                    $type = 'حذف';
                                } elseif ($activity->event_type == 'Login') {
                                    $color = 'success';
                                    $type = 'تسجيل دخول';
                                } elseif ($activity->event_type == 'Access Denied') {
                                    $color = 'warning';
                                    $type = 'التحقق من صلاحيات الوصول';
                                }
                            @endphp
                            <tr>
                                <td><span class="badge bg-label-{{ $color }}">{{ $type }}</span></td>
                                <td class="small">{{ $activity->message }}</td>
                                <td class="small text-muted">{{ $activity->user_name ?: 'النظام' }}</td>
                                <td class="small text-muted text-nowrap">
                                    {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد نشاطات مسجلة بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan
</x-front-layout>
