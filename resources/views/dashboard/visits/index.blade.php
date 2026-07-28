<x-front-layout :title="'الزيارات'">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/datatable/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.bootstrap4.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.dataTables.css') }}">
        <link id="stickyTableLight" rel="stylesheet" href="{{ asset('css/custom2/stickyTable.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex2.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom/select2.min.css') }}">
        <style>
            :root {
                --sticky-col1-width: 60px;
                --sticky-col2-width: 90px;
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

            #patient-search-results .patient-choice { cursor: pointer; }
            #patient-search-results .family-card { border-color: #e3e7ed; }
            #patient-search-results .family-header { background: #f7f9fb; }
            #visits-table td.amount-column { direction: ltr; font-variant-numeric: tabular-nums; white-space: nowrap; }
            #visits-table td { vertical-align: middle; padding-top: .7rem; padding-bottom: .7rem; }
        </style>
    @endpush

    <x-slot:extra_nav>
        @can('create', 'App\\Models\\Visit')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="btn-open-new-visit">
                    <i class="fa-solid fa-plus fe-16"></i> تسجيل زيارة جديدة
                </button>
            </div>
        @endcan
        <div class="nav-item d-flex align-items-center">
            <i class="fa-solid fa-circle-info text-muted"
                data-bs-toggle="tooltip"
                title="الجدول يعرض زيارات اليوم افتراضياً — استخدم فلتر التاريخ بعمود 'التاريخ' لعرض زيارات تواريخ أخرى."></i>
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
            'edit' => 'تعديل',
            'visit_date' => 'التاريخ',
            'patient_name' => 'المريض',
            'employee_name' => 'الموظف صاحب الرصيد',
            'clinic_name' => 'العيادة',
            'departments_list' => 'الأقسام',
            'total_before_discount' => 'المبلغ قبل الخصم',
            'total_after_discount' => 'المبلغ بعد الخصم',
            'recorded_by_name' => 'مسجّل الزيارة',
        ];
        $filterableFields = ['patient_name', 'employee_name', 'clinic_name'];
        $sortableFields = ['visit_date'];
    @endphp

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="visits-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
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
                                <th class="enhanced-sticky">حذف</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: تسجيل زيارة جديدة --}}
    <div class="modal fade" id="newVisitModal" tabindex="-1" aria-labelledby="newVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newVisitModalLabel">تسجيل زيارة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="patient-search-input">ابحث بالاسم أو رقم الهوية</label>
                        <input type="text" class="form-control" id="patient-search-input" placeholder="اكتب اسم المريض أو رقم هويته...">
                    </div>
                    <div id="patient-search-results" class="list-group mb-3"></div>

                    <div id="selected-patient-panel" class="d-none">
                        <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                            <span>المريض المختار: <strong id="selected-patient-name"></strong></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-change-patient">تغيير</button>
                        </div>
                        <div id="quota-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="create-visit-form" action="{{ route('dashboard.visits.store') }}" method="post" class="d-none">
        @csrf
        <input type="hidden" name="national_id" id="create-visit-national-id">
    </form>

    <x-confirm-modal />

    @push('scripts')
        <script src="{{ asset('js/plugins/jquery.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.js') }}"></script>
        <script>
            const tableId = 'visits-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";
            const dateFilterField = 'visit_date';

            const urlIndex = `{{ route('dashboard.visits.index') }}`;
            const urlFilters = `{{ route('dashboard.visits.filters', ':column') }}`;
            const urlEdit = `{{ route('dashboard.visits.edit', ':id') }}`;
            const urlDelete = `{{ route('dashboard.visits.destroy', ':id') }}`;

            const fields = ['#', 'edit', 'visit_date', 'patient_name', 'employee_name', 'clinic_name', 'departments_list', 'total_before_discount', 'total_after_discount', 'recorded_by_name', 'delete'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                {
                    data: 'edit', name: 'edit', orderable: false, searchable: false, class: 'enhanced-sticky',
                    render: function (data) {
                        return `<a href="${urlEdit.replace(':id', data)}" class="action-btn btn-edit" title="تعديل"><i class="fas fa-edit"></i></a>`;
                    },
                },
                { data: 'visit_date', name: 'visit_date', orderable: false, class: 'text-center' },
                { data: 'patient_name', name: 'patient_name', orderable: false },
                { data: 'employee_name', name: 'employee_name', orderable: false },
                { data: 'clinic_name', name: 'clinic_name', orderable: false },
                { data: 'departments_list', name: 'departments_list', orderable: false },
                { data: 'total_before_discount', name: 'total_before_discount', orderable: false, class: 'text-center amount-column' },
                { data: 'total_after_discount', name: 'total_after_discount', orderable: false, class: 'text-center amount-column' },
                { data: 'recorded_by_name', name: 'recorded_by_name', orderable: false },
                {
                    data: 'delete', name: 'delete', orderable: false, searchable: false,
                    render: function (data) {
                        if (!data) return '';
                        return `<button class="action-btn btn-delete delete_row" data-id="${data}" title="حذف"><i class="fas fa-trash"></i></button>`;
                    },
                },
            ];

            const sortConfig = { enabled: true };
            let currentSortColumn = '';
            let currentSortDirection = '';
            const SUMMABLE_COLUMNS = { enabled: false, columns: {} };
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            let selectedPatient = null;
            let patientSearchTimer = null;

            function resetNewVisitModal() {
                selectedPatient = null;
                $('#patient-search-input').val('');
                $('#patient-search-results').empty();
                $('#selected-patient-panel').addClass('d-none');
                $('#quota-result').empty();
            }

            function selectPatient(patient, label) {
                selectedPatient = patient;
                $('#selected-patient-name').text(label);
                $('#selected-patient-panel').removeClass('d-none');
                $('#patient-search-results').empty();
                $('#patient-search-input').val('');

                const $result = $('#quota-result').html(
                    '<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>جارِ فحص الرصيد وزيارات اليوم...</div>'
                );

                $.ajax({
                    url: '{{ route('dashboard.visits.search') }}',
                    method: 'GET',
                    data: { national_id: patient.national_id },
                    success: function (response) {
                        if (response.redirect) {
                            $result.html(`
                                <div class="alert alert-info mb-0">
                                    توجد زيارة اليوم لهذا المريض.
                                    <a class="btn btn-sm btn-primary ms-3" href="${response.redirect}">فتح الزيارة</a>
                                </div>
                            `);
                            return;
                        }

                        if (response.remaining_quota <= 0) {
                            $result.html('<div class="alert alert-danger mb-0"><strong>انتهى الرصيد الشهري.</strong> الرصيد المتبقي: 0 / 2</div>');
                            return;
                        }

                        $result.html(`
                            <div class="alert alert-success mb-0">
                                الرصيد المتبقي هذا الشهر: <strong>${response.remaining_quota} / 2</strong>
                                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-create-visit">تسجيل الزيارة</button>
                            </div>
                        `);
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'حدث خطأ أثناء البحث';
                        $result.html($('<div class="alert alert-danger mb-0"></div>').text(message));
                    },
                });
            }

            function patientButton(person, suffix) {
                return $('<button type="button" class="patient-choice list-group-item list-group-item-action py-2"></button>')
                    .append($('<span class="fw-semibold"></span>').text(person.full_name))
                    .append($('<small class="text-muted ms-2"></small>').text(`${person.national_id}${suffix ? ` — ${suffix}` : ''}`))
                    .on('click', function () {
                        selectPatient(person, `${person.full_name} — ${person.national_id}`);
                    });
            }

            function renderFamilyResults(families) {
                const $results = $('#patient-search-results').empty();

                if (!families.length) {
                    $results.html('<div class="text-muted border rounded p-3">لا توجد نتائج</div>');
                    return;
                }

                const groups = [
                    { key: 'spouses', label: 'أزواج' },
                    { key: 'children', label: 'أبناء' },
                    { key: 'parents', label: 'والدين' },
                ];

                families.forEach(function (family) {
                    const $card = $('<div class="family-card card mb-3"></div>');
                    const $header = $('<div class="family-header card-header p-2"></div>');
                    $header.append(patientButton(family, 'الموظف صاحب الرصيد'));
                    $card.append($header);

                    const $body = $('<div class="card-body p-2"></div>');
                    groups.forEach(function (group) {
                        const people = family.dependents[group.key] || [];
                        if (!people.length) return;

                        $body.append($('<div class="small fw-bold text-primary mt-2 mb-1"></div>').text(group.label));
                        const $list = $('<div class="list-group list-group-flush border rounded"></div>');
                        people.forEach(function (person) {
                            let suffix = '';
                            if (person.parent_type === 'father') suffix = 'الأب';
                            if (person.parent_type === 'mother') suffix = 'الأم';
                            $list.append(patientButton(person, suffix));
                        });
                        $body.append($list);
                    });
                    $card.append($body);
                    $results.append($card);
                });
            }

            $(document).on('click', '#btn-open-new-visit', function () {
                resetNewVisitModal();
                $('#newVisitModal').modal('show');
                setTimeout(() => $('#patient-search-input').trigger('focus'), 300);
            });

            $(document).on('input', '#patient-search-input', function () {
                const term = $(this).val().trim();
                clearTimeout(patientSearchTimer);
                const $results = $('#patient-search-results');

                if (term.length < 2) {
                    $results.empty();
                    return;
                }

                patientSearchTimer = setTimeout(function () {
                    $.ajax({
                        url: '{{ route('dashboard.visits.search-patients') }}',
                        method: 'GET',
                        data: { term: term },
                        success: function (response) {
                            renderFamilyResults(response);
                        },
                        error: function () {
                            $results.html('<div class="alert alert-danger mb-0">حدث خطأ أثناء البحث عن المرضى.</div>');
                        },
                    });
                }, 300);
            });

            $(document).on('click', '#btn-change-patient', function () {
                resetNewVisitModal();
                $('#patient-search-input').trigger('focus');
            });

            $(document).on('click', '#btn-create-visit', function () {
                if (!selectedPatient) return;
                $('#create-visit-national-id').val(selectedPatient.national_id);
                $('#create-visit-form').trigger('submit');
            });

            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                new bootstrap.Tooltip(element);
            });
        </script>
    @endpush
</x-front-layout>
