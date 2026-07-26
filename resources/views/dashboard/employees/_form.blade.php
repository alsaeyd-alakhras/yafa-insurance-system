@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php $showSubmit = $showSubmit ?? true; @endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">بيانات الموظف</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="mb-4 col-md-6">
                <x-form.input name="full_name" label="الاسم الكامل" :value="$employee->full_name ?? ''" required />
            </div>
            <div class="mb-4 col-md-6">
                <x-form.input name="national_id" label="رقم الهوية" maxlength="9" :value="$employee->national_id ?? ''" required />
                @unless ($isEdit)
                    <div id="national_id_feedback" class="small mt-1"></div>
                @endunless
            </div>
            <div class="mb-4 col-md-6">
                <x-form.select
                    name="gender"
                    label="الجنس"
                    :options="['male' => 'ذكر', 'female' => 'أنثى']"
                    :value="$employee->gender ?? ''"
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
                    :value="$employee->marital_status ?? 'single'"
                    required
                />
                <div id="marital_status_gender_hint" class="small mt-1 text-warning d-none">
                    لا يمكن اختيار "متعدد الزوجات" لموظفة أنثى، تم تعديل الحالة الزوجية.
                </div>
            </div>
            @php
                $selectedOrganizationUnitId = old('organization_unit_id', $employee->organization_unit_id ?? '');
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
            @if ($isEdit)
                <div class="mb-4 col-md-6">
                    <x-form.select
                        name="status"
                        label="حالة الموظف"
                        :options="['pending' => 'قيد الموافقة', 'active' => 'نشط', 'inactive' => 'غير نشط']"
                        :value="$employee->status"
                        required
                    />
                </div>
            @endif
        </div>
        @if ($showSubmit)
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'حفظ التعديلات' : 'إضافة الموظف' }}</button>
            </div>
        @endif
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const organizationUnits = @json($organizationUnits);
            const selectedOrganizationUnitId = @json((string) $selectedOrganizationUnitId);
            const centerSelect = document.getElementById('org_unit_center');
            const departmentSelect = document.getElementById('org_unit_department');
            const sectionSelect = document.getElementById('organization_unit_id');
            const placeholder = 'إختر القيمة';

            function resetSelect(select, disabled = true) {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.value = '';
                select.disabled = disabled;
            }

            function addOptions(select, units) {
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

            function findPath(sectionId) {
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
                resetSelect(departmentSelect);
                resetSelect(sectionSelect);

                const center = findCenter(centerId);
                const departments = center?.children || [];
                if (!departments.length) return;

                departmentSelect.disabled = false;
                addOptions(departmentSelect, departments);
                departmentSelect.value = selectedDepartmentId ? String(selectedDepartmentId) : '';
            }

            function populateSections(centerId, departmentId, selectedSectionId = '') {
                resetSelect(sectionSelect);

                const department = findDepartment(findCenter(centerId), departmentId);
                const sections = department?.children || [];
                if (!sections.length) return;

                sectionSelect.disabled = false;
                addOptions(sectionSelect, sections);
                sectionSelect.value = selectedSectionId ? String(selectedSectionId) : '';
            }

            centerSelect.addEventListener('change', function () {
                populateDepartments(this.value);
            });

            departmentSelect.addEventListener('change', function () {
                populateSections(centerSelect.value, this.value);
            });

            if (selectedOrganizationUnitId) {
                const path = findPath(selectedOrganizationUnitId);
                if (path) {
                    centerSelect.value = String(path.center.id);
                    populateDepartments(path.center.id, path.department.id);
                    populateSections(path.center.id, path.department.id, path.section.id);
                }
            }
        });
    </script>
@endpush
