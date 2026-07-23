<x-public-layout>
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
                        <h6 class="mb-0">التابعون</h6>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-dependent-row">
                            <i class="fa-solid fa-plus"></i> إضافة تابع
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="dependent-rows"></div>
                        <p class="text-muted small mb-0" id="no-dependents-message">لا يوجد تابعون مضافون بعد.</p>
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
                const typeLabels = { spouse: 'زوج/ة', child: 'ابن/ة', parent: 'والد/ة' };
                const container = document.getElementById('dependent-rows');
                const noDependentsMessage = document.getElementById('no-dependents-message');

                function updateEmptyMessage() {
                    noDependentsMessage.style.display = container.children.length ? 'none' : 'block';
                }

                function addDependentRow() {
                    const index = dependentIndex++;
                    const row = document.createElement('div');
                    row.className = 'row align-items-end border-top pt-3 mt-3 dependent-row';
                    row.dataset.index = index;
                    row.innerHTML = `
                        <div class="col-md-2 mb-3">
                            <label class="form-label">النوع</label>
                            <select class="form-select" name="dependents[${index}][type]" required>
                                <option value="child">ابن/ة</option>
                                <option value="spouse">زوج/ة</option>
                                <option value="parent">والد/ة</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control" name="dependents[${index}][full_name]" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">رقم الهوية</label>
                            <input type="text" class="form-control dependent-national-id" maxlength="9" name="dependents[${index}][national_id]" required>
                            <div class="small mt-1 dependent-national-id-feedback"></div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">الجنس</label>
                            <select class="form-select" name="dependents[${index}][gender]" required>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3 parent-type-wrapper" style="display: none;">
                            <label class="form-label">النوع</label>
                            <select class="form-select" name="dependents[${index}][parent_type]">
                                <option value="father">أب</option>
                                <option value="mother">أم</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3">
                            <button type="button" class="btn btn-outline-danger btn-remove-dependent-row w-100">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(row);
                    updateEmptyMessage();
                }

                document.getElementById('btn-add-dependent-row').addEventListener('click', addDependentRow);

                container.addEventListener('change', function (event) {
                    if (event.target.matches('select[name$="[type]"]')) {
                        const row = event.target.closest('.dependent-row');
                        const wrapper = row.querySelector('.parent-type-wrapper');
                        wrapper.style.display = event.target.value === 'parent' ? 'block' : 'none';
                    }
                });

                container.addEventListener('click', function (event) {
                    if (event.target.closest('.btn-remove-dependent-row')) {
                        event.target.closest('.dependent-row').remove();
                        updateEmptyMessage();
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

                container.addEventListener('blur', function (event) {
                    if (event.target.matches('.dependent-national-id')) {
                        const feedbackEl = event.target.closest('.dependent-row').querySelector('.dependent-national-id-feedback');
                        checkNationalId(event.target.value.trim(), feedbackEl);
                    }
                }, true);

                updateEmptyMessage();
            });
        </script>
    @endpush
</x-public-layout>
