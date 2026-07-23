<x-front-layout :title="'الوحدات التنظيمية'">
    <x-slot:extra_nav>
        @can('create', 'App\Models\OrganizationUnit')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="btn-add-root-unit">
                    <i class="fa-solid fa-plus fe-16"></i> إضافة مركز جديد
                </button>
            </div>
        @endcan
    </x-slot:extra_nav>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">الهيكل التنظيمي</h5>
        </div>
        <div class="card-body">
            @if ($units->isEmpty())
                <p class="text-muted mb-0">لا توجد وحدات تنظيمية بعد.</p>
            @else
                <ul class="list-unstyled">
                    @foreach ($units as $unit)
                        @include('dashboard.organization_units._node', ['unit' => $unit])
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @can('create', 'App\Models\OrganizationUnit')
        @include('dashboard.organization_units._modal')
        <x-confirm-modal />

        @push('scripts')
            <script>
                (function () {
                    const modalEl = document.getElementById('unitModal');
                    const modal = new bootstrap.Modal(modalEl);
                    const form = document.getElementById('unitModalForm');
                    const methodInput = document.getElementById('unit-form-method');
                    const nameInput = document.getElementById('unit-name');
                    const parentSelect = document.getElementById('unit-parent-id');
                    const titleEl = document.getElementById('unitModalLabel');

                    const urlStore = "{{ route('dashboard.organization-units.store') }}";
                    const urlUpdateTemplate = "{{ route('dashboard.organization-units.update', ':id') }}";

                    function openForCreate(parentId, parentName) {
                        titleEl.textContent = parentId ? `إضافة وحدة فرعية لـ ${parentName}` : 'إضافة مركز جديد';
                        form.action = urlStore;
                        methodInput.value = 'post';
                        nameInput.value = '';
                        parentSelect.value = parentId || '';
                        modal.show();
                    }

                    function openForEdit(id, name, parentId) {
                        titleEl.textContent = 'تعديل الوحدة التنظيمية';
                        form.action = urlUpdateTemplate.replace(':id', id);
                        methodInput.value = 'put';
                        nameInput.value = name;
                        parentSelect.value = parentId || '';
                        modal.show();
                    }

                    document.getElementById('btn-add-root-unit')?.addEventListener('click', function () {
                        openForCreate(null, null);
                    });

                    document.addEventListener('click', function (event) {
                        const addChildBtn = event.target.closest('.btn-add-child-unit');
                        if (addChildBtn) {
                            openForCreate(addChildBtn.dataset.parentId, addChildBtn.dataset.parentName);
                            return;
                        }

                        const editBtn = event.target.closest('.btn-edit-unit');
                        if (editBtn) {
                            openForEdit(editBtn.dataset.id, editBtn.dataset.name, editBtn.dataset.parentId);
                            return;
                        }

                        const deleteBtn = event.target.closest('.btn-delete-unit');
                        if (deleteBtn) {
                            const deleteForm = deleteBtn.closest('form');
                            window.confirmAction({
                                title: 'تأكيد الحذف',
                                message: 'هل أنت متأكد من حذف هذه الوحدة التنظيمية؟',
                                variant: 'danger',
                                onConfirm: function () {
                                    deleteForm.submit();
                                },
                            });
                        }
                    });
                })();
            </script>
        @endpush
    @endcan
</x-front-layout>
