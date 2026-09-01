<x-front-layout :title="'تقرير الدخل حسب القسم الطبي'">
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

            #income-report-table td.amount-column {
                direction: ltr;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }
        </style>
    @endpush

    <x-slot:extra_nav>
        <div class="mx-2 nav-item">
            <a href="{{ route('dashboard.reports.income') }}" class="m-0 btn btn-outline-secondary">
                <i class="fa-solid fa-filter fe-16"></i> تعديل الفلاتر
            </a>
        </div>
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="-1">all</option>
            </select>
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
            'department_name' => 'اسم القسم الطبي',
            'visits_count' => 'عدد الزيارات المحتسبة',
            'total_before' => 'إجمالي قبل الخصم',
            'total_after' => 'إجمالي بعد الخصم',
            'total_discount' => 'إجمالي الخصم',
            'avg_discount_percentage' => 'متوسط الخصم %',
            'current_discount_percentage' => 'نسبة الخصم المُعتمدة حالياً',
            'current_max_discount_amount' => 'الحد الأقصى للخصم (حالياً)',
        ];
        $filterableFields = ['department_name'];
    @endphp

    <div class="row g-3 mb-2">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي الدخل قبل الخصم</div>
                <div class="summary-value" id="summary-total-before">0.00</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي الدخل بعد الخصم</div>
                <div class="summary-value" id="summary-total-after">0.00</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي قيمة الخصومات الممنوحة</div>
                <div class="summary-value" id="summary-total-discount">0.00</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">القسم الأعلى دخلاً</div>
                <div class="summary-value" id="summary-top-department">-</div>
            </div>
        </div>
    </div>
    <div class="text-muted small mb-4">لا يشمل هذا التقرير الزيارات التي لم يُدخَل لها مبلغ (المبالغ اختيارية دائماً)</div>

    <div class="income-filter-toolbar d-flex flex-wrap gap-2 align-items-center mb-4">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                تاريخ الزيارة
            </button>
            <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu p-3">
                <div class="mb-3">
                    <label class="form-label text-muted small">من تاريخ:</label>
                    <input type="date" class="form-control form-control-sm" id="from_date">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">إلى تاريخ:</label>
                    <input type="date" class="form-control form-control-sm" id="to_date">
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
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                العيادة
            </button>
            <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu p-3">
                <div class="enhanced-checkbox-list checkbox-list-box">
                    <label style="display: block;">
                        <input type="checkbox" value="all" class="all-checkbox" data-index="clinic"> الكل
                    </label>
                    <div class="checkbox-list checkbox-list-clinic">
                        @foreach ($clinics as $clinic)
                            <label style="display: block;">
                                <input type="checkbox" value="{{ $clinic }}" class="clinic_name-checkbox"> {{ $clinic }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="enhanced-apply-btn mt-2 filter-apply-btn-checkbox" type="button">
                    <i class="fas fa-check me-1"></i> تطبيق
                </button>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                المركزية
            </button>
            <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu p-3">
                <div class="enhanced-checkbox-list checkbox-list-box">
                    <label style="display: block;">
                        <input type="checkbox" value="all" class="all-checkbox" data-index="center"> الكل
                    </label>
                    <div class="checkbox-list checkbox-list-center">
                        @foreach ($organizationCenters as $center)
                            <label style="display: block;">
                                <input type="checkbox" value="{{ $center }}" class="organization_center-checkbox"> {{ $center }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="enhanced-apply-btn mt-2 filter-apply-btn-checkbox" type="button">
                    <i class="fas fa-check me-1"></i> تطبيق
                </button>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                الدائرة
            </button>
            <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu p-3">
                <div class="enhanced-checkbox-list checkbox-list-box">
                    <label style="display: block;">
                        <input type="checkbox" value="all" class="all-checkbox" data-index="department-unit"> الكل
                    </label>
                    <div class="checkbox-list checkbox-list-department-unit">
                        @foreach ($organizationDepartments as $department)
                            <label style="display: block;">
                                <input type="checkbox" value="{{ $department }}" class="organization_department-checkbox"> {{ $department }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="enhanced-apply-btn mt-2 filter-apply-btn-checkbox" type="button">
                    <i class="fas fa-check me-1"></i> تطبيق
                </button>
            </div>
        </div>
    </div>

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="income-report-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $label }}</span>
                                            <div class="enhanced-filter-dropdown d-flex align-items-center gap-1">
                                                @if (in_array($index, $filterableFields, true))
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
                        <tfoot>
                            <tr>
                                <td class="enhanced-sticky"></td>
                                @foreach ($fields as $index => $label)
                                    <td class="text-center fw-bold" id="tfoot-{{ $index }}"></td>
                                @endforeach
                            </tr>
                        </tfoot>
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
            const tableId = 'income-report-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";
            const dateFilterField = 'visit_date';

            const urlIndex = `{{ route('dashboard.reports.income') }}`;
            const urlFilters = `{{ route('dashboard.reports.income.filters', ':column') }}`;
            const urlDelete = '';
            const urlSummary = `{{ route('dashboard.reports.income.summary') }}`;

            const fields = ['#', 'department_name', 'visits_count', 'total_before', 'total_after', 'total_discount', 'avg_discount_percentage', 'current_discount_percentage', 'current_max_discount_amount'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                { data: 'department_name', name: 'medical_departments.name', orderable: false },
                { data: 'visits_count', name: 'visits_count', orderable: false, searchable: false, class: 'text-center' },
                { data: 'total_before_formatted', name: 'total_before', orderable: false, searchable: false, class: 'text-center amount-column' },
                { data: 'total_after_formatted', name: 'total_after', orderable: false, searchable: false, class: 'text-center amount-column' },
                { data: 'total_discount_formatted', name: 'total_discount', orderable: false, searchable: false, class: 'text-center amount-column' },
                { data: 'avg_discount_percentage', name: 'avg_discount_percentage', orderable: false, searchable: false, class: 'text-center amount-column' },
                { data: 'current_discount_percentage', name: 'current_discount_percentage', orderable: false, searchable: false, class: 'text-center' },
                { data: 'current_max_discount_amount', name: 'current_max_discount_amount', orderable: false, searchable: false, class: 'text-center amount-column' },
            ];

            const sortConfig = { enabled: false };
            let currentSortColumn = '';
            let currentSortDirection = '';
            const SUMMABLE_COLUMNS = {
                enabled: true,
                columns: {
                    total_before: { format: 'currency' },
                    total_after: { format: 'currency' },
                    total_discount: { format: 'currency' },
                },
            };

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
                    filters.visit_from = $('#from_date').val();
                }
                if ($('#to_date').val()) {
                    filters.visit_to = $('#to_date').val();
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

            function updateIncomeSummary() {
                $.getJSON(urlSummary + '?' + reportFiltersAsQuery(), function (summary) {
                    $('#summary-total-before').text(summary.total_before ?? '0.00');
                    $('#summary-total-after').text(summary.total_after ?? '0.00');
                    $('#summary-total-discount').text(summary.total_discount ?? '0.00');
                    $('#summary-top-department').text(summary.top_department ?? '-');
                });
            }

            function onTableReady(table) {
                updateIncomeSummary();
                table.on('draw', function () {
                    updateIncomeSummary();
                });
            }
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            $(document).on('click', '.income-filter-toolbar .checkbox-list-box', function (event) {
                event.stopPropagation();
            });
        </script>
    @endpush
</x-front-layout>
