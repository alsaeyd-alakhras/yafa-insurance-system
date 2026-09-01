<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            direction: rtl;
            font-family: dejavusans, sans-serif;
            font-size: 9px;
            color: #2b2b2b;
        }

        .report-header {
            border-bottom: 2px solid #03c3ec;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .report-header h1 {
            font-size: 17px;
            margin: 0 0 4px;
            color: #1a1a1a;
        }

        .report-header .subtitle {
            font-size: 10px;
            color: #039cc4;
            font-weight: bold;
        }

        .meta {
            color: #666;
            margin-bottom: 12px;
            line-height: 1.8;
            font-size: 8.5px;
        }

        .meta span.label {
            font-weight: bold;
            color: #444;
        }

        table.summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.summary td {
            border: 1px solid #d9dee3;
            padding: 7px 8px;
            background: #eefbfd;
            text-align: center;
        }

        table.summary td .label {
            display: block;
            font-size: 8px;
            color: #777;
            margin-bottom: 2px;
        }

        table.summary td .value {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #1a1a1a;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            border: 1px solid #d9dee3;
            padding: 3px 4px;
            vertical-align: top;
            font-size: 8px;
        }

        table.report th {
            background: #444a5e;
            color: #fff;
            font-weight: bold;
            font-size: 8px;
        }

        table.report tbody tr:nth-child(even) {
            background: #f7f8fc;
        }

        table.report tfoot td {
            background: #dff7fb;
            font-weight: bold;
            border-top: 2px solid #03c3ec;
        }

        .report-footer {
            margin-top: 12px;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>{{ $appName }}</h1>
        <div class="subtitle">تقرير الزيارات</div>
    </div>

    <div class="meta">
        <div><span class="label">تاريخ التصدير:</span> {{ $generatedAt }}</div>
        <div><span class="label">الفلاتر المطبَّقة:</span> {{ $filtersDescription }}</div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="label">إجمالي الزيارات</span>
                <span class="value">{{ $summary['total_visits'] }}</span>
            </td>
            <td>
                <span class="label">إجمالي المبلغ قبل الخصم</span>
                <span class="value">{{ $summary['total_before_discount'] }}</span>
            </td>
            <td>
                <span class="label">إجمالي المبلغ بعد الخصم</span>
                <span class="value">{{ $summary['total_after_discount'] }}</span>
            </td>
            <td>
                <span class="label">متوسط الأقسام لكل زيارة</span>
                <span class="value">{{ $summary['avg_departments_per_visit'] }}</span>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" style="text-align: center;">لا توجد بيانات مطابقة للفلاتر</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align: left;">الإجمالي</td>
                    <td>{{ number_format($totals['total_before_discount'], 2) }}</td>
                    <td>{{ number_format($totals['total_after_discount'], 2) }}</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="report-footer">
        تم إنشاء هذا التقرير آلياً من {{ $appName }} — {{ $generatedAt }}
    </div>
</body>
</html>
