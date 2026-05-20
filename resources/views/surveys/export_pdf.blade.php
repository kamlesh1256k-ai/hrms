<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $survey->title }}</title>
    <style>
        body{font-family:DejaVu Sans, sans-serif;font-size:12px;color:#111827;}
        h1{font-size:18px;margin:0 0 6px;}
        .muted{color:#6b7280;font-size:11px;}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#f3f4f6;color:#111827;font-size:10px;font-weight:700;margin-right:6px;}
        table{width:100%;border-collapse:collapse;margin-top:12px;}
        th,td{border:1px solid #e5e7eb;padding:6px 8px;vertical-align:top;}
        th{background:#f9fafb;text-transform:uppercase;letter-spacing:.3px;font-size:10px;color:#6b7280;text-align:left;}
        .right{text-align:right;}
        .center{text-align:center;}
    </style>
</head>
<body>
    <h1>{{ $survey->title }}</h1>
    <div class="muted">
        <span class="badge">{{ ucfirst($survey->type) }}</span>
        <span class="badge">{{ ucfirst($survey->status) }}</span>
        <span class="badge">{{ $survey->is_anonymous ? 'Anonymous' : 'Identified' }}</span>
        <span>{{ ($survey->responses_count ?? 0) }} responses</span>
    </div>

    @if(($enpsSummary['total'] ?? 0) > 0)
        <p><strong>eNPS:</strong> {{ ($enpsSummary['score'] ?? 0) > 0 ? '+' : '' }}{{ number_format((float)($enpsSummary['score'] ?? 0), 1) }} (n={{ (int)($enpsSummary['total'] ?? 0) }})</p>
    @endif

    <h3 style="margin:14px 0 6px;">Question-wise analytics</h3>
    <table>
        <thead>
            <tr>
                <th style="width:34px;">#</th>
                <th>Question</th>
                <th style="width:88px;">Type</th>
                <th style="width:70px;" class="center">Answers</th>
                <th style="width:70px;" class="center">Average</th>
            </tr>
        </thead>
        <tbody>
            @foreach($questionStats as $q)
                <tr>
                    <td class="center">{{ $q['order_no'] }}</td>
                    <td>
                        <strong>{{ $q['text'] }}</strong>
                        @if(!empty($q['required'])) <span class="muted">(required)</span> @endif
                        @if(!empty($q['options']))
                            <div class="muted" style="margin-top:4px;">
                                Options: {{ implode(', ', array_map(fn($o) => $o['value'].' ('.$o['total'].')', $q['options'])) }}
                            </div>
                        @endif
                        @if(!empty($q['sentiment']))
                            <div class="muted" style="margin-top:4px;">
                                Sentiment: {{ implode(', ', array_map(fn($s) => $s['sentiment'].' ('.$s['total'].')', $q['sentiment'])) }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $q['type'] }}</td>
                    <td class="center">{{ (int)($q['total'] ?? 0) }}</td>
                    <td class="center">{{ $q['avg'] === null ? '—' : number_format((float)$q['avg'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

