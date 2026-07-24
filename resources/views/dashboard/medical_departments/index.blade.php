<x-front-layout :title="'الأقسام الطبية'">
    <x-slot:extra_nav>
        @can('create', 'App\Models\MedicalDepartment')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="btn-add-department">
                    <i class="fa-solid fa-plus fe-16"></i> إضافة قسم جديد
                </button>
            </div>
        @endcan
    </x-slot:extra_nav>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">الأقسام الطبية المشمولة بالتأمين</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>القسم</th>
                            <th>نسبة الخصم</th>
                            <th>الحد الأقصى لمبلغ الخصم</th>
                            @can('update', 'App\Models\MedicalDepartment')
                                <th>إجراءات</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td>{{ $labels[$department->name] ?? $department->name }}</td>
                                <td>{{ rtrim(rtrim(number_format($department->discount_percentage, 2), '0'), '.') }}%</td>
                                <td>
                                    @if ($department->max_discount_amount !== null)
                                        {{ rtrim(rtrim(number_format($department->max_discount_amount, 2), '0'), '.') }} ₪
                                    @else
                                        <span class="text-muted">بدون حد</span>
                                    @endif
                                </td>
                                @can('update', 'App\Models\MedicalDepartment')
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-edit-department"
                                            data-id="{{ $department->id }}"
                                            data-label="{{ $labels[$department->name] ?? $department->name }}"
                                            data-discount="{{ $department->discount_percentage }}"
                                            data-max="{{ $department->max_discount_amount }}"
                                        >
                                            <i class="fa-solid fa-pen"></i> تعديل
                                        </button>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create', 'App\Models\MedicalDepartment')
        <div class="modal fade" id="departmentCreateModal" tabindex="-1" aria-labelledby="departmentCreateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="departmentCreateModalForm" method="post" action="{{ route('dashboard.medical-departments.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="departmentCreateModalLabel">إضافة قسم جديد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <x-form.input name="name" id="department-create-name" label="اسم القسم" required />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="discount_percentage" id="department-create-discount" type="number" step="0.01" min="0" max="100" label="نسبة الخصم (%)" required />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="max_discount_amount" id="department-create-max" type="number" step="0.01" min="0" label="الحد الأقصى لمبلغ الخصم (اختياري)" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    const modal = new bootstrap.Modal(document.getElementById('departmentCreateModal'));
                    const form = document.getElementById('departmentCreateModalForm');
                    const nameInput = document.getElementById('department-create-name');
                    const discountInput = document.getElementById('department-create-discount');
                    const maxInput = document.getElementById('department-create-max');

                    document.getElementById('btn-add-department')?.addEventListener('click', function () {
                        form.reset();
                        nameInput.value = '';
                        discountInput.value = '';
                        maxInput.value = '';
                        modal.show();
                    });
                })();
            </script>
        @endpush
    @endcan

    @can('update', 'App\Models\MedicalDepartment')
        <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="departmentModalForm" method="post" class="modal-content">
                    @csrf
                    @method('put')
                    <div class="modal-header">
                        <h5 class="modal-title" id="departmentModalLabel">تعديل القسم الطبي</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <x-form.input name="discount_percentage" id="department-discount" type="number" step="0.01" min="0" max="100" label="نسبة الخصم (%)" required />
                        </div>
                        <div class="mb-3">
                            <x-form.input name="max_discount_amount" id="department-max" type="number" step="0.01" min="0" label="الحد الأقصى لمبلغ الخصم (اتركه فارغاً لعدم وجود حد)" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    const modal = new bootstrap.Modal(document.getElementById('departmentModal'));
                    const form = document.getElementById('departmentModalForm');
                    const titleEl = document.getElementById('departmentModalLabel');
                    const discountInput = document.getElementById('department-discount');
                    const maxInput = document.getElementById('department-max');
                    const urlUpdateTemplate = "{{ route('dashboard.medical-departments.update', ':id') }}";

                    document.addEventListener('click', function (event) {
                        const btn = event.target.closest('.btn-edit-department');
                        if (!btn) return;

                        titleEl.textContent = `تعديل القسم الطبي: ${btn.dataset.label}`;
                        form.action = urlUpdateTemplate.replace(':id', btn.dataset.id);
                        discountInput.value = btn.dataset.discount;
                        maxInput.value = btn.dataset.max === 'null' || btn.dataset.max === '' ? '' : btn.dataset.max;
                        modal.show();
                    });
                })();
            </script>
        @endpush
    @endcan
</x-front-layout>
