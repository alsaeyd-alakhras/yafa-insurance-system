@php
    $typeLabels = ['spouse' => 'زوج/ة', 'child' => 'ابن/ة', 'parent' => 'والد/ة'];
    $genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
    $parentTypeLabels = ['father' => 'أب', 'mother' => 'أم'];
    $canManageDependents = auth()->user()->can('create', 'App\Models\Dependent');

    $spouses = $employee->dependents->where('type', 'spouse')->values();
    $children = $employee->dependents->where('type', 'child')->values();
    $parents = $employee->dependents->where('type', 'parent')->values();

    $maxSpouses = $employee->gender === 'male' && $employee->marital_status === 'polygamous' ? 4 : 1;
    $spouseLimitReached = $spouses->count() >= $maxSpouses;
    $hasFather = $parents->contains('parent_type', 'father');
    $hasMother = $parents->contains('parent_type', 'mother');
    $parentLimitReached = $hasFather && $hasMother;

    $dependentSections = [
        'spouse' => [
            'heading' => 'الزوجات/الزوج',
            'add_label' => 'إضافة زوج/ة',
            'empty_message' => 'لا يوجد زوج/ة مضافون بعد.',
            'dependents' => $spouses,
            'add_disabled' => $spouseLimitReached,
        ],
        'child' => [
            'heading' => 'الأبناء',
            'add_label' => 'إضافة ابن/ة',
            'empty_message' => 'لا يوجد أبناء مضافون بعد.',
            'dependents' => $children,
            'add_disabled' => false,
        ],
        'parent' => [
            'heading' => 'الآباء',
            'add_label' => 'إضافة أب/أم',
            'empty_message' => 'لا يوجد آباء مضافون بعد.',
            'dependents' => $parents,
            'add_disabled' => $parentLimitReached,
        ],
    ];
@endphp

@push('styles')
    <style>
        .dependent-card .card-header {
            background: rgba(67, 89, 113, .02);
        }

        .dependent-card .btn-add-dependent:disabled {
            opacity: .5;
        }

        .dependent-row {
            position: relative;
            background: #fff;
            border: 1px solid rgba(67, 89, 113, .16);
            border-right: 4px solid #696cff;
            border-radius: .5rem;
            box-shadow: 0 .125rem .375rem rgba(67, 89, 113, .08);
            padding: 1rem;
        }

        .dependent-row + .dependent-row {
            margin-top: .9rem;
        }

        .dependent-row[data-type="child"] {
            border-right-color: #71dd37;
        }

        .dependent-row[data-type="parent"] {
            border-right-color: #03c3ec;
        }

        .dependent-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .85rem;
            padding-bottom: .6rem;
            border-bottom: 1px dashed rgba(67, 89, 113, .16);
        }

        .dependent-row-title {
            font-weight: 600;
            color: #566a7f;
        }

        .dependent-detail-label {
            display: block;
            margin-bottom: .2rem;
            color: #a1acb8;
            font-size: .75rem;
        }

        .dependent-detail-value {
            color: #566a7f;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .dependent-limit-message {
            margin-top: .75rem;
            padding: .5rem .85rem;
            border-radius: .5rem;
            font-size: .8125rem;
            display: none;
        }

        .dependent-limit-message.is-visible {
            display: block;
        }

        .dependent-limit-message.is-info {
            background: rgba(105, 108, 255, .1);
            color: #696cff;
        }

        .dependent-limit-message.is-warning {
            background: rgba(255, 62, 29, .1);
            color: #ff3e1d;
        }
    </style>
@endpush

