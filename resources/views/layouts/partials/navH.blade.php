<nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="container-xxl">
        <div class="py-0 navbar-brand app-brand demo d-none d-xl-flex me-4" style="overflow: visible;">
            <a href="https://alsaeyd-alakhras.vercel.app/ar" target="_blank" class="app-brand-link" style="overflow: visible;">
                <span class="app-brand-logo demo" style="overflow: visible; width: 100px !important;">
                    <img src=" {{ asset('imgs/logo-brand.png') }}" alt="Logo" width="100">
                </span>
                <span class="app-brand-text demo menu-text fw-bold">- {{ $title }}</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="align-middle ti ti-x ti-md"></i>
            </a>
        </div>

        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="px-0 nav-item nav-link me-xl-4" href="javascript:void(0)">
                <i class="ti ti-menu-2 ti-md"></i>
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <ul class="flex-row navbar-nav align-items-center ms-auto">
                {{ $extra_nav ?? '' }}

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="p-0 nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                        data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            <img src="{{ Auth::user()->avatar_url }}" alt class="rounded-circle" />
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="mt-0 dropdown-item" href="{{ route('dashboard.users.show', Auth::user()->id) }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="avatar avatar-online">
                                            <img src="{{ Auth::user()->avatar_url }}" alt
                                                class="rounded-circle" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->email ?? Auth::user()->username }}</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <div class="my-1 dropdown-divider mx-n2"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('dashboard.users.show', Auth::user()->id) }}">
                                <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">
                                    الملف الشخصي</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('dashboard.profile.settings') }}">
                                <i class="ti ti-settings me-3 ti-md"></i><span class="align-middle">الإعدادات</span>
                            </a>
                        </li>
                        <li>
                            <div class="px-2 pt-2 pb-1 d-grid">
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger d-flex" href="javascript:void(0);">
                                        <small class="align-middle">تسجيل الخروج</small>
                                        <i class="ti ti-logout ms-2 ti-14px"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </li>
                <!--/ User -->
            </ul>
        </div>

        <!-- Search Small Screens -->
        <div class="navbar-search-wrapper search-input-wrapper container-xxl d-none">
            <input type="text" class="border-0 form-control search-input" placeholder="Search..."
                aria-label="Search..." />
            <i class="cursor-pointer ti ti-x search-toggler"></i>
        </div>
    </div>
</nav>