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
        </style>
    @endpush

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">تسجيل زيارة جديدة</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="search-national-id">رقم الهوية</label>
                    <input type="text" class="form-control" id="search-national-id" maxlength="9" placeholder="رقم الهوية">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="search-clinic-id">العيادة (اختياري — للكشف الطبي فقط)</label>
                    <select class="form-select" id="search-clinic-id">
                        <option value="">بدون عيادة</option>
                        @foreach (\App\Models\Clinic::where('is_active', true)->orderBy('name')->get() as $clinic)
                            <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="button" class="btn btn-primary w-100" id="btn-search-visit">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> بحث
                    </button>
                </div>
            </div>
            <div id="search-result" class="mt-2"></div>
        </div>
    </div>

    <form id="create-visit-form" action="{{ route('dashboard.visits.store') }}" method="post" class="d-none">
        @csrf
        <input type="hidden" name="national_id" id="create-visit-national-id">
        <input type="hidden" name="clinic_id" id="create-visit-clinic-id">
    </form>

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
            'patient_name' => 'المريض',
            'employee_name' => 'الموظف صاحب الرصيد',
            'clinic_name' => 'العيادة',
            'visit_date' => 'التاريخ',
        ];
        $filterableFields = ['clinic_name'];
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

            const fields = ['#', 'edit', 'patient_name', 'employee_name', 'clinic_name', 'visit_date', 'delete'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                {
                    data: 'edit', name: 'edit', orderable: false, searchable: false, class: 'enhanced-sticky',
                    render: function (data) {
                        return `<a href="${urlEdit.replace(':id', data)}" class="action-btn btn-edit" title="تعديل"><i class="fas fa-edit"></i></a>`;
                    },
                },
                { data: 'patient_name', name: 'patient_name', orderable: false },
                { data: 'employee_name', name: 'employee_name', orderable: false },
                { data: 'clinic_name', name: 'clinic_name', orderable: false },
                { data: 'visit_date', name: 'visit_date', orderable: false, class: 'text-center' },
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
            $(document).on('click', '#btn-search-visit', function () {
                const nationalId = $('#search-national-id').val().trim();
                const clinicId = $('#search-clinic-id').val();
                const $result = $('#search-result');
                $result.empty();

                if (!nationalId) {
                    toastr.warning('الرجاء إدخال رقم الهوية');
                    return;
                }

                $.ajax({
                    url: '{{ route('dashboard.visits.search') }}',
                    method: 'GET',
                    data: { national_id: nationalId, clinic_id: clinicId },
                    success: function (response) {
                        if (response.redirect) {
                            $result.html('<div class="alert alert-info">توجد زيارة مسجّلة لهذا المريض اليوم — جارِ التوجيه...</div>');
                            window.location.href = response.redirect;
                            return;
                        }

                        const quotaColor = response.remaining_quota > 0 ? 'success' : 'danger';
                        $result.html(`
                            <div class="alert alert-${quotaColor}">
                                المريض: <strong>${response.patient_name}</strong> —
                                الرصيد المتبقي هذا الشهر: <strong>${response.remaining_quota} / 2</strong>
                                ${response.remaining_quota > 0 ? '<button type="button" class="btn btn-sm btn-primary ms-3" id="btn-create-visit">تسجيل الزيارة</button>' : ''}
                            </div>
                        `);
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'حدث خطأ أثناء البحث';
                        $result.html(`<div class="alert alert-danger">${message}</div>`);
                    },
                });
            });

            $(document).on('click', '#btn-create-visit', function () {
                $('#create-visit-national-id').val($('#search-national-id').val().trim());
                $('#create-visit-clinic-id').val($('#search-clinic-id').val());
                $('#create-visit-form').trigger('submit');
            });
        </script>
    @endpush
</x-front-layout>
