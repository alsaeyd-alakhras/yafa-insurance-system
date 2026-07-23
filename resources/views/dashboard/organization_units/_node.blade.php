@php
    $canManage = auth()->user()->can('update', 'App\Models\OrganizationUnit');
@endphp
<li class="mb-2">
    <div class="d-flex align-items-center gap-2 org-unit-node p-2 rounded">
        <i class="fa-solid fa-{{ $unit->level == 1 ? 'building' : ($unit->level == 2 ? 'diagram-project' : 'folder') }} text-muted"></i>
        <span class="fw-semibold">{{ $unit->name }}</span>
        <span class="badge bg-label-secondary">{{ ['مركز', 'دائرة', 'قسم'][$unit->level - 1] ?? $unit->level }}</span>
        @if ($canManage)
            <div class="ms-auto d-flex gap-1">
                <button
                    type="button"
                    class="btn btn-sm btn-icon btn-outline-primary btn-edit-unit"
                    data-id="{{ $unit->id }}"
                    data-name="{{ $unit->name }}"
                    data-parent-id="{{ $unit->parent_id }}"
                    title="تعديل"
                >
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-icon btn-outline-primary btn-add-child-unit"
                    data-parent-id="{{ $unit->id }}"
                    data-parent-name="{{ $unit->name }}"
                    title="إضافة وحدة فرعية"
                >
                    <i class="fa-solid fa-plus"></i>
                </button>
                <form action="{{ route('dashboard.organization-units.destroy', $unit) }}" method="post" class="d-inline js-delete-form">
                    @csrf
                    @method('delete')
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-delete-unit" title="حذف">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
        @endif
    </div>
    @if ($unit->children->isNotEmpty())
        <ul class="list-unstyled ps-4 border-start mt-2">
            @foreach ($unit->children as $child)
                @include('dashboard.organization_units._node', ['unit' => $child])
            @endforeach
        </ul>
    @endif
</li>
