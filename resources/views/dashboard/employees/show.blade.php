<x-front-layout :title="'عرض موظف'">
    @php
        $genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
        $maritalLabels = [
            'single' => 'أعزب/عزباء',
            'married' => 'متزوج/ة',
            'polygamous' => 'متعدد الزوجات',
            'widowed' => 'أرمل/ة',
            'divorced' => 'مطلق/ة',
        ];
        $statusLabels = ['pending' => 'قيد الموافقة', 'active' => 'نشط', 'inactive' => 'غير نشط'];
        $parentTypeLabels = ['father' => 'أب', 'mother' => 'أم'];
        $organizationChain = $employee->organizationUnit?->ancestryChain();
        $dependentGroups = [
            'spouse' => ['title' => 'الزوجات/الزوج', 'empty' => 'لا يوجد زوج/ة مضافون.'],
            'child' => ['title' => 'الأبناء', 'empty' => 'لا يوجد أبناء مضافون.'],
            'parent' => ['title' => 'الآباء', 'empty' => 'لا يوجد آباء مضافون.'],
        ];
    @endphp

    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">بيانات الموظف</h5>
                @can('update', $employee)
                    <a href="{{ route('dashboard.employees.edit', $employee) }}" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-pen"></i> تعديل
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="mb-4 col-md-6">
                        <div class="text-muted small">الاسم الكامل</div>
                        <div class="fw-semibold">{{ $employee->full_name }}</div>
                    </div>
                    <div class="mb-4 col-md-6">
                        <div class="text-muted small">رقم الهوية</div>
                        <div class="fw-semibold">{{ $employee->national_id }}</div>
                    </div>
                    <div class="mb-4 col-md-6">
                        <div class="text-muted small">الجنس</div>
                        <div class="fw-semibold">{{ $genderLabels[$employee->gender] ?? $employee->gender }}</div>
                    </div>
                    <div class="mb-4 col-md-6">
                        <div class="text-muted small">الحالة الزوجية</div>
                        <div class="fw-semibold">{{ $maritalLabels[$employee->marital_status] ?? $employee->marital_status }}</div>
                    </div>
                    <div class="mb-4 col-md-4">
                        <div class="text-muted small">مركزية</div>
                        <div class="fw-semibold">{{ $organizationChain['center']?->name ?? '-' }}</div>
                    </div>
                    <div class="mb-4 col-md-4">
                        <div class="text-muted small">دائرة</div>
                        <div class="fw-semibold">{{ $organizationChain['department']?->name ?? '-' }}</div>
                    </div>
                    <div class="mb-4 col-md-4">
                        <div class="text-muted small">قسم</div>
                        <div class="fw-semibold">{{ $organizationChain['section']?->name ?? '-' }}</div>
                    </div>
                    <div class="mb-4 col-md-6">
                        <div class="text-muted small">حالة الموظف</div>
                        <div class="fw-semibold">{{ $statusLabels[$employee->status] ?? $employee->status }}</div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($dependentGroups as $type => $group)
            @php $groupDependents = $employee->dependents->where('type', $type); @endphp
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ $group['title'] }}</h5>
                </div>
                <div class="card-body">
                    @if ($groupDependents->isEmpty())
                        <p class="text-muted mb-0">{{ $group['empty'] }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>رقم الهوية</th>
                                        <th>الجنس</th>
                                        @if ($type === 'parent')
                                            <th>نوع الوالد</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupDependents as $dependent)
                                        <tr>
                                            <td>{{ $dependent->full_name }}</td>
                                            <td>{{ $dependent->national_id }}</td>
                                            <td>{{ $genderLabels[$dependent->gender] ?? $dependent->gender }}</td>
                                            @if ($type === 'parent')
                                                <td>{{ $parentTypeLabels[$dependent->parent_type] ?? '-' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-front-layout>
