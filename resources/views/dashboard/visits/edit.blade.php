@php
    $patient = $visit->patientEmployee ?? $visit->patientDependent;
    $quotaOwner = $visit->employee;
    $departmentLabels = [
        'clinics' => 'الكشف الطبي',
        'pharmacy' => 'الصيدلية',
        'laboratory' => 'المختبر',
        'optics' => 'البصريات',
        'dental' => 'الأسنان',
        'radiology' => 'الأشعة',
    ];
@endphp
<x-front-layout :title="'تعديل زيارة #' . $visit->id">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">بيانات الزيارة</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>المريض:</strong> {{ $patient?->full_name }}</li>
                        <li class="mb-2"><strong>الموظف صاحب الرصيد:</strong> {{ $quotaOwner?->full_name }}</li>
                        <li class="mb-2"><strong>العيادة:</strong> {{ $visit->clinic?->name ?? 'بدون عيادة' }}</li>
                        <li class="mb-2"><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date }}</li>
                        <li class="mb-2"><strong>الرصيد المتبقي هذا الشهر:</strong> {{ $quotaOwner?->remainingQuota() }} / 2</li>
                        @if ($visit->total_before_discount !== null)
                            <li class="mb-2"><strong>الإجمالي قبل الخصم:</strong> {{ number_format($visit->total_before_discount, 2) }} ₪</li>
                            <li class="mb-2"><strong>الإجمالي بعد الخصم:</strong> {{ number_format($visit->total_after_discount, 2) }} ₪</li>
                        @endif
                    </ul>

                    @can('delete', $visit)
                        <form action="{{ route('dashboard.visits.destroy', $visit) }}" method="post" class="mt-3 js-delete-visit-form">
                            @csrf
                            @method('delete')
                            <button type="button" class="btn btn-outline-danger w-100 btn-delete-visit">
                                <i class="fa-solid fa-trash me-1"></i> حذف الزيارة
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">الأقسام المضافة للزيارة</h5>
                </div>
                <div class="card-body">
                    @if ($visit->visitDepartments->isEmpty())
                        <p class="text-muted">لا توجد أقسام مضافة بعد.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>القسم</th>
                                        <th>النسبة</th>
                                        <th>المبلغ الأساسي</th>
                                        <th>المبلغ بعد الخصم</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($visit->visitDepartments as $vd)
                                        <tr>
                                            <td>{{ $departmentLabels[$vd->medicalDepartment->name] ?? $vd->medicalDepartment->name }}</td>
                                            <td>{{ rtrim(rtrim(number_format($vd->applied_discount_percentage, 2), '0'), '.') }}%</td>
                                            <td>
                                                <form
                                                    action="{{ route('dashboard.visits.departments.update-amount', [$visit, $vd]) }}"
                                                    method="post"
                                                    class="d-flex gap-2"
                                                >
                                                    @csrf
                                                    @method('put')
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        name="amount_before_discount"
                                                        class="form-control form-control-sm"
                                                        value="{{ $vd->amount_before_discount }}"
                                                        style="max-width: 120px;"
                                                    >
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                                                </form>
                                            </td>
                                            <td>{{ $vd->amount_after_discount !== null ? number_format($vd->amount_after_discount, 2) . ' ₪' : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($medicalDepartments->isNotEmpty())
                        <hr>
                        <h6>إضافة قسم جديد</h6>
                        <form action="{{ route('dashboard.visits.departments.store', $visit) }}" method="post" class="row align-items-end">
                            @csrf
                            <div class="col-md-5 mb-3">
                                <x-form.select
                                    name="medical_department_id"
                                    label="القسم الطبي"
                                    :options="$medicalDepartments->mapWithKeys(fn ($d) => [$d->id => $departmentLabels[$d->name] ?? $d->name])->toArray()"
                                    required
                                />
                            </div>
                            <div class="col-md-4 mb-3">
                                <x-form.input name="amount_before_discount" type="number" step="0.01" min="0" label="المبلغ الأساسي (اختياري)" />
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="submit" class="btn btn-primary w-100">إضافة القسم</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-confirm-modal />

    @push('scripts')
        <script>
            $(document).on('click', '.btn-delete-visit', function () {
                const form = $(this).closest('form')[0];
                window.confirmAction({
                    title: 'تأكيد الحذف',
                    message: 'هل أنت متأكد من حذف هذه الزيارة بالكامل؟ لا يمكن التراجع عن هذا الإجراء.',
                    variant: 'danger',
                    onConfirm: function () {
                        form.submit();
                    },
                });
            });
        </script>
    @endpush
</x-front-layout>
