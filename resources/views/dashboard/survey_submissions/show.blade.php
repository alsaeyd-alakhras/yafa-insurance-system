@php
    $data = $submission->raw_data;
    $genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
    $maritalLabels = [
        'single' => 'أعزب/عزباء',
        'married' => 'متزوج/ة',
        'polygamous' => 'متعدد الزوجات',
        'widowed' => 'أرمل/ة',
        'divorced' => 'مطلق/ة',
    ];
    $typeLabels = ['spouse' => 'زوج/ة', 'child' => 'ابن/ة', 'parent' => 'والد/ة'];
    $parentTypeLabels = ['father' => 'أب', 'mother' => 'أم'];
    $organizationUnitName = \App\Models\OrganizationUnit::find($data['organization_unit_id'] ?? null)?->name ?? '-';
@endphp
<x-front-layout :title="'تفاصيل طلب استبيان'">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">بيانات الموظف</h5>
        </div>
        <div class="card-body">
            <ul class="list-unstyled mb-0">
                <li class="mb-2"><strong>الاسم:</strong> {{ $data['full_name'] ?? '-' }}</li>
                <li class="mb-2"><strong>رقم الهوية:</strong> {{ $data['national_id'] ?? '-' }}</li>
                <li class="mb-2"><strong>الجنس:</strong> {{ $genderLabels[$data['gender'] ?? ''] ?? '-' }}</li>
                <li class="mb-2"><strong>الحالة الزوجية:</strong> {{ $maritalLabels[$data['marital_status'] ?? ''] ?? '-' }}</li>
                <li class="mb-2"><strong>الوحدة التنظيمية:</strong> {{ $organizationUnitName }}</li>
                <li class="mb-2"><strong>حالة الطلب:</strong>
                    @if ($submission->status === 'pending')
                        <span class="badge bg-label-warning">قيد المراجعة</span>
                    @elseif ($submission->status === 'approved')
                        <span class="badge bg-label-success">موافَق عليها</span>
                    @else
                        <span class="badge bg-label-danger">مرفوضة</span>
                    @endif
                </li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">التابعون</h5>
        </div>
        <div class="card-body">
            @if (empty($data['dependents']))
                <p class="text-muted mb-0">لا يوجد تابعون.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>النوع</th>
                                <th>الجنس</th>
                                <th>رقم الهوية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['dependents'] as $dependent)
                                <tr>
                                    <td>{{ $dependent['full_name'] ?? '-' }}</td>
                                    <td>
                                        {{ $typeLabels[$dependent['type'] ?? ''] ?? '-' }}
                                        @if (($dependent['type'] ?? null) === 'parent' && ! empty($dependent['parent_type']))
                                            ({{ $parentTypeLabels[$dependent['parent_type']] ?? '' }})
                                        @endif
                                    </td>
                                    <td>{{ $genderLabels[$dependent['gender'] ?? ''] ?? '-' }}</td>
                                    <td>{{ $dependent['national_id'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($submission->status === 'pending')
        @can('update', 'App\Models\SurveySubmission')
            <div class="d-flex gap-2 mb-5">
                <form action="{{ route('dashboard.survey-submissions.approve', $submission) }}" method="post" class="js-approve-form">
                    @csrf
                    <button type="button" class="btn btn-success btn-approve">
                        <i class="fa-solid fa-check me-1"></i> موافقة
                    </button>
                </form>
                <form action="{{ route('dashboard.survey-submissions.reject', $submission) }}" method="post" class="js-reject-form">
                    @csrf
                    <button type="button" class="btn btn-outline-danger btn-reject">
                        <i class="fa-solid fa-xmark me-1"></i> رفض
                    </button>
                </form>
            </div>

            <x-confirm-modal />

            @push('scripts')
                <script>
                    document.querySelector('.btn-approve')?.addEventListener('click', function () {
                        const form = this.closest('form');
                        window.confirmAction({
                            title: 'تأكيد الموافقة',
                            message: 'سيتم إنشاء سجل موظف رسمي وربطه بالتابعين المذكورين. هل تريد المتابعة؟',
                            variant: 'primary',
                            onConfirm: function () { form.submit(); },
                        });
                    });

                    document.querySelector('.btn-reject')?.addEventListener('click', function () {
                        const form = this.closest('form');
                        window.confirmAction({
                            title: 'تأكيد الرفض',
                            message: 'هل أنت متأكد من رفض هذا الطلب؟',
                            variant: 'danger',
                            onConfirm: function () { form.submit(); },
                        });
                    });
                </script>
            @endpush
        @endcan
    @endif
</x-front-layout>
