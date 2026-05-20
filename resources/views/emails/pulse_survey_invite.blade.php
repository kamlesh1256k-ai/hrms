<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $survey->title }}</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f7fafc;padding:24px;margin:0;color:#0f172a;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 14px rgba(15,23,42,.08);">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);color:#fff;padding:24px 26px;">
            <div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;opacity:.9;">
                {{ $isReminder ? __('Reminder') : __('Pulse Survey') }}
            </div>
            <h2 style="margin:6px 0 0 0;font-size:1.35rem;font-weight:700;">{{ $survey->title }}</h2>
        </div>
        <div style="padding:24px 26px;line-height:1.55;font-size:.94rem;">
            <p>{{ __('Hi') }} {{ $employeeName }},</p>

            @if($isReminder)
                <p>{{ __('A quick reminder — this short pulse survey is still open. It only takes a couple of minutes to share your feedback.') }}</p>
            @else
                <p>{{ __('Your feedback matters. We have launched a quick pulse survey — only 3-5 questions, takes under 2 minutes. Your input helps us improve as a team.') }}</p>
            @endif

            @if($survey->description)
                <p style="background:#f8fafc;padding:12px 14px;border-left:3px solid #6366f1;border-radius:6px;font-size:.88rem;color:#475569;">
                    {{ $survey->description }}
                </p>
            @endif

            @if($survey->is_anonymous)
                <p style="font-size:.85rem;color:#6d28d9;">
                    🔒 {{ __('This is an anonymous survey — your identity will not be shown in any report.') }}
                </p>
            @endif

            @if($survey->end_date)
                <p style="font-size:.85rem;color:#64748b;">
                    ⏱ {{ __('Closes on') }} <strong>{{ \Carbon\Carbon::parse($survey->end_date)->format('d M Y') }}</strong>
                </p>
            @endif

            <div style="text-align:center;margin:28px 0;">
                <a href="{{ url('my-surveys/' . $survey->id) }}"
                   style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;display:inline-block;letter-spacing:.3px;">
                    {{ __('Take Survey') }} →
                </a>
            </div>

            <p style="font-size:.8rem;color:#94a3b8;margin-top:24px;text-align:center;">
                {{ __('You are receiving this because the survey was assigned to your team.') }}
            </p>
        </div>
    </div>
</body>
</html>
