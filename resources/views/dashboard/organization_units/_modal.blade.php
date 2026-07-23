<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="unitModalForm" method="post" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="unit-form-method" value="post">
            <div class="modal-header">
                <h5 class="modal-title" id="unitModalLabel">إضافة وحدة تنظيمية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <x-form.input name="name" id="unit-name" label="اسم الوحدة" required />
                </div>
                <div class="mb-3">
                    <x-form.select
                        name="parent_id"
                        id="unit-parent-id"
                        label="الوحدة الأعلى (اتركها فارغة لمركز جديد)"
                        :optionsId="$allUnits"
                    />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>
