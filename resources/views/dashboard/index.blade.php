@php
    $roleLabels = [
        'admin' => 'أدمن النظام',
        'receptionist' => 'موظف استقبال',
        'super_admin' => 'مدير النظام',
    ];
@endphp
<x-front-layout>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1">مرحباً، {{ auth()->user()?->name }}</h4>
            <p class="text-muted mb-0">{{ $roleLabels[$role] ?? 'مستخدم' }}</p>
        </div>
    </div>

    @if (in_array($role, ['admin', 'super_admin'], true))
        <div class="card">
            <div class="card-header"><h5 class="mb-0">روابط الإدارة</h5></div>
            <div class="card-body d-flex flex-wrap gap-2">
                @can('view', 'App\Models\User')
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-outline-primary btn-sm">المستخدمون</a>
                @endcan
                @can('view', 'App\Models\Constant')
                    <a href="{{ route('dashboard.constants.index') }}" class="btn btn-outline-primary btn-sm">ثوابت النظام</a>
                @endcan
            </div>
        </div>
    @endif
</x-front-layout>
