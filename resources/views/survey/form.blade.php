<x-public-layout>
    @php
        $oldDependents = collect(old('dependents', []))->values();
    @endphp

    @push('styles')
        <style>
            .dependent-card .card-header {
                background: rgba(67, 89, 113, .02);
            }

            .dependent-card.is-section-disabled .card-body {
                opacity: .55;
                pointer-events: none;
                user-select: none;
            }

            .dependent-card .btn-add-dependent-row:disabled {
                opacity: .5;
            }

            .dependent-section-note {
                display: none;
                align-items: center;
                gap: .5rem;
                padding: .65rem .9rem;
                border-radius: .5rem;
                background: rgba(255, 171, 0, .08);
                color: #a17700;
                font-size: .8125rem;
            }

            .dependent-section-note.is-visible {
                display: flex;
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
                                <div id="marital_status_gender_hint" class="small mt-1 text-warning d-none">
                                    لا يمكن اختيار "متعدد الزوجات" لموظفة أنثى، تم تعديل الحالة الزوجية.
                                </div>
                            </div>
                            @php
                                $selectedOrganizationUnitId = old('organization_unit_id', '');
                            @endphp
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="org_unit_center">مركزية</label>
                                <select id="org_unit_center" class="form-select">
                                    <option value="">إختر القيمة</option>
                                    @foreach ($organizationUnits as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="org_unit_department">دائرة</label>
                                <select id="org_unit_department" class="form-select" disabled>
                                    <option value="">إختر القيمة</option>
                                </select>
                            </div>
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="organization_unit_id">قسم</label>
                                <select
                                    id="organization_unit_id"
                                    name="organization_unit_id"
                                    class="form-select @error('organization_unit_id') is-invalid @enderror"
                                    required
                                    disabled>
                                    <option value="">إختر القيمة</option>
                                </select>
                                @error('organization_unit_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 dependent-card" id="spouse-card" data-type="spouse">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <h6 class="mb-0">الزوجات/الزوج</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="spouse">
                            <i class="fa-solid fa-plus"></i> إضافة زوج/ة
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="dependent-section-note" data-note-for="spouse">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>هذا القسم متاح فقط عند اختيار حالة زوجية "متزوج/ة" أو "متعدد الزوجات".</span>
                        </p>
                        <div id="spouse-rows" class="dependent-section" data-type="spouse"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد زوج/ة مضافون بعد.</p>
                        <p class="dependent-limit-message" data-type="spouse" aria-live="polite"></p>
                    </div>
                </div>

                <div class="card mb-4 dependent-card" id="child-card" data-type="child">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <h6 class="mb-0">الأبناء</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="child">
                            <i class="fa-solid fa-plus"></i> إضافة ابن/ة
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="dependent-section-note" data-note-for="child">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>هذا القسم غير متاح عند اختيار حالة زوجية "أعزب/عزباء".</span>
                        </p>
                        <div id="child-rows" class="dependent-section" data-type="child"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد أبناء مضافون بعد.</p>
                    </div>
                </div>

                <div class="card mb-4 dependent-card" id="parent-card" data-type="parent">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <h6 class="mb-0">الآباء</h6>
                        <button type="button" class="btn btn-sm btn-primary btn-add-dependent-row" data-type="parent">
                            <i class="fa-solid fa-plus"></i> إضافة أب/أم
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="parent-rows" class="dependent-section" data-type="parent"></div>
                        <p class="text-muted small mb-0 empty-dependent-message">لا يوجد آباء مضافون بعد.</p>
                        <p class="dependent-limit-message" data-type="parent" aria-live="polite"></p>
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
                const organizationUnits = @json($organizationUnits);
                const selectedOrganizationUnitId = @json((string) $selectedOrganizationUnitId);
                const centerSelect = document.getElementById('org_unit_center');
                const departmentSelect = document.getElementById('org_unit_department');
                const sectionSelect = document.getElementById('organization_unit_id');
                const orgUnitPlaceholder = 'إختر القيمة';

                function resetOrgUnitSelect(select, disabled = true) {
                    select.innerHTML = `<option value="">${orgUnitPlaceholder}</option>`;
                    select.value = '';
                    select.disabled = disabled;
                }

                function addOrgUnitOptions(select, units) {
                    units.forEach(function (unit) {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = unit.name;
                        select.appendChild(option);
                    });
                }

                function findCenter(centerId) {
                    return organizationUnits.find(function (center) {
                        return String(center.id) === String(centerId);
                    });
                }

                function findDepartment(center, departmentId) {
                    return (center?.children || []).find(function (department) {
                        return String(department.id) === String(departmentId);
                    });
                }

                function findOrganizationUnitPath(sectionId) {
                    for (const center of organizationUnits) {
                        for (const department of center.children || []) {
                            const section = (department.children || []).find(function (candidate) {
                                return String(candidate.id) === String(sectionId);
                            });

                            if (section) {
                                return { center, department, section };
                            }
                        }
                    }

                    return null;
                }

                function populateDepartments(centerId, selectedDepartmentId = '') {
                    resetOrgUnitSelect(departmentSelect);
                    resetOrgUnitSelect(sectionSelect);

                    const center = findCenter(centerId);
                    const departments = center?.children || [];
                    if (!departments.length) return;

                    departmentSelect.disabled = false;
                    addOrgUnitOptions(departmentSelect, departments);
                    departmentSelect.value = selectedDepartmentId ? String(selectedDepartmentId) : '';
                }

                function populateSections(centerId, departmentId, selectedSectionId = '') {
                    resetOrgUnitSelect(sectionSelect);

                    const department = findDepartment(findCenter(centerId), departmentId);
                    const sections = department?.children || [];
                    if (!sections.length) return;

                    sectionSelect.disabled = false;
                    addOrgUnitOptions(sectionSelect, sections);
                    sectionSelect.value = selectedSectionId ? String(selectedSectionId) : '';
                }

                centerSelect.addEventListener('change', function () {
                    populateDepartments(this.value);
                });

                departmentSelect.addEventListener('change', function () {
                    populateSections(centerSelect.value, this.value);
                });

                if (selectedOrganizationUnitId) {
                    const path = findOrganizationUnitPath(selectedOrganizationUnitId);
                    if (path) {
                        centerSelect.value = String(path.center.id);
                        populateDepartments(path.center.id, path.department.id);
                        populateSections(path.center.id, path.department.id, path.section.id);
                    }
                }
                const sections = {
                    spouse: document.getElementById('spouse-rows'),
                    child: document.getElementById('child-rows'),
                    parent: document.getElementById('parent-rows'),
                };
                const cards = {
                    spouse: document.getElementById('spouse-card'),
                    child: document.getElementById('child-card'),
                    parent: document.getElementById('parent-card'),
                };
                const employeeGenderSelect = document.getElementById('gender');
                const maritalStatusSelect = document.getElementById('marital_status');
                const maritalStatusHint = document.getElementById('marital_status_gender_hint');
                const employeeNationalIdInput = document.getElementById('national_id');
                const employeeNationalIdFeedback = document.getElementById('national_id_feedback');
                const checkNationalIdUrl = "{{ route('survey.check-national-id', ':nationalId') }}";

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

                function rowsFor(type) {
                    return Array.from(sections[type].children).filter(function (row) {
                        return row.classList.contains('dependent-row');
                    });
                }

                function updateEmptyMessages() {
                    Object.values(sections).forEach(function (section) {
                        const message = section.parentElement.querySelector('.empty-dependent-message');
                        message.style.display = rowsFor(section.dataset.type).length ? 'none' : 'block';
                    });
                }

                function rowTitle(type) {
                    if (type === 'spouse') return 'زوج/ة';
                    if (type === 'child') return 'ابن/ة';
                    return 'والد/والدة';
                }

                function updateRowNumbers(type) {
                    rowsFor(type).forEach(function (row, position) {
                        const title = row.querySelector('.dependent-row-title');
                        if (title) {
                            title.textContent = `${rowTitle(type)} #${position + 1}`;
                        }
                    });
                }

                function updateAllRowNumbers() {
                    Object.keys(sections).forEach(updateRowNumbers);
                }

                function spouseSectionAllowed() {
                    return ['married', 'polygamous'].includes(maritalStatusSelect.value);
                }

                function childSectionAllowed() {
                    return maritalStatusSelect.value !== 'single';
                }

                function spouseLimit() {
                    return employeeGenderSelect.value === 'male' && maritalStatusSelect.value === 'polygamous' ? 4 : 1;
                }

                function setLimitMessage(type, message, tone = 'info') {
                    const messageEl = document.querySelector(`.dependent-limit-message[data-type="${type}"]`);
                    if (!messageEl) return;

                    messageEl.textContent = message;
                    messageEl.classList.toggle('is-visible', Boolean(message));
                    messageEl.classList.toggle('is-warning', tone === 'warning');
                    messageEl.classList.toggle('is-info', tone === 'info');
                }

                function toggleSectionNote(type, visible) {
                    const note = cards[type].querySelector(`.dependent-section-note[data-note-for="${type}"]`);
                    if (note) {
                        note.classList.toggle('is-visible', visible);
                    }
                }

                function updateDependentLimits() {
                    const spouseButton = document.querySelector('.btn-add-dependent-row[data-type="spouse"]');
                    const parentButton = document.querySelector('.btn-add-dependent-row[data-type="parent"]');
                    const childButton = document.querySelector('.btn-add-dependent-row[data-type="child"]');
                    const spouseCount = rowsFor('spouse').length;
                    const childCount = rowsFor('child').length;
                    const parentCount = rowsFor('parent').length;
                    const maxSpouses = spouseLimit();
                    const spouseAllowed = spouseSectionAllowed();
                    const childAllowed = childSectionAllowed();

                    spouseButton.disabled = !spouseAllowed || spouseCount >= maxSpouses;
                    cards.spouse.classList.toggle('is-section-disabled', !spouseAllowed && spouseCount === 0);
                    toggleSectionNote('spouse', !spouseAllowed);

                    if (!spouseAllowed && spouseCount > 0) {
                        setLimitMessage('spouse', 'الحالة الزوجية الحالية لا تسمح بوجود زوج/ة؛ الرجاء حذف السجلات الزائدة أو تعديل الحالة الزوجية.', 'warning');
                    } else if (spouseAllowed && spouseCount > maxSpouses) {
                        setLimitMessage('spouse', maxSpouses === 4
                            ? 'لا يمكن إضافة أكثر من 4 زوجات؛ الرجاء حذف الزوجات الزائدة قبل الإرسال.'
                            : 'الحالة الزوجية الحالية تسمح بزوج/ة واحدة فقط؛ الرجاء حذف الزوجات الزائدة قبل الإرسال.', 'warning');
                    } else if (spouseAllowed && spouseCount === maxSpouses) {
                        setLimitMessage('spouse', maxSpouses === 4 ? 'تم الوصول للحد الأقصى (4 زوجات)' : 'تم الوصول للحد الأقصى (زوج/ة واحدة)', 'info');
                    } else {
                        setLimitMessage('spouse', '');
                    }

                    childButton.disabled = !childAllowed;
                    cards.child.classList.toggle('is-section-disabled', !childAllowed && childCount === 0);
                    toggleSectionNote('child', !childAllowed);

                    parentButton.disabled = parentCount >= 2;
                    setLimitMessage('parent', parentCount >= 2 ? 'تم الوصول للحد الأقصى (أب وأم)' : '', 'info');
                }

                function updateMaritalStatusAvailability() {
                    const polygamousOption = maritalStatusSelect.querySelector('option[value="polygamous"]');
                    if (!polygamousOption) return;

                    const isFemale = employeeGenderSelect.value === 'female';
                    polygamousOption.disabled = isFemale;
                    polygamousOption.hidden = isFemale;

                    if (isFemale && maritalStatusSelect.value === 'polygamous') {
                        maritalStatusSelect.value = 'married';
                        maritalStatusHint?.classList.remove('d-none');
                    } else {
                        maritalStatusHint?.classList.add('d-none');
                    }
                }

                function syncSpouseGenders() {
                    document.querySelectorAll('.dependent-row[data-type="spouse"] input[name$="[gender]"]').forEach(function (input) {
                        input.value = oppositeGender();
                    });
                }

                function addDependentRow(type, values = {}) {
                    const index = dependentIndex++;
                    const row = document.createElement('div');
                    row.className = 'dependent-row';
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
                            <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label class="form-label">نوع الوالد</label>
                            <select class="form-select parent-type-select" name="dependents[${index}][parent_type]" required>
                                <option value="">إختر القيمة</option>
                                <option value="father" ${selectedParentType === 'father' ? 'selected' : ''}>أب</option>
                                <option value="mother" ${selectedParentType === 'mother' ? 'selected' : ''}>أم</option>
                            </select>
                        </div>
                    ` : '';

                    row.innerHTML = `
                        <div class="dependent-row-header">
                            <span class="dependent-row-title"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-dependent-row" title="حذف">
                                <i class="fa-solid fa-trash"></i> حذف
                            </button>
                        </div>
                        <input type="hidden" name="dependents[${index}][type]" value="${type}">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">الاسم</label>
                                <input type="text" class="form-control" name="dependents[${index}][full_name]" value="${fullName}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">رقم الهوية</label>
                                <input type="text" class="form-control dependent-national-id" maxlength="9" name="dependents[${index}][national_id]" value="${nationalId}" required>
                                <div class="small mt-1 dependent-national-id-feedback"></div>
                            </div>
                            ${genderField}
                            ${parentTypeField}
                        </div>
                    `;

                    sections[type].appendChild(row);
                    updateEmptyMessages();
                    updateRowNumbers(type);
                    updateDependentLimits();
                }

                document.querySelectorAll('.btn-add-dependent-row').forEach(function (button) {
                    button.addEventListener('click', function () {
                        updateDependentLimits();
                        if (button.disabled) return;

                        addDependentRow(button.dataset.type);
                    });
                });

                document.addEventListener('change', function (event) {
                    if (event.target === employeeGenderSelect) {
                        updateMaritalStatusAvailability();
                        syncSpouseGenders();
                        updateDependentLimits();
                        return;
                    }

                    if (event.target === maritalStatusSelect) {
                        updateMaritalStatusAvailability();
                        updateDependentLimits();
                        return;
                    }

                    if (event.target.matches('.parent-type-select')) {
                        const row = event.target.closest('.dependent-row');
                        row.querySelector('input[name$="[gender]"]').value = parentGender(event.target.value);
                    }
                });

                document.addEventListener('click', function (event) {
                    if (event.target.closest('.btn-remove-dependent-row')) {
                        const row = event.target.closest('.dependent-row');
                        const type = row.dataset.type;
                        row.remove();
                        updateEmptyMessages();
                        updateRowNumbers(type);
                        updateDependentLimits();
                        recheckAllNationalIds();
                    }
                });

                function allNationalIdInputs() {
                    const inputs = [];
                    if (employeeNationalIdInput) {
                        inputs.push({ input: employeeNationalIdInput, feedback: employeeNationalIdFeedback });
                    }
                    document.querySelectorAll('.dependent-national-id').forEach(function (input) {
                        inputs.push({
                            input: input,
                            feedback: input.closest('.dependent-row')?.querySelector('.dependent-national-id-feedback'),
                        });
                    });
                    return inputs;
                }

                function onPageDuplicateExists(value, currentInput) {
                    return allNationalIdInputs().some(function (entry) {
                        return entry.input !== currentInput && entry.input.value.trim() === value;
                    });
                }

                function checkNationalId(value, feedbackEl, currentInput) {
                    feedbackEl.textContent = '';
                    feedbackEl.className = 'small mt-1';

                    if (!value || value.length !== 9) {
                        return;
                    }

                    if (currentInput && onPageDuplicateExists(value, currentInput)) {
                        feedbackEl.textContent = 'رقم الهوية مكرر ضمن نفس النموذج.';
                        feedbackEl.classList.add('text-danger');
                        return;
                    }

                    fetch(checkNationalIdUrl.replace(':nationalId', encodeURIComponent(value)))
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

                function checkLocalDuplicateOnly(value, feedbackEl, currentInput) {
                    if (!value || value.length !== 9) {
                        return;
                    }

                    if (onPageDuplicateExists(value, currentInput)) {
                        feedbackEl.textContent = 'رقم الهوية مكرر ضمن نفس النموذج.';
                        feedbackEl.className = 'small mt-1 text-danger';
                    }
                }

                function recheckAllNationalIds() {
                    allNationalIdInputs().forEach(function (entry) {
                        if (!entry.feedback) return;

                        const value = entry.input.value.trim();
                        const wasFlaggedDuplicate = entry.feedback.textContent === 'رقم الهوية مكرر ضمن نفس النموذج.';

                        if (wasFlaggedDuplicate && (!value || value.length !== 9 || !onPageDuplicateExists(value, entry.input))) {
                            entry.feedback.textContent = '';
                            entry.feedback.className = 'small mt-1';
                        }

                        checkLocalDuplicateOnly(value, entry.feedback, entry.input);
                    });
                }

                if (employeeNationalIdInput && employeeNationalIdFeedback) {
                    employeeNationalIdInput.addEventListener('blur', function () {
                        checkNationalId(this.value.trim(), employeeNationalIdFeedback, employeeNationalIdInput);
                    });
                    employeeNationalIdInput.addEventListener('input', recheckAllNationalIds);
                }

                document.addEventListener('blur', function (event) {
                    if (event.target.matches('.dependent-national-id')) {
                        const feedbackEl = event.target.closest('.dependent-row').querySelector('.dependent-national-id-feedback');
                        checkNationalId(event.target.value.trim(), feedbackEl, event.target);
                    }
                }, true);

                document.addEventListener('input', function (event) {
                    if (event.target.matches('.dependent-national-id')) {
                        recheckAllNationalIds();
                    }
                });

                oldDependents.forEach(function (dependent) {
                    if (sections[dependent.type]) {
                        addDependentRow(dependent.type, dependent);
                    }
                });

                updateEmptyMessages();
                updateAllRowNumbers();
                updateMaritalStatusAvailability();
                syncSpouseGenders();
                updateDependentLimits();
            });
        </script>
    @endpush
</x-public-layout>
