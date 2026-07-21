<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo"  style="overflow: visible">
        <a href="{{ route('dashboard.home') }}" class="app-brand-link">
            <span class="app-brand-logo demo" style="overflow: visible">
                <img src=" {{ asset('imgs/logo-brand.png') }}" alt="Logo" width="60">
            </span>
            {{-- <span class="app-brand-text demo menu-text fw-bold">{{ $title }}</span> --}}
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="align-middle ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="align-middle ti ti-x d-block d-xl-none ti-md"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="py-1 menu-inner">
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Apps &amp; Pages">العامة</span>
        </li>
        <!-- Page -->
        <li class="menu-item {{ request()->is('/') ? 'active' : '' }}">
            <a href="{{ route('dashboard.home') }}" class="menu-link">
                <i class="fa-solid fa-house me-2"></i>
                <div data-i18n="home">الرئيسية</div>
            </a>
        </li>
        @can('view', 'App\Models\Visit')
        <li class="menu-item {{ request()->is('visits*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.visits.index') }}" class="menu-link">
                <i class="fa-solid fa-notes-medical me-2"></i>
                <div data-i18n="visits">الزيارات</div>
            </a>
        </li>
        @endcan
        @can('view', 'App\Models\Employee')
        <li class="menu-item {{ request()->is('employees*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.employees.index') }}" class="menu-link">
                <i class="fa-solid fa-users me-2"></i>
                <div data-i18n="employees">الموظفون والتابعون</div>
            </a>
        </li>
        @endcan
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Apps &amp; Pages">البيانات الأساسية</span>
        </li>
        @can('view', 'App\Models\OrganizationUnit')
        <li class="menu-item {{ request()->is('organization-units*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.organization-units.index') }}" class="menu-link">
                <i class="fa-solid fa-sitemap me-2"></i>
                <div data-i18n="organization_units">الوحدات التنظيمية</div>
            </a>
        </li>
        @endcan
        @can('view', 'App\Models\MedicalDepartment')
        <li class="menu-item {{ request()->is('medical-departments*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.medical-departments.index') }}" class="menu-link">
                <i class="fa-solid fa-hospital me-2"></i>
                <div data-i18n="medical_departments">الأقسام الطبية</div>
            </a>
        </li>
        @endcan
        @can('view', 'App\Models\SurveySubmission')
        <li class="menu-item {{ request()->is('survey-submissions*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.survey-submissions.index') }}" class="menu-link">
                <i class="fa-solid fa-file-circle-question me-2"></i>
                <div data-i18n="survey_submissions">طلبات الاستبيان</div>
            </a>
        </li>
        @endcan
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Apps &amp; Pages">إدارة النظام</span>
        </li>
        @can('view', 'App\Models\User')
        <li class="menu-item {{ request()->is('users*') ? 'active' : '' }}">
            <a href="{{ route('dashboard.users.index') }}" class="menu-link">
                <i class="fa-solid fa-user-gear me-2"></i>
                <div data-i18n="users">إدارة المستخدمين</div>
            </a>
        </li>
        @endcan
        @if (
            auth()->user()?->can('view', 'App\Models\Constant')
            || auth()->user()?->can('view', 'App\Models\ActivityLog')
        )
        <li class="menu-item {{ request()->is('constants*') || request()->is('logs*') ? 'active open' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="fa-solid fa-gear me-2"></i>
                <div data-i18n="settings">الإعدادات</div>
            </a>
            <ul class="menu-sub">
                @can('view', 'App\Models\Constant')
                <li class="menu-item {{ request()->is('constants*') ? 'active' : '' }}">
                    <a href="{{ route('dashboard.constants.index') }}" class="menu-link">
                        <i class="fa-solid fa-sliders me-2"></i>
                        <div data-i18n="constants">ثوابت النظام</div>
                    </a>
                </li>
                @endcan
                @can('view', 'App\Models\ActivityLog')
                <li class="menu-item {{ request()->is('logs*') ? 'active' : '' }}">
                    <a href="{{ route('dashboard.logs.index') }}" class="menu-link">
                        <i class="fa-solid fa-calendar-days me-2"></i>
                        <div data-i18n="logs">الأحداث</div>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endif
    </ul>
    <div class="my-3 text-center text-white text-body">
        ©
        2025
        , تم الإنشاء ❤️ بواسطة <a href="https://saeyd-jamal.github.io/portfolio/" target="_blank"
            class="footer-link">م . السيد الاخرسي</a>
    </div>
</aside>