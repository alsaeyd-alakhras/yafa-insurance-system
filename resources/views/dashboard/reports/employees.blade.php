<x-front-layout :title="'تقرير الموظفين'">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/datatable/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.bootstrap4.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.dataTables.css') }}">
        <link id="stickyTableLight" rel="stylesheet" href="{{ asset('css/custom2/stickyTable.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex2.css') }}">
        <style>
            :root {
                --sticky-col1-width: 60px;
            }

            th.enhanced-sticky:nth-child(1), td.enhanced-sticky:nth-child(1) {
                right: 0;
                width: var(--sticky-col1-width);
                min-width: var(--sticky-col1-width);
            }

            .report-summary-card {
                border: 1px solid rgba(67, 89, 113, .12);
                border-radius: .5rem;
                background: #fff;
                padding: 1rem;
                min-height: 94px;
            }

            .report-summary-card .summary-value {
                font-size: 1.45rem;
                font-weight: 700;
                line-height: 1.2;
            }

            .dependents-count-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.75rem;
                padding: .25rem .55rem;
                border-radius: 50rem;
                font-size: .8125rem;
                font-weight: 600;
                background: rgba(105, 108, 255, .1);
                color: #696cff;
            }

            .dependents-count-badge.is-empty {
                background: rgba(67, 89, 113, .06);
                color: #8592a3;
            }
        </style>
    @endpush

    <x-slot:extra_nav>
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="-1">all</option>
            </select>
        </div>
        <div class="mx-2 nav-item">
            <button type="button" class="m-0 btn btn-outline-success report-export-btn" data-export-url="{{ route('dashboard.reports.employees.export-excel') }}">
                <i class="fa-solid fa-file-excel fe-16"></i> تصدير Excel
            </button>
        </div>
        <div class="mx-2 nav-item">
            <button type="button" class="m-0 btn btn-outline-danger report-export-btn" data-export-url="{{ route('dashboard.reports.employees.export-pdf') }}">
                <i class="fa-solid fa-file-pdf fe-16"></i> تصدير PDF
            </button>
        </div>
        <div class="mx-2 nav-item">
            <button type="button" class="p-2 border-0 btn btn-outline-danger rounded-pill me-n1 waves-effect waves-light d-none"
                id="filterBtnClear" title="إزالة التصفية">
                <i class="fa-solid fa-eraser fe-16"></i>
            </button>
        </div>
        <div class="mx-2 nav-item d-flex align-items-center justify-content-center">
            <button type="button" class="btn" id="refreshData">
                <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </div>
    </x-slot:extra_nav>

    @php
        $fields = [
            'id' => 'المعرف',
            'full_name' => 'الاسم الكامل',
            'national_id' => 'رقم الهوية',
            'status' => 'الحالة',
            'gender' => 'الجنس',
            'marital_status' => 'الحالة الاجتماعية',
            'organization_center' => 'المركزية',
            'organization_department' => 'الدائرة',
            'organization_section' => 'القسم',
            'source' => 'مصدر التسجيل',
            'dependents_count' => 'عدد التابعين',
            'approved_by_name' => 'اعتُمد بواسطة',
            'approved_at' => 'تاريخ الاعتماد',
            'created_at' => 'تاريخ الإنشاء',
        ];
        $filterableFields = ['status', 'gender', 'marital_status', 'organization_center', 'organization_department', 'source', 'dependents_count'];
        $sortableFields = ['id', 'full_name', 'national_id', 'status', 'gender', 'marital_status', 'source', 'approved_at', 'created_at'];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي الموظفين</div>
                <div class="summary-value" id="summary-total-employees">0</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">الموظفون النشطون</div>
                <div class="summary-value" id="summary-active-count">0</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">قيد الموافقة</div>
                <div class="summary-value" id="summary-pending-count">0</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي التابعين</div>
                <div class="summary-value" id="summary-total-dependents">0</div>
            </div>
        </div>
    </div>

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="employees-report-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $label }}</span>
                                            <div class="enhanced-filter-dropdown d-flex align-items-center gap-1">
                                                @if (in_array($index, $sortableFields, true))
                                                    <button class="btn-sort btn btn-sm border-0 p-1" type="button"
                                                        data-sort-field="{{ $index }}" title="فرز">
                                                        <i class="fas fa-sort text-muted"></i>
                                                    </button>
                                                @endif
                                                @if ($index === 'created_at')
                                                    <div class="dropdown">
                                                        <button class="enhanced-btn-filter btn-filter" type="button"
                                                            data-bs-toggle="dropdown"
                                                            id="btn-filter-{{ $loop->index + 1 }}">
                                                            <i class="fas fa-filter"></i>
                                                        </button>
                                                        <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu"
                                                            aria-labelledby="{{ $index }}_filter">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">من تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="from_date"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">إلى تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="to_date"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="gap-2 d-flex">
                                                                <button class="enhanced-apply-btn flex-fill" id="filter-date-btn">
                                                                    <i class="fas fa-check me-1"></i> تطبيق
                                                                </button>
                                                                <button class="btn btn-outline-secondary btn-sm flex-fill" id="filter-date-clear-btn" type="button">
                                                                    <i class="fas fa-times me-1"></i> مسح
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif ($index === 'approved_at')
                                                    <div class="dropdown">
                                                        <button class="enhanced-btn-filter btn-filter" type="button"
                                                            data-bs-toggle="dropdown"
                                                            id="btn-filter-{{ $loop->index + 1 }}">
                                                            <i class="fas fa-filter"></i>
                                                        </button>
                                                        <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu"
                                                            aria-labelledby="{{ $index }}_filter">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">من تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="approved_from"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">إلى تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="approved_to"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="gap-2 d-flex">
                                                                <button class="enhanced-apply-btn flex-fill" id="filter-approved-date-btn">
                                                                    <i class="fas fa-check me-1"></i> تطبيق
                                                                </button>
                                                                <button class="btn btn-outline-secondary btn-sm flex-fill" id="filter-approved-date-clear-btn" type="button">
                                                                    <i class="fas fa-times me-1"></i> مسح
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif (in_array($index, $filterableFields, true))
                                                    <div class="dropdown">
                                                        <button class="enhanced-btn-filter btn-filter" type="button"
                                                            data-bs-toggle="dropdown"
                                                            id="btn-filter-{{ $loop->index + 1 }}">
                                                            <i class="fas fa-filter"></i>
                                                        </button>
                                                        <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu"
                                                            aria-labelledby="{{ $index }}_filter">
                                                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                                                <input type="search" class="form-control search-checkbox"
                                                                    placeholder="ابحث..." data-index="{{ $loop->index + 1 }}">
                                                                <button class="enhanced-apply-btn ms-2 filter-apply-btn-checkbox"
                                                                    data-target="{{ $loop->index + 1 }}"
                                                                    data-field="{{ $index }}">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </div>
                                                            <div class="enhanced-checkbox-list checkbox-list-box">
                                                                <label style="display: block;">
                                                                    <input type="checkbox" value="all"
                                                                        class="all-checkbox"
                                                                        data-index="{{ $loop->index + 1 }}"> الكل
                                                                </label>
                                                                <div class="checkbox-list checkbox-list-{{ $loop->index + 1 }}"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/plugins/jquery.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.js') }}"></script>
        <script src="{{ asset('js/plugins/jquery.validate.min.js') }}"></script>
        <script>
            const tableId = 'employees-report-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";
            const dateFilterField = 'created_at';

            const urlIndex = `{{ route('dashboard.reports.employees') }}`;
            const urlFilters = `{{ route('dashboard.reports.employees.filters', ':column') }}`;
            const urlDelete = '';
            const urlSummary = `{{ route('dashboard.reports.employees.summary') }}`;

            const statusLabels = { pending: 'قيد الموافقة', active: 'نشط', inactive: 'غير نشط' };
            const genderLabels = { male: 'ذكر', female: 'أنثى' };
            const maritalLabels = {
                single: 'أعزب/عزباء',
                married: 'متزوج/ة',
                polygamous: 'متعدد الزوجات',
                widowed: 'أرمل/ة',
                divorced: 'مطلق/ة',
            };
            const sourceLabels = { survey: 'استبيان', admin: 'إضافة مباشرة' };

            const fields = ['#', 'id', 'full_name', 'national_id', 'status', 'gender', 'marital_status', 'organization_center', 'organization_department', 'organization_section', 'source', 'dependents_count', 'approved_by_name', 'approved_at', 'created_at'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                { data: 'id', name: 'id', orderable: false, class: 'text-center' },
                { data: 'full_name', name: 'full_name', orderable: false },
                { data: 'national_id', name: 'national_id', orderable: false, class: 'text-center' },
                {
                    data: 'status', name: 'status', orderable: false, class: 'text-center',
                    render: function (data) { return statusLabels[data] || data; },
                },
                {
                    data: 'gender', name: 'gender', orderable: false, class: 'text-center',
                    render: function (data) { return genderLabels[data] || data; },
                },
                {
                    data: 'marital_status', name: 'marital_status', orderable: false, class: 'text-center',
                    render: function (data) { return maritalLabels[data] || data; },
                },
                { data: 'organization_center', name: 'organization_center', orderable: false, searchable: false },
                { data: 'organization_department', name: 'organization_department', orderable: false, searchable: false },
                { data: 'organization_section', name: 'organization_section', orderable: false, searchable: false },
                {
                    data: 'source', name: 'source', orderable: false, class: 'text-center',
                    render: function (data) { return sourceLabels[data] || data || '-'; },
                },
                {
                    data: 'dependents_count', name: 'dependents_count', orderable: false, searchable: false, class: 'text-center',
                    render: function (data) {
                        const count = Number(data) || 0;
                        const cls = count > 0 ? 'dependents-count-badge' : 'dependents-count-badge is-empty';
                        return `<span class="${cls}">${count}</span>`;
                    },
                },
                { data: 'approved_by_name', name: 'approved_by_name', orderable: false, searchable: false },
                { data: 'approved_at_formatted', name: 'approved_at', orderable: false, class: 'text-center' },
                { data: 'created_at_formatted', name: 'created_at', orderable: false, class: 'text-center' },
            ];

            const sortConfig = { enabled: true };
            let currentSortColumn = '';
            let currentSortDirection = '';
            const SUMMABLE_COLUMNS = { enabled: false, columns: {} };

            function collectReportFilters() {
                const filters = {};

                $('input[type="checkbox"]:checked').each(function () {
                    const className = $(this).attr('class') || '';
                    const value = $(this).val();

                    if (value === 'الكل' || value === 'all' || value === 'All') {
                        return;
                    }

                    const fieldMatch = className.match(/(\w+)-checkbox/);
                    if (fieldMatch) {
                        const fieldName = fieldMatch[1];
                        if (!filters[fieldName]) {
                            filters[fieldName] = [];
                        }
                        filters[fieldName].push(value);
                    }
                });

                if ($('#from_date').val()) {
                    filters.created_from = $('#from_date').val();
                }
                if ($('#to_date').val()) {
                    filters.created_to = $('#to_date').val();
                }
                if ($('#approved_from').val()) {
                    filters.approved_from = $('#approved_from').val();
                }
                if ($('#approved_to').val()) {
                    filters.approved_to = $('#approved_to').val();
                }

                return filters;
            }

            function reportFiltersAsQuery() {
                const params = new URLSearchParams();
                const filters = collectReportFilters();

                Object.keys(filters).forEach(function (key) {
                    const value = filters[key];
                    if (Array.isArray(value)) {
                        value.forEach(function (item) {
                            params.append(key + '[]', item);
                        });
                    } else if (value) {
                        params.append(key, value);
                    }
                });

                return params.toString();
            }

            function updateEmployeesSummary() {
                $.getJSON(urlSummary + '?' + reportFiltersAsQuery(), function (summary) {
                    $('#summary-total-employees').text(summary.total_employees ?? 0);
                    $('#summary-active-count').text(summary.active_count ?? 0);
                    $('#summary-pending-count').text(summary.pending_count ?? 0);
                    $('#summary-total-dependents').text(summary.total_dependents ?? 0);
                });
            }

            function updateApprovedDateFilterStyle() {
                const button = $('#btn-filter-' + fields.indexOf('approved_at'));
                if (!button.length) {
                    return;
                }

                if ($('#approved_from').val() || $('#approved_to').val()) {
                    button.removeClass('btn-secondary').addClass('btn-success');
                    button.find('i').removeClass('fa-solid fa-filter').addClass('fa-brands fa-get-pocket');
                    $('#filterBtnClear').removeClass('d-none');
                } else {
                    button.removeClass('btn-success').addClass('btn-secondary');
                    button.find('i').removeClass('fa-brands fa-get-pocket').addClass('fa-solid fa-filter');
                }
            }

            document.addEventListener('click', function (event) {
                if (event.target.closest('#filterBtnClear')) {
                    $('#approved_from').val('');
                    $('#approved_to').val('');
                }
            }, true);

            $.ajaxPrefilter(function (options) {
                if (!options.url || options.url.indexOf(urlIndex) !== 0) {
                    return;
                }

                const extra = {
                    approved_from: $('#approved_from').val(),
                    approved_to: $('#approved_to').val(),
                };

                if (typeof options.data === 'string') {
                    const params = new URLSearchParams(options.data);
                    Object.keys(extra).forEach(function (key) {
                        if (extra[key]) {
                            params.set(key, extra[key]);
                        }
                    });
                    options.data = params.toString();
                    return;
                }

                options.data = options.data || {};
                Object.keys(extra).forEach(function (key) {
                    if (extra[key]) {
                        options.data[key] = extra[key];
                    }
                });
            });

            function onTableReady(table) {
                updateEmployeesSummary();
                updateApprovedDateFilterStyle();
                table.on('draw', function () {
                    updateEmployeesSummary();
                    updateApprovedDateFilterStyle();
                });
            }
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            $(document).on('click', '#filter-approved-date-btn', function () {
                updateApprovedDateFilterStyle();
                $('#refreshData').trigger('click');
            });

            $(document).on('click', '#filter-approved-date-clear-btn', function () {
                $('#approved_from').val('');
                $('#approved_to').val('');
                updateApprovedDateFilterStyle();
                $('#refreshData').trigger('click');
            });

            $(document).on('click', '#filterBtnClear', function () {
                $('#approved_from').val('');
                $('#approved_to').val('');
                updateApprovedDateFilterStyle();
                updateEmployeesSummary();
            });

            $(document).on('click', '.report-export-btn', function () {
                const query = reportFiltersAsQuery();
                const url = $(this).data('export-url');
                window.location.href = query ? url + '?' + query : url;
            });
        </script>
    @endpush
</x-front-layout>
