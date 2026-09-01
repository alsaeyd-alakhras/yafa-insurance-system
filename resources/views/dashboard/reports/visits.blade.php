<x-front-layout :title="'تقرير الزيارات'">
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

            #visits-report-table td.amount-column {
                direction: ltr;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
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
            <button type="button" class="m-0 btn btn-outline-success report-export-btn" data-export-url="{{ route('dashboard.reports.visits.export-excel') }}">
                <i class="fa-solid fa-file-excel fe-16"></i> تصدير Excel
            </button>
        </div>
        <div class="mx-2 nav-item">
            <button type="button" class="m-0 btn btn-outline-danger report-export-btn" data-export-url="{{ route('dashboard.reports.visits.export-pdf') }}">
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
            'visit_date' => 'التاريخ',
            'patient_name' => 'اسم المريض',
            'patient_type' => 'نوع المريض',
            'employee_name' => 'الموظف صاحب الرصيد',
            'clinic_name' => 'العيادة',
            'departments_list' => 'الأقسام المضافة',
            'total_before_discount' => 'المبلغ قبل الخصم',
            'total_after_discount' => 'المبلغ بعد الخصم',
            'recorded_by_name' => 'مسجّل الزيارة',
            'organization_center' => 'المركزية',
            'organization_department' => 'الدائرة',
        ];
        $filterableFields = ['patient_name', 'patient_type', 'employee_name', 'clinic_name', 'departments_list', 'recorded_by_name', 'organization_center', 'organization_department'];
        $sortableFields = ['visit_date'];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي الزيارات</div>
                <div class="summary-value" id="summary-total-visits">0</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي المبلغ قبل الخصم</div>
                <div class="summary-value" id="summary-total-before-discount">0.00</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">إجمالي المبلغ بعد الخصم</div>
                <div class="summary-value" id="summary-total-after-discount">0.00</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-summary-card">
                <div class="text-muted mb-2">متوسط عدد الأقسام لكل زيارة</div>
                <div class="summary-value" id="summary-avg-departments">0.0</div>
            </div>
        </div>
    </div>

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="visits-report-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
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
                                                @if ($index === 'visit_date')
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
            const tableId = 'visits-report-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";
            const dateFilterField = 'visit_date';

            const urlIndex = `{{ route('dashboard.reports.visits') }}`;
            const urlFilters = `{{ route('dashboard.reports.visits.filters', ':column') }}`;
            const urlDelete = '';
            const urlSummary = `{{ route('dashboard.reports.visits.summary') }}`;

            const fields = ['#', 'visit_date', 'patient_name', 'patient_type', 'employee_name', 'clinic_name', 'departments_list', 'total_before_discount', 'total_after_discount', 'recorded_by_name', 'organization_center', 'organization_department'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                { data: 'visit_date', name: 'visit_date', orderable: false, class: 'text-center' },
                { data: 'patient_name', name: 'patient_name', orderable: false },
                { data: 'patient_type_label', name: 'patient_type', orderable: false, class: 'text-center' },
                { data: 'employee_name', name: 'employee_name', orderable: false },
                { data: 'clinic_name', name: 'clinic_name', orderable: false },
                { data: 'departments_list', name: 'departments_list', orderable: false },
                { data: 'total_before_discount_formatted', name: 'total_before_discount', orderable: false, class: 'text-center amount-column' },
                { data: 'total_after_discount_formatted', name: 'total_after_discount', orderable: false, class: 'text-center amount-column' },
                { data: 'recorded_by_name', name: 'recorded_by_name', orderable: false },
                { data: 'organization_center', name: 'organization_center', orderable: false, searchable: false },
                { data: 'organization_department', name: 'organization_department', orderable: false, searchable: false },
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

            function updateVisitsSummary() {
                $.getJSON(urlSummary + '?' + reportFiltersAsQuery(), function (summary) {
                    $('#summary-total-visits').text(summary.total_visits ?? 0);
                    $('#summary-total-before-discount').text(summary.total_before_discount ?? '0.00');
                    $('#summary-total-after-discount').text(summary.total_after_discount ?? '0.00');
                    $('#summary-avg-departments').text(summary.avg_departments_per_visit ?? '0.0');
                });
            }

            function onTableReady(table) {
                updateVisitsSummary();
                table.on('draw', function () {
                    updateVisitsSummary();
                });
            }
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            $(document).on('click', '.report-export-btn', function () {
                const query = reportFiltersAsQuery();
                const url = $(this).data('export-url');
                window.location.href = query ? url + '?' + query : url;
            });
        </script>
    @endpush
</x-front-layout>
