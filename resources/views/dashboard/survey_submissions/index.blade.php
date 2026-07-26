<x-front-layout :title="'طلبات الاستبيان'">
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
                --sticky-col2-width: 110px;
                --sticky-col2-right: var(--sticky-col1-width);
            }

            th.enhanced-sticky:nth-child(1), td.enhanced-sticky:nth-child(1) {
                right: 0;
                width: var(--sticky-col1-width);
                min-width: var(--sticky-col1-width);
            }

            th.enhanced-sticky:nth-child(2), td.enhanced-sticky:nth-child(2) {
                right: var(--sticky-col2-right);
                width: var(--sticky-col2-width);
                min-width: var(--sticky-col2-width);
            }

            .survey-detail-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }
        </style>
    @endpush

    <x-slot:extra_nav>
        @can('update', 'App\Models\SurveySubmission')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" data-bs-toggle="modal" data-bs-target="#surveyWindowModal">
                    <i class="fa-solid fa-gear fe-16"></i> إعدادات
                </button>
            </div>
        @endcan
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
            'view' => 'إجراءات',
            'full_name' => 'الاسم',
            'national_id' => 'رقم الهوية',
            'gender' => 'الجنس',
            'marital_status' => 'الحالة الزوجية',
            'status' => 'الحالة',
            'created_at' => 'تاريخ التقديم',
        ];
        $filterableFields = ['gender', 'marital_status', 'status'];
        $sortableFields = ['national_id', 'created_at'];
    @endphp

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="survey-submissions-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th class="{{ $loop->index < 1 ? 'enhanced-sticky' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $label }}</span>
                                            <div class="enhanced-filter-dropdown d-flex align-items-center gap-1">
                                                @if (in_array($index, $sortableFields, true))
                                                    <button class="btn-sort btn btn-sm border-0 p-1" type="button"
                                                        data-sort-field="{{ $index }}" title="فرز">
                                                        <i class="fas fa-sort text-muted"></i>
                                                    </button>
                                                @endif
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
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('update', 'App\Models\SurveySubmission')
        <div class="modal fade" id="surveyWindowModal" tabindex="-1" aria-labelledby="surveyWindowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="surveyWindowModalLabel">نافذة استقبال الاستبيان</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">حالة النافذة الحالية</span>
                            <span class="badge {{ $windowOpen ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $windowOpen ? 'مفتوحة حالياً' : 'مغلقة حالياً' }}
                            </span>
                        </div>
                        <form action="{{ route('dashboard.survey-submissions.update-window') }}" method="post" class="row align-items-end">
                            @csrf
                            @method('put')
                            <div class="col-md-4 mb-3">
                                <x-form.input type="date" name="window_start" label="تاريخ البداية" :value="$windowStart" required />
                            </div>
                            <div class="col-md-4 mb-3">
                                <x-form.input type="date" name="window_end" label="تاريخ النهاية" :value="$windowEnd" required />
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="submit" class="btn btn-primary w-100">حفظ النافذة الزمنية</button>
                            </div>
                        </form>
                        <p class="text-muted small mb-0">
                            رابط الاستبيان العام:
                            <a href="{{ route('survey.show') }}" target="_blank">{{ route('survey.show') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal fade" id="surveySubmissionDetailsModal" tabindex="-1" aria-labelledby="surveySubmissionDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="surveySubmissionDetailsModalLabel">تفاصيل طلب الاستبيان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="surveySubmissionDetailsBody">
                    <div class="text-center text-muted py-4">جاري التحميل...</div>
                </div>
            </div>
        </div>
    </div>

    <x-confirm-modal />

    @push('scripts')
        <script src="{{ asset('js/plugins/jquery.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.js') }}"></script>
        <script>
            const tableId = 'survey-submissions-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";

            const urlIndex = `{{ route('dashboard.survey-submissions.index') }}`;
            const urlFilters = `{{ route('dashboard.survey-submissions.filters', ':column') }}`;
            const urlShow = `{{ route('dashboard.survey-submissions.show', ':id') }}`;

            const abilityView = {{ Auth::user()->can('view', 'App\\Models\\SurveySubmission') ? 'true' : 'false' }};
            const abilityUpdate = {{ Auth::user()->can('update', 'App\\Models\\SurveySubmission') ? 'true' : 'false' }};

            const statusLabels = { pending: 'قيد المراجعة', approved: 'موافَق عليها', rejected: 'مرفوضة' };
            const genderLabels = { male: 'ذكر', female: 'أنثى' };
            const maritalLabels = {
                single: 'أعزب/عزباء',
                married: 'متزوج/ة',
                polygamous: 'متعدد الزوجات',
                widowed: 'أرمل/ة',
                divorced: 'مطلق/ة',
            };

            const fields = ['#', 'view', 'full_name', 'national_id', 'gender', 'marital_status', 'status', 'created_at'];

            function renderStatusBadge(status, label) {
                const classes = {
                    pending: 'bg-label-warning',
                    approved: 'bg-label-success',
                    rejected: 'bg-label-danger',
                };

                return `<span class="badge ${classes[status] || 'bg-label-secondary'}">${escapeHtml(label || statusLabels[status] || status || '-')}</span>`;
            }

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                {
                    data: 'view',
                    name: 'view',
                    orderable: false,
                    searchable: false,
                    class: 'enhanced-sticky text-center',
                    render: function (data) {
                        if (!abilityView) return '';
                        return `<button type="button" class="action-btn btn-view btn-survey-details" data-id="${data}" title="عرض التفاصيل"><i class="fas fa-eye"></i></button>`;
                    },
                },
                { data: 'full_name', name: 'full_name', orderable: false, searchable: false },
                { data: 'national_id', name: 'national_id', orderable: false, class: 'text-center' },
                {
                    data: 'gender', name: 'gender', orderable: false, searchable: false, class: 'text-center',
                    render: function (data) { return genderLabels[data] || data || '-'; },
                },
                {
                    data: 'marital_status', name: 'marital_status', orderable: false, searchable: false, class: 'text-center',
                    render: function (data) { return maritalLabels[data] || data || '-'; },
                },
                {
                    data: 'status', name: 'status', orderable: false, searchable: false, class: 'text-center',
                    render: function (data) { return renderStatusBadge(data); },
                },
                { data: 'created_at_formatted', name: 'created_at', orderable: false, searchable: false, class: 'text-center' },
            ];

            const sortConfig = { enabled: true };
            let currentSortColumn = '';
            let currentSortDirection = '';
            const SUMMABLE_COLUMNS = { enabled: false, columns: {} };

            let surveySubmissionsTable = null;
            function onTableReady(table) {
                surveySubmissionsTable = table;
            }
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            function dependentRows(rows, includeParentType) {
                if (!rows.length) {
                    return '<tr><td colspan="4" class="text-center text-muted">لا يوجد</td></tr>';
                }

                return rows.map(function (row) {
                    return `
                        <tr>
                            <td>${escapeHtml(row.full_name || '-')}</td>
                            <td class="text-center">${escapeHtml(row.national_id || '-')}</td>
                            <td class="text-center">${escapeHtml(row.gender || '-')}</td>
                            <td class="text-center">${includeParentType ? escapeHtml(row.parent_type || '-') : '-'}</td>
                        </tr>
                    `;
                }).join('');
            }

            function dependentSection(title, rows, includeParentType) {
                return `
                    <div class="mt-4">
                        <h6 class="mb-2">${title}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th class="text-center">رقم الهوية</th>
                                        <th class="text-center">الجنس</th>
                                        <th class="text-center">نوع الوالد</th>
                                    </tr>
                                </thead>
                                <tbody>${dependentRows(rows, includeParentType)}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            function renderDetailsModal(response) {
                const employee = response.employee || {};
                const dependents = response.dependents || { spouses: [], children: [], parents: [] };
                const actions = abilityUpdate && response.can_update && employee.status === 'pending'
                    ? `
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-danger btn-survey-reject" data-url="${response.urls.reject}">
                                <i class="fa-solid fa-xmark me-1"></i> رفض
                            </button>
                            <button type="button" class="btn btn-success btn-survey-approve" data-url="${response.urls.approve}">
                                <i class="fa-solid fa-check me-1"></i> موافقة
                            </button>
                        </div>
                    `
                    : '';

                return `
                    <div class="survey-detail-summary">
                        <div><strong>الاسم:</strong> ${escapeHtml(employee.full_name || '-')}</div>
                        <div><strong>رقم الهوية:</strong> ${escapeHtml(employee.national_id || '-')}</div>
                        <div><strong>الجنس:</strong> ${escapeHtml(employee.gender || '-')}</div>
                        <div><strong>الحالة الزوجية:</strong> ${escapeHtml(employee.marital_status || '-')}</div>
                        <div><strong>الوحدة التنظيمية:</strong> ${escapeHtml(employee.organization_unit_name || '-')}</div>
                        <div><strong>حالة الطلب:</strong> ${renderStatusBadge(employee.status, employee.status_label)}</div>
                    </div>
                    ${dependentSection('زوجات/أزواج', dependents.spouses || [], false)}
                    ${dependentSection('أبناء', dependents.children || [], false)}
                    ${dependentSection('والدين', dependents.parents || [], true)}
                    ${actions}
                `;
            }

            function showToast(type, message) {
                if (window.toastr && typeof window.toastr[type] === 'function') {
                    window.toastr[type](message);
                    return;
                }

                alert(message);
            }

            function submitSurveyAction(url, successMessage) {
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    data: { _token: _token },
                    success: function (response) {
                        $('#surveySubmissionDetailsModal').modal('hide');
                        showToast('success', response.message || successMessage);
                        surveySubmissionsTable?.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تنفيذ الإجراء.';
                        showToast('error', message);
                    },
                });
            }

            $(document).on('click', '.btn-survey-details', function () {
                const id = $(this).data('id');
                const $body = $('#surveySubmissionDetailsBody');
                $body.html('<div class="text-center text-muted py-4">جاري التحميل...</div>');
                $('#surveySubmissionDetailsModal').modal('show');

                $.ajax({
                    url: urlShow.replace(':id', id),
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    success: function (response) {
                        $body.html(renderDetailsModal(response));
                    },
                    error: function () {
                        $body.html('<div class="alert alert-danger mb-0">تعذر تحميل تفاصيل الطلب.</div>');
                    },
                });
            });

            $(document).on('click', '.btn-survey-approve', function () {
                const url = $(this).data('url');
                window.confirmAction({
                    title: 'تأكيد الموافقة',
                    message: 'سيتم إنشاء سجل موظف رسمي وربطه بالتابعين المذكورين. هل تريد المتابعة؟',
                    variant: 'primary',
                    onConfirm: function () { submitSurveyAction(url, 'تمت الموافقة وإنشاء سجل الموظف.'); },
                });
            });

            $(document).on('click', '.btn-survey-reject', function () {
                const url = $(this).data('url');
                window.confirmAction({
                    title: 'تأكيد الرفض',
                    message: 'هل أنت متأكد من رفض هذا الطلب؟',
                    variant: 'danger',
                    onConfirm: function () { submitSurveyAction(url, 'تم رفض الطلب.'); },
                });
            });
        </script>
    @endpush
</x-front-layout>