@foreach ($dependentSections as $sectionType => $section)
    <div class="card mb-4 dependent-card" id="{{ $sectionType }}-card" data-type="{{ $sectionType }}">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">{{ $section['heading'] }}</h5>
                <span class="badge bg-label-secondary">{{ $section['dependents']->count() }}</span>
            </div>
            @if ($canManageDependents)
                <button
                    type="button"
                    class="btn btn-sm btn-primary btn-add-dependent"
                    data-type="{{ $sectionType }}"
                    @disabled($section['add_disabled'])
                >
                    <i class="fa-solid fa-plus"></i> {{ $section['add_label'] }}
                </button>
            @endif
        </div>
        <div class="card-body">
            @forelse ($section['dependents'] as $dependent)
                <div class="dependent-row" data-type="{{ $sectionType }}">
                    <div class="dependent-row-header">
                        <span class="dependent-row-title">
                            {{ $typeLabels[$sectionType] }} #{{ $loop->iteration }}
                            @if ($sectionType === 'parent' && $dependent->parent_type)
                                <span class="badge bg-label-info ms-1">
                                    {{ $parentTypeLabels[$dependent->parent_type] ?? $dependent->parent_type }}
                                </span>
                            @endif
                        </span>
                        @if ($canManageDependents)
                            <div class="d-flex gap-1">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary btn-edit-dependent"
                                    title="تعديل"
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
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-dependent" title="حذف">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="dependent-detail-label">الاسم</span>
                            <span class="dependent-detail-value">{{ $dependent->full_name }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="dependent-detail-label">رقم الهوية</span>
                            <span class="dependent-detail-value">{{ $dependent->national_id }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="dependent-detail-label">الجنس</span>
                            <span class="dependent-detail-value">
                                {{ $genderLabels[$dependent->gender] ?? $dependent->gender }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0 empty-dependent-message">{{ $section['empty_message'] }}</p>
            @endforelse

            @if ($sectionType === 'spouse')
                <p class="dependent-limit-message is-visible is-info" aria-live="polite">
                    @if ($spouseLimitReached)
                        {{ $maxSpouses === 4 ? 'تم الوصول للحد الأقصى (4 زوجات)' : 'تم الوصول للحد الأقصى (زوج/ة واحدة)' }}
                    @else
                        العدد الحالي: {{ $spouses->count() }} من {{ $maxSpouses }}
                        ({{ $maxSpouses === 4 ? 'الحد الأقصى 4 زوجات' : 'الحد الأقصى زوج/ة واحدة' }})
                    @endif
                </p>
            @elseif ($sectionType === 'child')
                <p class="dependent-limit-message is-visible is-info" aria-live="polite">
                    العدد الحالي: {{ $children->count() }} (لا يوجد حد أقصى)
                </p>
            @else
                <p class="dependent-limit-message is-visible is-info" aria-live="polite">
                    @if ($parentLimitReached)
                        تم الوصول للحد الأقصى (أب واحد وأم واحدة)
                    @else
                        الأب: {{ $hasFather ? 'مضاف' : 'غير مضاف' }}،
                        الأم: {{ $hasMother ? 'مضافة' : 'غير مضافة' }}
                        (يسمح بأب واحد وأم واحدة)
                    @endif
                </p>
            @endif
        </div>
    </div>
@endforeach

@if ($canManageDependents)
    <div class="modal fade" id="dependentModal" tabindex="-1" aria-labelledby="dependentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="dependentModalForm" method="post" class="modal-content">
                @csrf
                <input type="hidden" name="_method" id="dependent-form-method" value="post">
                <input type="hidden" name="type" id="dependent-type">
                <div class="modal-header">
                    <h5 class="modal-title" id="dependentModalLabel">إضافة تابع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="dependent-type-label">نوع التابع</label>
                        <input type="text" class="form-control" id="dependent-type-label" readonly>
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
                const typeInput = document.getElementById('dependent-type');
                const typeLabelInput = document.getElementById('dependent-type-label');
                const fullNameInput = document.getElementById('dependent-full-name');
                const nationalIdInput = document.getElementById('dependent-national-id');
                const genderSelect = document.getElementById('dependent-gender');
                const parentTypeWrapper = document.getElementById('dependent-parent-type-wrapper');
                const parentTypeSelect = document.getElementById('dependent-parent-type');
                const titleEl = document.getElementById('dependentModalLabel');

                const typeLabels = @json($typeLabels);
                const addTitles = {
                    spouse: 'إضافة زوج/ة',
                    child: 'إضافة ابن/ة',
                    parent: 'إضافة أب/أم',
                };
                const editTitles = {
                    spouse: 'تعديل الزوج/ة',
                    child: 'تعديل الابن/ة',
                    parent: 'تعديل الوالد/ة',
                };
                const urlStore = "{{ route('dashboard.employees.dependents.store', $employee) }}";
                const urlUpdateTemplate = "{{ route('dashboard.employees.dependents.update', [$employee, ':id']) }}";

                function setFixedType(type) {
                    typeInput.value = type;
                    typeLabelInput.value = typeLabels[type] || type;
                    toggleParentType();
                }

                function toggleParentType() {
                    const isParent = typeInput.value === 'parent';
                    parentTypeWrapper.style.display = isParent ? 'block' : 'none';
                    parentTypeSelect.required = isParent;

                    if (!isParent) {
                        parentTypeSelect.value = '';
                    }
                }

                document.querySelectorAll('.btn-add-dependent').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (button.disabled) return;

                        const type = button.dataset.type;
                        titleEl.textContent = addTitles[type] || 'إضافة تابع';
                        form.action = urlStore;
                        methodInput.value = 'post';
                        fullNameInput.value = '';
                        nationalIdInput.value = '';
                        genderSelect.value = '';
                        parentTypeSelect.value = '';
                        setFixedType(type);
                        modal.show();
                    });
                });

                document.addEventListener('click', function (event) {
                    const editBtn = event.target.closest('.btn-edit-dependent');
                    if (editBtn) {
                        const type = editBtn.dataset.type;
                        titleEl.textContent = editTitles[type] || 'تعديل التابع';
                        form.action = urlUpdateTemplate.replace(':id', editBtn.dataset.id);
                        methodInput.value = 'put';
                        fullNameInput.value = editBtn.dataset.fullName;
                        nationalIdInput.value = editBtn.dataset.nationalId;
                        genderSelect.value = editBtn.dataset.gender;
                        parentTypeSelect.value = editBtn.dataset.parentType || '';
                        setFixedType(type);
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
