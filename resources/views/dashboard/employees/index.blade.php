<x-front-layout :title="'الموظفون والتابعون'">
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
        @can('create', 'App\Models\Employee')
            <div class="mx-2 nav-item">
                <a href="{{ route('dashboard.employees.create') }}" class="m-0 text-white btn btn-primary">
                    <i class="fa-solid fa-plus fe-16"></i> إضافة موظف
                </a>
            </div>
        @endcan
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
            'edit' => 'إجراءات',
            'full_name' => 'الاسم',
            'national_id' => 'رقم الهوية',
            'gender' => 'الجنس',
            'marital_status' => 'الحالة الزوجية',
            'organization_center' => 'مركزية',
            'organization_department' => 'دائرة',
            'organization_unit_name' => 'قسم',
            'dependents_count' => 'عدد التابعين',
            'status' => 'الحالة',
        ];
        $filterableFields = ['gender', 'marital_status', 'status', 'organization_unit_name', 'organization_center', 'organization_department'];
        $sortableFields = ['full_name', 'national_id', 'gender', 'marital_status', 'status'];
    @endphp

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="employees-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
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
        <script src="{{ asset('js/plugins/jquery.validate.min.js') }}"></script>
        <script>
            const tableId = 'employees-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";

            const urlIndex = `{{ route('dashboard.employees.index') }}`;
            const urlFilters = `{{ route('dashboard.employees.filters', ':column') }}`;
            const urlShow = `{{ route('dashboard.employees.show', ':id') }}`;
            const urlEdit = `{{ route('dashboard.employees.edit', ':id') }}`;
            const urlDelete = `{{ route('dashboard.employees.destroy', ':id') }}`;

            const abilityView = {{ Auth::user()->can('view', 'App\\Models\\Employee') ? 'true' : 'false' }};
            const abilityEdit = {{ Auth::user()->can('update', 'App\\Models\\Employee') ? 'true' : 'false' }};
            const abilityDelete = {{ Auth::user()->can('delete', 'App\\Models\\Employee') ? 'true' : 'false' }};

            const statusLabels = { pending: 'قيد الموافقة', active: 'نشط', inactive: 'غير نشط' };
            const genderLabels = { male: 'ذكر', female: 'أنثى' };
            const maritalLabels = {
                single: 'أعزب/عزباء',
                married: 'متزوج/ة',
                polygamous: 'متعدد الزوجات',
                widowed: 'أرمل/ة',
                divorced: 'مطلق/ة',
            };

            const fields = ['#', 'edit', 'full_name', 'national_id', 'gender', 'marital_status', 'organization_center', 'organization_department', 'organization_unit_name', 'dependents_count', 'status', 'delete'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                {
                    data: 'edit',
                    name: 'edit',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        const buttons = [];
                        if (abilityView) {
                            buttons.push(`<a href="${urlShow.replace(':id', data)}" class="action-btn btn-view" title="عرض"><i class="fas fa-eye"></i></a>`);
                        }
                        if (abilityEdit) {
                            buttons.push(`<a href="${urlEdit.replace(':id', data)}" class="action-btn btn-edit" title="تعديل"><i class="fas fa-edit"></i></a>`);
                        }
                        return buttons.join(' ');
                    },
                },
                { data: 'full_name', name: 'full_name', orderable: false },
                { data: 'national_id', name: 'national_id', orderable: false, class: 'text-center' },
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
                { data: 'organization_unit_name', name: 'organization_unit_name', orderable: false },
                {
                    data: 'dependents_count', name: 'dependents_count', orderable: false, searchable: false, class: 'text-center',
                    render: function (data) {
                        const count = Number(data) || 0;
                        const cls = count > 0 ? 'dependents-count-badge' : 'dependents-count-badge is-empty';
                        return `<span class="${cls}">${count}</span>`;
                    },
                },
                {
                    data: 'status', name: 'status', orderable: false, class: 'text-center',
                    render: function (data) { return statusLabels[data] || data; },
                },
                {
                    data: 'delete',
                    name: 'delete',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        if (!abilityDelete) return '';
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
    @endpush
</x-front-layout>

