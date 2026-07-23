@php
    $typeLabels = ['spouse' => 'زوج/ة', 'child' => 'ابن/ة', 'parent' => 'والد/ة'];
    $genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
    $parentTypeLabels = ['father' => 'أب', 'mother' => 'أم'];
    $canManageDependents = auth()->user()->can('create', 'App\Models\Dependent');
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">التابعون</h5>
        @if ($canManageDependents)
            <button type="button" class="btn btn-sm btn-primary" id="btn-add-dependent">
                <i class="fa-solid fa-plus"></i> إضافة تابع
            </button>
        @endif
    </div>
    <div class="card-body">
        @if ($employee->dependents->isEmpty())
            <p class="text-muted mb-0">لا يوجد تابعون بعد.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>الجنس</th>
                            <th>رقم الهوية</th>
                            @if ($canManageDependents)
                                <th>إجراءات</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employee->dependents as $dependent)
                            <tr>
                                <td>{{ $dependent->full_name }}</td>
                                <td>
                                    {{ $typeLabels[$dependent->type] ?? $dependent->type }}
                                    @if ($dependent->type === 'parent' && $dependent->parent_type)
                                        ({{ $parentTypeLabels[$dependent->parent_type] ?? '' }})
                                    @endif
                                </td>
                                <td>{{ $genderLabels[$dependent->gender] ?? $dependent->gender }}</td>
                                <td>{{ $dependent->national_id }}</td>
                                @if ($canManageDependents)
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-edit-dependent"
                                            data-id="{{ $dependent->id }}"
                                            data-type="{{ $dependent->type }}"
                                            data-full-name="{{ $dependent->full_name }}"
                                            data-national-id="{{ $dependent->national_id }}"
                                            data-gender="{{ $dependent->gender }}"
                                            data-parent-type="{{ $dependent->parent_type }}"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form
                                            action="{{ route('dashboard.employees.dependents.destroy', [$employee, $dependent]) }}"
                                            method="post"
                                            class="d-inline js-delete-dependent-form"
                                        >
                                            @csrf
                                            @method('delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-dependent">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if ($canManageDependents)
    <div class="modal fade" id="dependentModal" tabindex="-1" aria-labelledby="dependentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="dependentModalForm" method="post" class="modal-content">
                @csrf
                <input type="hidden" name="_method" id="dependent-form-method" value="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="dependentModalLabel">إضافة تابع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <x-form.select
                            name="type"
                            id="dependent-type"
                            label="نوع التابع"
                            :options="$typeLabels"
                            required
                        />
                    </div>
                    <div class="mb-3">
                        <x-form.input name="full_name" id="dependent-full-name" label="الاسم" required />
                    </div>
                    <div class="mb-3">
                        <x-form.input name="national_id" id="dependent-national-id" label="رقم الهوية" maxlength="9" required />
                    </div>
                    <div class="mb-3">
                        <x-form.select
                            name="gender"
                            id="dependent-gender"
                            label="الجنس"
                            :options="$genderLabels"
                            required
                        />
                    </div>
                    <div class="mb-3" id="dependent-parent-type-wrapper" style="display: none;">
                        <x-form.select
                            name="parent_type"
                            id="dependent-parent-type"
                            label="نوع الوالد"
                            :options="$parentTypeLabels"
                        />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-modal />

    @push('scripts')
        <script>
            (function () {
                const modalEl = document.getElementById('dependentModal');
                const modal = new bootstrap.Modal(modalEl);
                const form = document.getElementById('dependentModalForm');
                const methodInput = document.getElementById('dependent-form-method');
                const typeSelect = document.getElementById('dependent-type');
                const fullNameInput = document.getElementById('dependent-full-name');
                const nationalIdInput = document.getElementById('dependent-national-id');
                const genderSelect = document.getElementById('dependent-gender');
                const parentTypeWrapper = document.getElementById('dependent-parent-type-wrapper');
                const parentTypeSelect = document.getElementById('dependent-parent-type');
                const titleEl = document.getElementById('dependentModalLabel');

                const urlStore = "{{ route('dashboard.employees.dependents.store', $employee) }}";
                const urlUpdateTemplate = "{{ route('dashboard.employees.dependents.update', [$employee, ':id']) }}";

                function toggleParentType() {
                    parentTypeWrapper.style.display = typeSelect.value === 'parent' ? 'block' : 'none';
                }
                typeSelect.addEventListener('change', toggleParentType);

                document.getElementById('btn-add-dependent')?.addEventListener('click', function () {
                    titleEl.textContent = 'إضافة تابع';
                    form.action = urlStore;
                    methodInput.value = 'post';
                    fullNameInput.value = '';
                    nationalIdInput.value = '';
                    typeSelect.value = 'child';
                    genderSelect.value = '';
                    parentTypeSelect.value = '';
                    toggleParentType();
                    modal.show();
                });

                document.addEventListener('click', function (event) {
                    const editBtn = event.target.closest('.btn-edit-dependent');
                    if (editBtn) {
                        titleEl.textContent = 'تعديل التابع';
                        form.action = urlUpdateTemplate.replace(':id', editBtn.dataset.id);
                        methodInput.value = 'put';
                        typeSelect.value = editBtn.dataset.type;
                        fullNameInput.value = editBtn.dataset.fullName;
                        nationalIdInput.value = editBtn.dataset.nationalId;
                        genderSelect.value = editBtn.dataset.gender;
                        parentTypeSelect.value = editBtn.dataset.parentType || '';
                        toggleParentType();
                        modal.show();
                        return;
                    }

                    const deleteBtn = event.target.closest('.btn-delete-dependent');
                    if (deleteBtn) {
                        const deleteForm = deleteBtn.closest('form');
                        window.confirmAction({
                            title: 'تأكيد الحذف',
                            message: 'هل أنت متأكد من حذف هذا التابع؟',
                            variant: 'danger',
                            onConfirm: function () {
                                deleteForm.submit();
                            },
                        });
                    }
                });
            })();
        </script>
    @endpush
@endif
