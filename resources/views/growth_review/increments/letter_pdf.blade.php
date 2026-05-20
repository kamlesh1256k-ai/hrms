<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body{font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;color:#222;line-height:1.6;margin:40px 50px;}
    .header{text-align:center;margin-bottom:30px;border-bottom:3px solid #4f46e5;padding-bottom:15px;}
    .header h2{margin:0;color:#4f46e5;font-size:22px;}
    .header p{margin:4px 0;color:#666;font-size:12px;}
    .date-ref{display:flex;justify-content:space-between;margin-bottom:25px;font-size:12px;color:#666;}
    .subject{font-weight:700;font-size:15px;margin:20px 0 15px;color:#1f2a44;text-align:center;text-decoration:underline;}
    .body-text{margin-bottom:12px;text-align:justify;}
    .table-wrap{margin:20px 0;}
    table{width:100%;border-collapse:collapse;font-size:12px;}
    th{background:#4f46e5;color:#fff;padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;}
    td{padding:8px 12px;border-bottom:1px solid #e5e7eb;}
    tr:nth-child(even) td{background:#f8fafc;}
    .highlight td{font-weight:700;background:#ede9fe !important;color:#4f46e5;}
    .diff td{color:#16a34a;font-weight:600;}
    .sign-section{margin-top:50px;display:flex;justify-content:space-between;}
    .sign-box{width:45%;}
    .sign-box p{margin:4px 0;font-size:12px;}
    .sign-line{border-top:1px solid #222;margin-top:40px;padding-top:5px;font-size:11px;color:#666;}
    .footer{margin-top:40px;text-align:center;font-size:10px;color:#999;border-top:1px solid #e5e7eb;padding-top:10px;}
</style>
</head>
<body>
    <div class="header">
        <h2>{{ $companyName }}</h2>
        <p>{{ __('Increment / Revision Letter') }}</p>
    </div>

    <div style="margin-bottom:20px;">
        <p style="float:right;font-size:12px;color:#666;">{{ __('Date:') }} {{ now()->format('d M Y') }}</p>
        <div style="clear:both;"></div>
    </div>

    <p class="body-text">
        <strong>{{ __('To,') }}</strong><br>
        {{ $emp->name }}<br>
        {{ __('Employee ID:') }} {{ $emp->employee_id }}<br>
        {{ __('Designation:') }} {{ $emp->designation->name ?? '—' }}
    </p>

    <p class="subject">{{ __('Subject: Salary Revision / Increment Letter') }}</p>

    <p class="body-text">
        {{ __('Dear :name,', ['name' => $emp->name]) }}
    </p>

    <p class="body-text">
        {{ __('We are pleased to inform you that based on your performance review for the cycle ":cycle", your compensation has been revised as follows, effective :date.', [
            'cycle' => $inc->cycle->name ?? '—',
            'date' => $inc->effective_date->format('d M Y'),
        ]) }}
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>{{ __('Particulars') }}</th>
                    <th style="text-align:right;">{{ __('Old (Annual)') }}</th>
                    <th style="text-align:right;">{{ __('New (Annual)') }}</th>
                    <th style="text-align:right;">{{ __('Old (Monthly)') }}</th>
                    <th style="text-align:right;">{{ __('New (Monthly)') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('CTC') }}</td>
                    <td style="text-align:right;">{{ number_format($inc->old_ctc) }}</td>
                    <td style="text-align:right;">{{ number_format($inc->new_ctc) }}</td>
                    <td style="text-align:right;">{{ number_format($oldMonthly) }}</td>
                    <td style="text-align:right;">{{ number_format($newMonthly) }}</td>
                </tr>
                <tr class="highlight">
                    <td>{{ __('Increment') }}</td>
                    <td style="text-align:right;" colspan="2">{{ number_format($inc->increment_amount) }} ({{ $inc->increment_pct }}%)</td>
                    <td style="text-align:right;" colspan="2">{{ number_format($diffMonthly) }} {{ __('per month') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="body-text">
        {{ __('This revision is effective from :date. The revised compensation is subject to applicable statutory deductions (PF, ESIC, PT, TDS etc.) as per government regulations.', [
            'date' => $inc->effective_date->format('d M Y'),
        ]) }}
    </p>

    <p class="body-text">
        {{ __('We appreciate your dedication and contributions to the organization. We look forward to your continued excellence.') }}
    </p>

    <p class="body-text">
        {{ __('Congratulations!') }}
    </p>

    <div style="margin-top:50px;">
        <p style="margin-bottom:40px;">{{ __('Yours sincerely,') }}</p>
        <p style="border-top:1px solid #222;display:inline-block;padding-top:5px;font-size:12px;color:#666;min-width:200px;">
            {{ __('Authorized Signatory') }}<br>
            <strong>{{ $companyName }}</strong>
        </p>
    </div>

    <div style="margin-top:50px;">
        <p style="font-size:12px;color:#666;">{{ __('Employee Acknowledgement:') }}</p>
        <p style="margin-top:30px;border-top:1px solid #222;display:inline-block;padding-top:5px;font-size:12px;color:#666;min-width:200px;">
            {{ $emp->name }}<br>
            {{ __('Date:') }} _______________
        </p>
    </div>

    <div class="footer">
        {{ __('This is a system-generated document.') }} | {{ $companyName }}
    </div>
</body>
</html>
