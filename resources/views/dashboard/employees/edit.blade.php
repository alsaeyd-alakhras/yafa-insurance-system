<x-front-layout :title="'تعديل موظف'">
    @php $isEdit = true; @endphp
    <form action="{{ route('dashboard.employees.update', $employee) }}" method="post" class="col-12 mb-4">
        @csrf
        @method('put')
        @include('dashboard.employees._form')
    </form>

    <div class="col-12">
        @include('dashboard.employees._dependents')
    </div>
</x-front-layout>
