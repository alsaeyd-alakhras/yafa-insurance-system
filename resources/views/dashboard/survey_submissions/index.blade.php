<x-front-layout :title="'طلبات الاستبيان'">
    @can('update', 'App\Models\SurveySubmission')
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">نافذة استقبال الاستبيان</h5>
                <span class="badge {{ $windowOpen ? 'bg-label-success' : 'bg-label-secondary' }}">
                    {{ $windowOpen ? 'مفتوحة حالياً' : 'مغلقة حالياً' }}
                </span>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.survey-submissions.update-window') }}" method="post" class="row align-items-end">
                    @csrf
                    @method('put')
                    <div class="col-md-4 mb-3">
                        <x-form.input type="date" name="window_start" label="تاريخ البداية" :value="$windowStart" required />
                    </div>
                    <div class="col-md-4 mb-3">
                        <x-form.input type="date" name="window_end" label="تاريخ النهاية" :value="$windowEnd" required />
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" class="btn btn-primary w-100">حفظ النافذة الزمنية</button>
                    </div>
                </form>
                <p class="text-muted small mb-0">
                    رابط الاستبيان العام:
                    <a href="{{ route('survey.show') }}" target="_blank">{{ route('survey.show') }}</a>
                </p>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">طلبات الاستبيان</h5>
            <div class="btn-group">
                <a href="{{ route('dashboard.survey-submissions.index', ['status' => 'pending']) }}"
                    class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">قيد المراجعة</a>
                <a href="{{ route('dashboard.survey-submissions.index', ['status' => 'approved']) }}"
                    class="btn btn-sm {{ $status === 'approved' ? 'btn-primary' : 'btn-outline-primary' }}">موافَق عليها</a>
                <a href="{{ route('dashboard.survey-submissions.index', ['status' => 'rejected']) }}"
                    class="btn btn-sm {{ $status === 'rejected' ? 'btn-primary' : 'btn-outline-primary' }}">مرفوضة</a>
                <a href="{{ route('dashboard.survey-submissions.index', ['status' => 'all']) }}"
                    class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">الكل</a>
            </div>
        </div>
        <div class="card-body">
            @if ($submissions->isEmpty())
                <p class="text-muted mb-0">لا توجد طلبات بهذه الحالة.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>رقم الهوية</th>
                                <th>الحالة</th>
                                <th>تاريخ التقديم</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submissions as $submission)
                                <tr>
                                    <td>{{ $submission->raw_data['full_name'] ?? '-' }}</td>
                                    <td>{{ $submission->national_id }}</td>
                                    <td>
                                        @if ($submission->status === 'pending')
                                            <span class="badge bg-label-warning">قيد المراجعة</span>
                                        @elseif ($submission->status === 'approved')
                                            <span class="badge bg-label-success">موافَق عليها</span>
                                        @else
                                            <span class="badge bg-label-danger">مرفوضة</span>
                                        @endif
                                    </td>
                                    <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('dashboard.survey-submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-eye"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $submissions->links() }}</div>
            @endif
        </div>
    </div>
</x-front-layout>
