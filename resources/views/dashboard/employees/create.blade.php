<x-front-layout :title="'إضافة موظف'">
    @php $isEdit = false; @endphp
    <form action="{{ route('dashboard.employees.store') }}" method="post" class="col-12">
        @csrf
        @include('dashboard.employees._form')
    </form>
</x-front-layout>
