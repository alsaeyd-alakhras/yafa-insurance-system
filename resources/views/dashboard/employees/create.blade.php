<x-front-layout :title="'إضافة موظف'">
    @php
        $isEdit = false;
        $showSubmit = false;
    @endphp
    <form action="{{ route('dashboard.employees.store') }}" method="post" class="col-12">
        @csrf
        @include('dashboard.employees._form')
        @include('dashboard.employees._create_dependents')

        <div class="d-grid mb-5">
            <button type="submit" class="btn btn-primary">إضافة الموظف</button>
        </div>
    </form>
</x-front-layout>
