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
            <div class="mb-4 col-md-6">
                <x-form.select
                    name="organization_unit_id"
                    label="الوحدة التنظيمية"
                    :optionsId="$organizationUnits"
                    :value="$employee->organization_unit_id ?? ''"
                    required
                />
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
