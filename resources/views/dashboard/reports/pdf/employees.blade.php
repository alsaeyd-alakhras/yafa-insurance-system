<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            direction: rtl;
            font-family: dejavusans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 6px;
        }

        .meta {
            color: #555;
            margin-bottom: 10px;
            line-height: 1.7;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary td {
            border: 1px solid #d9dee3;
            padding: 6px;
            background: #f7f8fa;
            font-weight: bold;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            border: 1px solid #d9dee3;
            padding: 5px;
            vertical-align: top;
        }

        table.report th {
            background: #eef1f5;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>{{ $appName }} - تقرير الموظفين</h1>
    <div class="meta">
        <div>تاريخ التصدير: {{ $generatedAt }}</div>
        <div>الفلاتر: {{ $filtersDescription }}</div>
    </div>

    <table class="summary">
        <tr>
            <td>إجمالي الموظفين: {{ $summary['total_employees'] }}</td>
            <td>الموظفون النشطون: {{ $summary['active_count'] }}</td>
            <td>قيد الموافقة: {{ $summary['pending_count'] }}</td>
            <td>إجمالي التابعين: {{ $summary['total_dependents'] }}</td>
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
    </table>
</body>
</html>
