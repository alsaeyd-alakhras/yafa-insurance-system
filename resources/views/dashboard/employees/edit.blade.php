<x-front-layout :title="'تعديل موظف'">
    @php
        $isEdit = true;
        $showSubmit = false;
    @endphp
    <form id="employee-edit-form" action="{{ route('dashboard.employees.update', $employee) }}" method="post" class="col-12 mb-4">
        @csrf
        @method('put')
        @include('dashboard.employees._form')
    </form>

    <div class="col-12">
        @include('dashboard.employees._dependents')

        <div class="d-grid mb-5">
            <button type="submit" form="employee-edit-form" class="btn btn-primary">حفظ التعديلات</button>
        </div>
    </div>
</x-front-layout>
