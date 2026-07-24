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

<div class="card mb-4 dependent-card" id="spouse-card" data-type="spouse">
    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h5 class="mb-0">الزوجات/الزوج</h5>
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
        <h5 class="mb-0">الأبناء</h5>
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
        <h5 class="mb-0">الآباء</h5>
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
            const checkNationalIdUrl = "{{ route('dashboard.employees.check-national-id', ':nationalId') }}";

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

            function checkNationalId(value, feedbackEl) {
                feedbackEl.textContent = '';
                feedbackEl.className = 'small mt-1';

                if (!value || value.length !== 9) {
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

            function buildRow(type, values = {}) {
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

                return row;
            }

            function addDependentRow(type, values = {}) {
                sections[type].appendChild(buildRow(type, values));
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
                }
            });

            if (employeeNationalIdInput && employeeNationalIdFeedback) {
                employeeNationalIdInput.addEventListener('blur', function () {
                    checkNationalId(this.value.trim(), employeeNationalIdFeedback);
                });
            }

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
            updateAllRowNumbers();
            updateMaritalStatusAvailability();
            syncSpouseGenders();
            updateDependentLimits();
        });
    </script>
@endpush
