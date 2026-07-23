<x-front-layout :title="'الأقسام الطبية'">
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
