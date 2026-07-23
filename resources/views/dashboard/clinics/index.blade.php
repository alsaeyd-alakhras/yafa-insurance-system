<x-front-layout :title="'العيادات'">
    <x-slot:extra_nav>
        @can('create', 'App\Models\Clinic')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="btn-add-clinic">
                    <i class="fa-solid fa-plus fe-16"></i> إضافة عيادة
                </button>
            </div>
        @endcan
    </x-slot:extra_nav>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">العيادات</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>اسم العيادة</th>
                            <th>الحالة</th>
                            @can('update', 'App\Models\Clinic')
                                <th>إجراءات</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clinics as $clinic)
                            <tr>
                                <td>{{ $clinic->name }}</td>
                                <td>
                                    @if ($clinic->is_active)
                                        <span class="badge bg-label-success">مفعّلة</span>
                                    @else
                                        <span class="badge bg-label-secondary">معطّلة</span>
                                    @endif
                                </td>
                                @can('update', 'App\Models\Clinic')
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-edit-clinic"
                                            data-id="{{ $clinic->id }}"
                                            data-name="{{ $clinic->name }}"
                                            data-active="{{ $clinic->is_active ? 1 : 0 }}"
                                        >
                                            <i class="fa-solid fa-pen"></i> تعديل
                                        </button>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد عيادات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create', 'App\Models\Clinic')
        <div class="modal fade" id="clinicModal" tabindex="-1" aria-labelledby="clinicModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="clinicModalForm" method="post" class="modal-content">
                    @csrf
                    <input type="hidden" name="_method" id="clinic-form-method" value="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clinicModalLabel">إضافة عيادة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <x-form.input name="name" id="clinic-name" label="اسم العيادة" required />
                        </div>
                        <div class="mb-3 form-check" id="clinic-active-wrapper" style="display: none;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="clinic-active" name="is_active" value="1">
                            <label class="form-check-label" for="clinic-active">مفعّلة</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    const modalEl = document.getElementById('clinicModal');
                    const modal = new bootstrap.Modal(modalEl);
                    const form = document.getElementById('clinicModalForm');
                    const methodInput = document.getElementById('clinic-form-method');
                    const nameInput = document.getElementById('clinic-name');
                    const activeWrapper = document.getElementById('clinic-active-wrapper');
                    const activeInput = document.getElementById('clinic-active');
                    const titleEl = document.getElementById('clinicModalLabel');

                    const urlStore = "{{ route('dashboard.clinics.store') }}";
                    const urlUpdateTemplate = "{{ route('dashboard.clinics.update', ':id') }}";

                    document.getElementById('btn-add-clinic')?.addEventListener('click', function () {
                        titleEl.textContent = 'إضافة عيادة';
                        form.action = urlStore;
                        methodInput.value = 'post';
                        nameInput.value = '';
                        activeWrapper.style.display = 'none';
                        modal.show();
                    });

                    document.addEventListener('click', function (event) {
                        const editBtn = event.target.closest('.btn-edit-clinic');
                        if (!editBtn) return;

                        titleEl.textContent = 'تعديل العيادة';
                        form.action = urlUpdateTemplate.replace(':id', editBtn.dataset.id);
                        methodInput.value = 'put';
                        nameInput.value = editBtn.dataset.name;
                        activeWrapper.style.display = 'block';
                        activeInput.checked = editBtn.dataset.active === '1';
                        modal.show();
                    });
                })();
            </script>
        @endpush
    @endcan
</x-front-layout>
