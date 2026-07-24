<x-public-layout>
    @php
        $oldDependents = collect(old('dependents', []))->values();
    @endphp

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">استبيان بيانات الموظف والتابعين</h5>
                    <p class="text-muted small mb-0">يرجى تعبئة البيانات بدقة — سيتم مراجعتها من قبل الإدارة قبل التفعيل.</p>
                </div>
            </div>

            <form action="{{ route('survey.store') }}" method="post" id="survey-form">
                @csrf

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">بيانات الموظف</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-4 col-md-6">
                                <x-form.input name="full_name" label="الاسم الكامل" :value="old('full_name')" required />
                            </div>
                            <div class="mb-4 col-md-6">
                                <x-form.input name="national_id" id="national_id" label="رقم الهوية" maxlength="9" :value="old('national_id')" required />
                                <div id="national_id_feedback" class="small mt-1"></div>
                            </div>
                            <div class="mb-4 col-md-6">
                                <x-form.select
                                    name="gender"
                                    label="الجنس"
                                    :options="['male' => 'ذكر', 'female' => 'أنثى']"
                                    :value="old('gender')"
                                    required
                                />
                            </div>
                            <div class="mb-4 col-md-6">
                                <x-form.select
                                    name="marital_status"
                                    id="marital_status"
                                    label="الحالة الزوجية"
                                    :options="[
                                        'single' => 'أعزب/عزباء',
                                        'married' => 'متزوج/ة',
                                        'polygamous' => 'متعدد الزوجات',
                                        'widowed' => 'أرمل/ة',
                                        'divorced' => 'مطلق/ة',
                                    ]"
                                    :value="old('marital_status', 'single')"
                                    required
                                />
                            </div>
                            <div class="mb-4 col-md-6">
                                <x-form.select
                                    name="organization_unit_id"
                                    label="الوحدة التنظيمية"
                                    :optionsId="$organizationUnits"
                                    :value="old('organization_unit_id')"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">الزوجات/الزوج</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="spouse">
                            <i class="fa-solid fa-plus"></i> إضافة زوج/ة
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="spouse-rows" class="dependent-section" data-type="spouse"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد زوج/ة مضافون بعد.</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">الأبناء</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="child">
                            <i class="fa-solid fa-plus"></i> إضافة ابن/ة
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="child-rows" class="dependent-section" data-type="child"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد أبناء مضافون بعد.</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">الآباء</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="parent">
                            <i class="fa-solid fa-plus"></i> إضافة أب/أم
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="parent-rows" class="dependent-section" data-type="parent"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد آباء مضافون بعد.</p>
                    </div>
                </div>

                <div class="d-grid mb-5">
                    <button type="submit" class="btn btn-primary">إرسال البيانات</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let dependentIndex = 0;
                const oldDependents = @json($oldDependents);
                const sections = {
                    spouse: document.getElementById('spouse-rows'),
                    child: document.getElementById('child-rows'),
                    parent: document.getElementById('parent-rows'),
                };
                const employeeGenderSelect = document.getElementById('gender');

                function escapeAttribute(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                function oppositeGender() {
                    if (employeeGenderSelect.value === 'male') return 'female';
                    if (employeeGenderSelect.value === 'female') return 'male';
                    return '';
                }

                function parentGender(parentType) {
                    if (parentType === 'father') return 'male';
                    if (parentType === 'mother') return 'female';
                    return '';
                }

                function updateEmptyMessages() {
                    Object.values(sections).forEach(function (section) {
                        const message = section.parentElement.querySelector('.empty-dependent-message');
                        message.style.display = section.children.length ? 'none' : 'block';
                    });
                }

                function syncSpouseGenders() {
                    document.querySelectorAll('.dependent-row[data-type="spouse"] input[name$="[gender]"]').forEach(function (input) {
                        input.value = oppositeGender();
                    });
                }

                function addDependentRow(type, values = {}) {
                    const index = dependentIndex++;
                    const row = document.createElement('div');
                    row.className = 'row align-items-end border-top pt-3 mt-3 dependent-row';
                    row.dataset.index = index;
                    row.dataset.type = type;

                    const fullName = escapeAttribute(values.full_name);
                    const nationalId = escapeAttribute(values.national_id);
                    const childGender = values.gender || '';
                    const selectedParentType = values.parent_type || '';
                    const hiddenGender = type === 'spouse' ? oppositeGender() : parentGender(selectedParentType);

                    let genderField = `<input type="hidden" name="dependents[${index}][gender]" value="${hiddenGender}">`;
                    if (type === 'child') {
                        genderField = `
                            <div class="col-md-2 mb-3">
                                <label class="form-label">الجنس</label>
                                <select class="form-select" name="dependents[${index}][gender]" required>
                                    <option value="">إختر القيمة</option>
                                    <option value="male" ${childGender === 'male' ? 'selected' : ''}>ذكر</option>
                                    <option value="female" ${childGender === 'female' ? 'selected' : ''}>أنثى</option>
                                </select>
                            </div>
                        `;
                    }

                    const parentTypeField = type === 'parent' ? `
                        <div class="col-md-2 mb-3">
                            <label class="form-label">نوع الوالد</label>
                            <select class="form-select parent-type-select" name="dependents[${index}][parent_type]" required>
                                <option value="">إختر القيمة</option>
                                <option value="father" ${selectedParentType === 'father' ? 'selected' : ''}>أب</option>
                                <option value="mother" ${selectedParentType === 'mother' ? 'selected' : ''}>أم</option>
                            </select>
                        </div>
                    ` : '';

                    row.innerHTML = `
                        <input type="hidden" name="dependents[${index}][type]" value="${type}">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control" name="dependents[${index}][full_name]" value="${fullName}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">رقم الهوية</label>
                            <input type="text" class="form-control dependent-national-id" maxlength="9" name="dependents[${index}][national_id]" value="${nationalId}" required>
                            <div class="small mt-1 dependent-national-id-feedback"></div>
                        </div>
                        ${genderField}
                        ${parentTypeField}
                        <div class="col-md-1 mb-3">
                            <button type="button" class="btn btn-outline-danger btn-remove-dependent-row w-100" title="حذف">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;

                    sections[type].appendChild(row);
                    updateEmptyMessages();
                }

                document.querySelectorAll('.btn-add-dependent-row').forEach(function (button) {
                    button.addEventListener('click', function () {
                        addDependentRow(button.dataset.type);
                    });
                });

                document.addEventListener('change', function (event) {
                    if (event.target === employeeGenderSelect) {
                        syncSpouseGenders();
                        return;
                    }

                    if (event.target.matches('.parent-type-select')) {
                        const row = event.target.closest('.dependent-row');
                        row.querySelector('input[name$="[gender]"]').value = parentGender(event.target.value);
                    }
                });

                document.addEventListener('click', function (event) {
                    if (event.target.closest('.btn-remove-dependent-row')) {
                        event.target.closest('.dependent-row').remove();
                        updateEmptyMessages();
                    }
                });

                function checkNationalId(value, feedbackEl) {
                    feedbackEl.textContent = '';
                    feedbackEl.className = 'small mt-1';

                    if (!value || value.length !== 9) {
                        return;
                    }

                    fetch(`{{ url('survey/check-national-id') }}/${value}`)
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.exists) {
                                feedbackEl.textContent = 'رقم الهوية مستخدم مسبقاً.';
                                feedbackEl.classList.add('text-danger');
                            } else {
                                feedbackEl.textContent = 'رقم الهوية متاح.';
                                feedbackEl.classList.add('text-success');
                            }
                        })
                        .catch(() => {});
                }

                document.getElementById('national_id').addEventListener('blur', function () {
                    checkNationalId(this.value.trim(), document.getElementById('national_id_feedback'));
                });

                document.addEventListener('blur', function (event) {
                    if (event.target.matches('.dependent-national-id')) {
                        const feedbackEl = event.target.closest('.dependent-row').querySelector('.dependent-national-id-feedback');
                        checkNationalId(event.target.value.trim(), feedbackEl);
                    }
                }, true);

                oldDependents.forEach(function (dependent) {
                    if (sections[dependent.type]) {
                        addDependentRow(dependent.type, dependent);
                    }
                });

                updateEmptyMessages();
                syncSpouseGenders();
            });
        </script>
    @endpush
</x-public-layout>
