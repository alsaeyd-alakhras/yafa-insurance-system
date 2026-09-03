<x-front-layout :title="'أسعار فحوصات الأشعة'">
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
        </style>
    @endpush

    <x-slot:extra_nav>
        @can('create', 'App\Models\RadiologyExam')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="btn-add-radiology-exam">
                    <i class="fa-solid fa-plus fe-16"></i> إضافة فحص أشعة
                </button>
            </div>
        @endcan
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="50">50</option>
                <option value="100">100</option>
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
            'category' => 'التصنيف',
            'name' => 'اسم الفحص',
            'price' => 'السعر',
            'discount_amount' => 'قيمة الخصم',
            'is_active_label' => 'الحالة',
        ];
        $filterableFields = ['category', 'name', 'is_active_label'];
        $sortableFields = ['category', 'name', 'price', 'discount_amount'];
    @endphp

    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="radiology-exams-table" class="table enhanced-sticky table-striped table-hover" style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th class="{{ $loop->index < 2 ? 'enhanced-sticky' : '' }}">
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

    @can('create', 'App\Models\RadiologyExam')
        <div class="modal fade" id="radiologyExamModal" tabindex="-1" aria-labelledby="radiologyExamModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="radiologyExamModalForm" method="post" class="modal-content">
                    @csrf
                    <input type="hidden" name="_method" id="radiology-exam-form-method" value="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="radiologyExamModalLabel">إضافة فحص أشعة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <x-form.input name="category" id="radiology-exam-category" label="التصنيف" />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="name" id="radiology-exam-name" label="اسم الفحص" required />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="price" id="radiology-exam-price" type="number" step="0.01" min="0" label="السعر" required />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="discount_amount" id="radiology-exam-discount" type="number" step="0.01" min="0" label="قيمة الخصم" />
                        </div>
                        <div class="mb-3 form-check" id="radiology-exam-active-wrapper" style="display: none;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="radiology-exam-active" name="is_active" value="1">
                            <label class="form-check-label" for="radiology-exam-active">مفعّلة</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script src="{{ asset('js/plugins/jquery.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.js') }}"></script>
        <script>
            const tableId = 'radiology-exams-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";
            const _token = "{{ csrf_token() }}";

            const urlIndex = `{{ route('dashboard.radiology-exams.index') }}`;
            const urlFilters = `{{ route('dashboard.radiology-exams.filters', ':column') }}`;

            const abilityEdit = @json(auth()->user()->can('update', 'App\Models\RadiologyExam'));

            const fields = ['#', 'edit', 'category', 'name', 'price', 'discount_amount', 'is_active_label'];

            const columnsTable = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, class: 'enhanced-sticky text-center' },
                {
                    data: 'edit', name: 'edit', orderable: false, searchable: false, class: 'enhanced-sticky',
                    render: function (data, type, row) {
                        if (!abilityEdit) return '';
                        return `<button type="button" class="action-btn btn-edit btn-edit-radiology-exam"
                                    data-id="${data}"
                                    data-category="${row.category === '-' ? '' : escapeHtml(row.category)}"
                                    data-name="${escapeHtml(row.name)}"
                                    data-price="${escapeHtml(row.price)}"
                                    data-discount="${escapeHtml(row.discount_amount)}"
                                    data-active="${row.is_active_label === 'مفعّلة' ? 1 : 0}"
                                    title="تعديل"><i class="fas fa-edit"></i></button>`;
                    },
                },
                { data: 'category', name: 'category', orderable: false },
                { data: 'name', name: 'name', orderable: false },
                { data: 'price', name: 'price', orderable: false, class: 'text-center amount-column' },
                { data: 'discount_amount', name: 'discount_amount', orderable: false, class: 'text-center amount-column' },
                { data: 'is_active_badge', name: 'is_active_label', orderable: false, class: 'text-center' },
            ];

            const sortConfig = { enabled: true };
            let currentSortColumn = '';
            let currentSortDirection = '';
            const SUMMABLE_COLUMNS = { enabled: false, columns: {} };
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            (function () {
                const modalEl = document.getElementById('radiologyExamModal');
                if (!modalEl) return;

                const modal = new bootstrap.Modal(modalEl);
                const form = document.getElementById('radiologyExamModalForm');
                const methodInput = document.getElementById('radiology-exam-form-method');
                const categoryInput = document.getElementById('radiology-exam-category');
                const nameInput = document.getElementById('radiology-exam-name');
                const priceInput = document.getElementById('radiology-exam-price');
                const discountInput = document.getElementById('radiology-exam-discount');
                const activeWrapper = document.getElementById('radiology-exam-active-wrapper');
                const activeInput = document.getElementById('radiology-exam-active');
                const titleEl = document.getElementById('radiologyExamModalLabel');

                const urlStore = "{{ route('dashboard.radiology-exams.store') }}";
                const urlUpdateTemplate = "{{ route('dashboard.radiology-exams.update', ':id') }}";

                document.getElementById('btn-add-radiology-exam')?.addEventListener('click', function () {
                    titleEl.textContent = 'إضافة فحص أشعة';
                    form.action = urlStore;
                    methodInput.value = 'post';
                    categoryInput.value = '';
                    nameInput.value = '';
                    priceInput.value = '';
                    discountInput.value = '';
                    activeWrapper.style.display = 'none';
                    modal.show();
                });

                document.addEventListener('click', function (event) {
                    const editBtn = event.target.closest('.btn-edit-radiology-exam');
                    if (!editBtn) return;

                    titleEl.textContent = 'تعديل فحص الأشعة';
                    form.action = urlUpdateTemplate.replace(':id', editBtn.dataset.id);
                    methodInput.value = 'put';
                    categoryInput.value = editBtn.dataset.category || '';
                    nameInput.value = editBtn.dataset.name;
                    priceInput.value = editBtn.dataset.price;
                    discountInput.value = editBtn.dataset.discount;
                    activeWrapper.style.display = 'block';
                    activeInput.checked = editBtn.dataset.active === '1';
                    modal.show();
                });

                form?.addEventListener('submit', function (event) {
                    event.preventDefault();

                    $.ajax({
                        url: form.action,
                        method: 'POST',
                        data: $(form).serialize(),
                        success: function (response) {
                            modal.hide();
                            $('#' + tableId).DataTable().ajax.reload(null, false);
                            toastr.success(response?.message || 'تم الحفظ.');
                        },
                        error: function (xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const firstError = errors ? Object.values(errors).flat()[0] : null;
                            toastr.error(firstError || xhr.responseJSON?.message || 'حدث خطأ أثناء الحفظ.');
                        },
                    });
                });
            })();
        </script>
    @endpush
</x-front-layout>
