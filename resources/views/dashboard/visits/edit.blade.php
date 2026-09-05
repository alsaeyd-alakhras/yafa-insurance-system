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
                        <li class="mb-2"><strong>العيادة:</strong> <span id="visit-clinic-name">{{ $visit->clinic?->name ?? 'بدون عيادة' }}</span></li>
                        <li class="mb-2"><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date }}</li>
                        <li class="mb-2"><strong>الرصيد المتبقي هذا الشهر:</strong> {{ $quotaOwner?->remainingQuota() }} / 2</li>
                        <li id="total-before-summary" class="mb-2 {{ $visit->total_before_discount === null ? 'd-none' : '' }}">
                            <strong>الإجمالي قبل الخصم:</strong> <span>{{ $visit->total_before_discount !== null ? number_format($visit->total_before_discount, 2) : '' }}</span> ₪
                        </li>
                        <li id="total-after-summary" class="mb-2 {{ $visit->total_after_discount === null ? 'd-none' : '' }}">
                            <strong>الإجمالي بعد الخصم:</strong> <span>{{ $visit->total_after_discount !== null ? number_format($visit->total_after_discount, 2) : '' }}</span> ₪
                        </li>
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
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>القسم</th>
                                        <th>النسبة</th>
                                        <th>المبلغ الأساسي</th>
                                        <th>المبلغ بعد الخصم</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="visit-departments-body">
                                    @if ($visit->visitDepartments->isEmpty())
                                        <tr class="empty-departments-row"><td colspan="5" class="text-center text-muted py-4">لا توجد أقسام مضافة بعد.</td></tr>
                                    @endif
                                    @foreach ($visit->visitDepartments as $vd)
                                        <tr>
                                            <td>{{ $departmentLabels[$vd->medicalDepartment->name] ?? $vd->medicalDepartment->name }}</td>
                                            <td>
                                                @if ($vd->radiology_exam_id !== null)
                                                    خصم ثابت: {{ number_format((float) ($vd->applied_discount_amount ?? 0), 2) }} ₪
                                                @else
                                                    {{ rtrim(rtrim(number_format($vd->applied_discount_percentage, 2), '0'), '.') }}%
                                                @endif
                                            </td>
                                            <td>
                                                @if ($vd->radiology_exam_id !== null)
                                                    <div>{{ $vd->radiologyExam?->name ?? '-' }}</div>
                                                    <small class="text-muted">السعر: {{ $vd->amount_before_discount !== null ? number_format((float) $vd->amount_before_discount, 2) . ' ₪' : '-' }}</small>
                                                @else
                                                    <form
                                                        action="{{ route('dashboard.visits.departments.update-amount', [$visit, $vd]) }}"
                                                        method="post"
                                                        class="department-amount-form d-flex gap-2"
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
                                                @endif
                                            </td>
                                            <td>{{ $vd->amount_after_discount !== null ? number_format($vd->amount_after_discount, 2) . ' ₪' : '-' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-department"
                                                    data-url="{{ route('dashboard.visits.departments.destroy', [$visit, $vd]) }}"
                                                    data-name="{{ $departmentLabels[$vd->medicalDepartment->name] ?? $vd->medicalDepartment->name }}"
                                                    title="حذف القسم">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot id="visit-departments-foot" class="{{ $visit->visitDepartments->isEmpty() ? 'd-none' : '' }}">
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-start">الإجمالي</td>
                                        <td>{{ number_format((float) $visit->visitDepartments->sum('amount_before_discount'), 2) }} ₪</td>
                                        <td>{{ number_format((float) $visit->visitDepartments->sum('amount_after_discount'), 2) }} ₪</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    <div id="add-department-section" class="{{ $medicalDepartments->isEmpty() ? 'd-none' : '' }}">
                        <hr>
                        <h6>إضافة قسم جديد</h6>
                        <form id="add-department-form" action="{{ route('dashboard.visits.departments.store', $visit) }}" method="post" class="row align-items-end">
                            @csrf
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="form-label" for="medical_department_id">القسم الطبي</label>
                                <select class="form-select" name="medical_department_id" id="medical_department_id" required>
                                    <option value="">اختر القسم</option>
                                    @foreach ($medicalDepartments as $department)
                                        <option value="{{ $department->id }}" data-name="{{ $department->name }}">
                                            {{ $departmentLabels[$department->name] ?? $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3 d-none" id="clinic-field-wrapper">
                                <label class="form-label" for="clinic_id">العيادة</label>
                                <select class="form-select" name="clinic_id" id="clinic_id" disabled>
                                    <option value="">اختر العيادة</option>
                                    @foreach ($clinics as $clinic)
                                        <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-12 mb-3 d-none" id="radiology-exam-field-wrapper">
                                <label class="form-label" for="radiology_exam_id">فحص الأشعة</label>
                                <select class="form-select select2-searchable" name="radiology_exam_id" id="radiology_exam_id" data-dropdown-css-class="radiology-exam-select-dropdown" disabled>
                                    <option value="">اختر فحص الأشعة</option>
                                    @foreach ($radiologyExams as $category => $exams)
                                        @if ($category)
                                            <optgroup label="{{ $category }}">
                                                @foreach ($exams as $exam)
                                                    <option value="{{ $exam->id }}">{{ $exam->name }} - {{ number_format((float) $exam->price, 2) }} ₪</option>
                                                @endforeach
                                            </optgroup>
                                        @else
                                            @foreach ($exams as $exam)
                                                <option value="{{ $exam->id }}">{{ $exam->name }} - {{ number_format((float) $exam->price, 2) }} ₪</option>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3" id="amount-field-wrapper">
                                <x-form.input name="amount_before_discount" type="number" step="0.01" min="0" label="المبلغ الأساسي (اختياري)" />
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <button type="submit" class="btn btn-primary w-100">إضافة القسم</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-confirm-modal />

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}">
        <link rel="stylesheet" href="{{ asset('css/searchable-select.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/vendor/libs/select2/select2.full.min.js') }}"></script>
        <script src="{{ asset('js/searchable-select.js') }}"></script>
    @endpush

    @push('scripts')
        <script>
            const departmentLabels = @json($departmentLabels);
            const csrfToken = '{{ csrf_token() }}';

            function formatMoney(value) {
                return value === null || value === undefined ? '-' : `${Number(value).toFixed(2)} ₪`;
            }

            function requestErrorMessage(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    const firstError = Object.values(errors).flat()[0];
                    if (firstError) return firstError;
                }
                return xhr.responseJSON?.message || 'حدث خطأ أثناء تنفيذ العملية.';
            }

            function toggleDepartmentFields() {
                const selectedDepartment = $('#medical_department_id option:selected').data('name');
                const isClinicDepartment = selectedDepartment === 'clinics';
                const isRadiologyDepartment = selectedDepartment === 'radiology';

                $('#clinic-field-wrapper').toggleClass('d-none', !isClinicDepartment);
                $('#clinic_id').prop('disabled', !isClinicDepartment).prop('required', isClinicDepartment);
                if (!isClinicDepartment) $('#clinic_id').val('');

                $('#radiology-exam-field-wrapper').toggleClass('d-none', !isRadiologyDepartment);
                $('#radiology_exam_id').prop('disabled', !isRadiologyDepartment).prop('required', isRadiologyDepartment);
                if (!isRadiologyDepartment) $('#radiology_exam_id').val('').trigger('change');

                $('#amount-field-wrapper').toggleClass('d-none', isRadiologyDepartment);
                $('#amount_before_discount').prop('disabled', isRadiologyDepartment);
                if (isRadiologyDepartment) $('#amount_before_discount').val('');
            }

            function renderVisitState(payload) {
                $('#visit-clinic-name').text(payload.visit.clinic_name || 'بدون عيادة');

                const hasTotals = payload.visit.total_before_discount !== null;
                $('#total-before-summary')
                    .toggleClass('d-none', !hasTotals)
                    .find('span').text(hasTotals ? Number(payload.visit.total_before_discount).toFixed(2) : '');
                $('#total-after-summary')
                    .toggleClass('d-none', !hasTotals)
                    .find('span').text(hasTotals ? Number(payload.visit.total_after_discount).toFixed(2) : '');

                const $body = $('#visit-departments-body').empty();
                if (!payload.departments.length) {
                    $body.html('<tr class="empty-departments-row"><td colspan="5" class="text-center text-muted py-4">لا توجد أقسام مضافة بعد.</td></tr>');
                }

                let totalBefore = 0;
                let totalAfter = 0;

                payload.departments.forEach(function (department) {
                    const $row = $('<tr></tr>');
                    $row.append($('<td></td>').text(departmentLabels[department.name] || department.name));
                    if (department.radiology_exam_name) {
                        $row.append($('<td></td>').text(`خصم ثابت: ${formatMoney(department.applied_discount_amount ?? 0)}`));

                        const $examCell = $('<td></td>')
                            .append($('<div></div>').text(department.radiology_exam_name))
                            .append($('<small class="text-muted"></small>').text(`السعر: ${formatMoney(department.amount_before_discount)}`));
                        $row.append($examCell);
                    } else {
                        $row.append($('<td></td>').text(`${Number(department.applied_discount_percentage).toFixed(2).replace(/\.00$/, '')}%`));

                    const $form = $('<form class="department-amount-form d-flex gap-2" method="post"></form>')
                        .attr('action', department.update_url)
                        .append($('<input type="hidden" name="_token">').val(csrfToken))
                        .append('<input type="hidden" name="_method" value="put">')
                        .append($('<input type="number" step="0.01" min="0" name="amount_before_discount" class="form-control form-control-sm" style="max-width:120px;">')
                            .val(department.amount_before_discount ?? ''))
                        .append('<button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>');
                    $row.append($('<td></td>').append($form));
                    }
                    $row.append($('<td></td>').text(formatMoney(department.amount_after_discount)));

                    totalBefore += Number(department.amount_before_discount) || 0;
                    totalAfter += Number(department.amount_after_discount) || 0;

                    const $deleteButton = $('<button type="button" class="btn btn-sm btn-outline-danger btn-delete-department" title="حذف القسم"><i class="fa-solid fa-trash"></i></button>')
                        .attr('data-url', department.delete_url)
                        .attr('data-name', departmentLabels[department.name] || department.name);
                    $row.append($('<td></td>').append($deleteButton));
                    $body.append($row);
                });

                const $footCells = $('#visit-departments-foot').toggleClass('d-none', !payload.departments.length).find('td');
                $footCells.eq(1).text(formatMoney(totalBefore));
                $footCells.eq(2).text(formatMoney(totalAfter));

                const $departmentSelect = $('#medical_department_id').empty().append('<option value="">اختر القسم</option>');
                payload.available_departments.forEach(function (department) {
                    $departmentSelect.append(
                        $('<option></option>')
                            .val(department.id)
                            .attr('data-name', department.name)
                            .text(departmentLabels[department.name] || department.name)
                    );
                });
                $('#add-department-section').toggleClass('d-none', !payload.available_departments.length);
                toggleDepartmentFields();
            }

            function submitDepartmentForm($form) {
                const $button = $form.find('button[type="submit"]');
                $button.prop('disabled', true);

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function (response) {
                        if ($form.is('#add-department-form')) {
                            $form[0].reset();
                        }
                        renderVisitState(response);
                        toastr.success(response.message);
                    },
                    error: function (xhr) {
                        toastr.error(requestErrorMessage(xhr));
                    },
                    complete: function () {
                        $button.prop('disabled', false);
                    },
                });
            }

            $(document).on('change', '#medical_department_id', toggleDepartmentFields);

            $(document).on('submit', '#add-department-form, .department-amount-form', function (event) {
                event.preventDefault();
                submitDepartmentForm($(this));
            });

            $(document).on('click', '.btn-delete-department', function () {
                const $button = $(this);
                window.confirmAction({
                    title: 'تأكيد حذف القسم',
                    message: `هل أنت متأكد من حذف قسم ${$button.data('name')} من هذه الزيارة؟`,
                    variant: 'danger',
                    onConfirm: function () {
                        $button.prop('disabled', true);
                        $.ajax({
                            url: $button.data('url'),
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            success: function (response) {
                                renderVisitState(response);
                                toastr.success(response.message);
                            },
                            error: function (xhr) {
                                $button.prop('disabled', false);
                                toastr.error(requestErrorMessage(xhr));
                            },
                        });
                    },
                });
            });

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
