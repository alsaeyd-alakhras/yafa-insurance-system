@props(['title' => 'استبيان بيانات الموظف'])
@include('layouts.partials.head', ['title' => $title, 'template' => 'survey'])
<div class="container py-5">
    <x-alert type="success" />
    <x-alert type="warning" />
    <x-alert type="danger" />
    {{ $slot }}
</div>
@include('layouts.partials.end')
