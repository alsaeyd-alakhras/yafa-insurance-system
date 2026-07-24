@php
    $canManage = auth()->user()->can('update', 'App\Models\OrganizationUnit');
    $levelMeta = [
        1 => ['icon' => 'building', 'badge' => 'bg-label-primary', 'iconClass' => 'org-unit-icon-level-1'],
        2 => ['icon' => 'diagram-project', 'badge' => 'bg-label-info', 'iconClass' => 'org-unit-icon-level-2'],
        3 => ['icon' => 'folder', 'badge' => 'bg-label-success', 'iconClass' => 'org-unit-icon-level-3'],
    ];
    $meta = $levelMeta[$unit->level] ?? $levelMeta[3];
@endphp
<li class="org-unit-item mb-2">
    <div class="d-flex align-items-center gap-2 org-unit-node org-unit-node-level-{{ $unit->level }} p-2 rounded">
        <span class="org-unit-icon {{ $meta['iconClass'] }}">
            <i class="fa-solid fa-{{ $meta['icon'] }}"></i>
        </span>
        <span class="fw-semibold text-truncate">{{ $unit->name }}</span>
        <span class="badge {{ $meta['badge'] }}">{{ ['مركز', 'دائرة', 'قسم'][$unit->level - 1] ?? $unit->level }}</span>
        @if ($canManage)
            <div class="ms-auto d-flex align-items-center gap-1 flex-shrink-0">
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
                @if ($unit->level < 3)
                    <button
                        type="button"
                        class="btn btn-sm btn-icon btn-outline-primary btn-add-child-unit"
                        data-parent-id="{{ $unit->id }}"
                        data-parent-name="{{ $unit->name }}"
                        title="إضافة وحدة فرعية"
                    >
                        <i class="fa-solid fa-plus"></i>
                    </button>
                @else
                    <span class="badge bg-label-secondary text-muted org-unit-depth-limit" title="لا يمكن إضافة مستوى رابع">
                        <i class="fa-solid fa-lock me-1"></i> أعمق مستوى
                    </span>
                @endif
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
        <ul class="list-unstyled org-unit-children mt-2">
            @foreach ($unit->children as $child)
                @include('dashboard.organization_units._node', ['unit' => $child])
            @endforeach
        </ul>
    @endif
</li>
