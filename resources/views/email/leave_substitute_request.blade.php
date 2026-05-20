<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leave Substitute Request</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
    <p>Hello {{ $substitute->name }},</p>
    <p>
        {{ $requester->name }} has requested you as a substitute for a leave.
    </p>
    <p>
        Leave Type: {{ optional($leave->leaveType)->title }}<br>
        Dates: {{ $leave->start_date }} to {{ $leave->end_date }}<br>
        Day Type: {{ ucwords(str_replace('_', ' ', $leave->day_type)) }}
    </p>
    <p>Please respond:</p>
    <p>
        <a href="{{ route('leave.substitute.action', ['leave' => $leave->id, 'token' => $leave->substitute_token, 'action' => 'accept']) }}">Accept</a>
        |
        <a href="{{ route('leave.substitute.action', ['leave' => $leave->id, 'token' => $leave->substitute_token, 'action' => 'reject']) }}">Reject</a>
    </p>
    <p>Thank you.</p>
</body>
</html>
