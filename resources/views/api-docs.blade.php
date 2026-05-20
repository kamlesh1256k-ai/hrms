@extends('layouts.admin')
@section('page-title', __('Mobile App API Documentation'))
@php
    $mobileApiKey = $mobileApiKey ?? '';
    $mobileApiStatus = $mobileApiStatus ?? 'inactive';
    $isPublicView = $isPublicView ?? false;
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item active">{{ __('API Documentation') }}</li>
@endsection

@push('css-page')
<style>
    .api-card { border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
    .api-card .card-header { padding: 12px 16px; font-weight: 600; font-size: .9rem; }
    .api-endpoint { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
    .api-endpoint:last-child { border-bottom: none; }
    .method-badge { font-size: .7rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-family: monospace; }
    .method-get { background: #dcfce7; color: #166534; }
    .method-post { background: #dbeafe; color: #1e40af; }
    .api-url { font-family: monospace; font-size: .85rem; font-weight: 600; color: #334155; }
    .api-desc { font-size: .8rem; color: #64748b; margin-top: 4px; }
    .param-tbl { font-size: .8rem; margin-top: 8px; }
    .param-tbl th { background: #f8fafc; font-weight: 600; padding: 4px 8px; }
    .param-tbl td { padding: 4px 8px; border-top: 1px solid #f1f5f9; }
    .param-tbl .req { color: #dc2626; font-size: .7rem; }
    .param-tbl .opt { color: #64748b; font-size: .7rem; }
    .resp-box { background: #1e293b; color: #e2e8f0; padding: 12px; border-radius: 8px; font-size: .78rem; font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 300px; margin-top: 8px; }
    .key-box { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 16px; }
    .key-box code { background: #1e293b; color: #fbbf24; padding: 2px 6px; border-radius: 4px; font-size: .85rem; }
    .auth-badge { font-size: .65rem; padding: 2px 6px; border-radius: 3px; }
    .auth-open { background: #dcfce7; color: #166534; }
    .auth-token { background: #fee2e2; color: #991b1b; }
    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #4361ee; }
    .copy-btn { cursor: pointer; font-size: .75rem; }

    /* ── API Tester ── */
    .api-tester { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-top: 10px; display: none; }
    .api-tester.open { display: block; }
    .api-tester label { font-size: .75rem; font-weight: 600; color: #475569; margin-bottom: 2px; }
    .api-tester input, .api-tester textarea { font-size: .8rem; font-family: monospace; }
    .api-tester textarea { min-height: 80px; }
    .try-btn { font-size: .7rem; padding: 2px 10px; border-radius: 12px; cursor: pointer; }
    .try-btn-open { background: #4361ee; color: #fff; border: none; }
    .try-btn-send { background: #059669; color: #fff; border: none; font-weight: 600; }
    .api-resp-live { background: #0f172a; color: #22d3ee; padding: 12px; border-radius: 8px; font-size: .75rem; font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 350px; margin-top: 8px; position: relative; }
    .api-resp-live .resp-status { position: absolute; top: 6px; right: 10px; font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
    .resp-ok { background: #059669; color: #fff; }
    .resp-err { background: #dc2626; color: #fff; }
    .resp-loading { color: #94a3b8; font-style: italic; }
    .saved-token-bar { background: #dcfce7; border: 1px solid #86efac; border-radius: 6px; padding: 6px 12px; font-size: .75rem; margin-bottom: 10px; }
    .saved-token-bar code { background: #166534; color: #bbf7d0; padding: 1px 5px; border-radius: 3px; font-size: .72rem; word-break: break-all; }
    .api-req-sent { background: #1a1a2e; color: #a5b4fc; padding: 12px; border-radius: 8px; font-size: .72rem; font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 250px; margin-top: 8px; border-left: 3px solid #6366f1; }
    .api-req-sent .req-label { color: #818cf8; font-weight: 700; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; }
    .api-req-sent .req-method { color: #34d399; font-weight: 700; }
    .api-req-sent .req-url { color: #fbbf24; }
    .api-req-sent .req-header { color: #94a3b8; }
    .api-req-sent .req-body { color: #e2e8f0; }
    .resp-tabs { display: flex; gap: 0; margin-top: 10px; }
    .resp-tab { padding: 4px 14px; font-size: .72rem; font-weight: 600; cursor: pointer; border: 1px solid #334155; border-bottom: none; border-radius: 6px 6px 0 0; background: #1e293b; color: #94a3b8; }
    .resp-tab.active { background: #0f172a; color: #22d3ee; }
    .resp-tab-content { display: none; }
    .resp-tab-content.active { display: block; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12 mb-3">
        <a href="{{ route('api-docs.postman-download') }}" download class="btn btn-sm btn-primary">
            <i class="ti ti-brand-google me-1"></i>{{ __('Download Postman Collection') }}
        </a>
    </div>
    <div class="col-lg-4 col-12">
        {{-- API Key & Base URL --}}
        <div class="card api-card">
            <div class="card-header bg-warning text-dark"><i class="ti ti-key me-2"></i>{{ __('API Configuration') }}</div>
            <div class="card-body">
                <div class="key-box mb-3">
                    <p class="mb-2 fw-bold">{{ __('Base URL') }}</p>
                    <code id="baseUrl">{{ url('/api/mobile') }}</code>
                    <button class="btn btn-sm btn-outline-dark ms-2 copy-btn" onclick="copyText('baseUrl')"><i class="ti ti-copy"></i></button>
                </div>

                <div class="key-box mb-3">
                    <p class="mb-2 fw-bold">{{ __('Authentication') }}</p>
                    <p class="mb-1" style="font-size:.82rem;">{{ __('Type:') }} <strong>Bearer Token (Sanctum)</strong></p>
                    <p class="mb-1" style="font-size:.82rem;">{{ __('Header:') }} <code>Authorization: Bearer {token}</code></p>
                    <p class="mb-1" style="font-size:.82rem;">{{ __('Header:') }} <code>X-App-Key: {your_app_key}</code></p>
                    <p class="mb-0" style="font-size:.82rem;">{{ __('Get token from') }} <code>POST /login</code></p>
                </div>

                <div class="key-box mb-3">
                    <p class="mb-2 fw-bold">{{ __('Mobile App Access Control') }}</p>
                    <p class="mb-1" style="font-size:.82rem;">
                        {{ __('Status:') }}
                        @if($mobileApiStatus === 'active')
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('Inactive') }}</span>
                        @endif
                    </p>
                    <p class="mb-1" style="font-size:.82rem;">{{ __('Current API Key:') }}</p>
                    <code id="mobileApiKey">{{ $mobileApiKey ?: __('Not generated yet') }}</code>
                    @if(!empty($mobileApiKey))
                        <button class="btn btn-sm btn-outline-dark ms-2 copy-btn" onclick="copyText('mobileApiKey')"><i class="ti ti-copy"></i></button>
                    @endif

                    <form method="POST" action="{{ route('api-docs.mobile-status') }}" class="mt-3">
                        @csrf
                        <div class="d-flex gap-2 align-items-center">
                            <select name="mobile_app_status" class="form-control form-control-sm">
                                <option value="active" {{ $mobileApiStatus === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ $mobileApiStatus === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('api-docs.generate-key') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning w-100">{{ __('Generate New API Key') }}</button>
                    </form>
                </div>

                <div class="key-box mb-3">
                    <p class="mb-2 fw-bold">{{ __('Content Type') }}</p>
                    <code>Content-Type: application/json</code><br>
                    <code>Accept: application/json</code>
                    <p class="mb-0 mt-2" style="font-size:.78rem; color:#7c2d12;">
                        {{ __('Important: All /api/mobile endpoints require X-App-Key header. Protected routes also require bearer token.') }}
                    </p>
                </div>

                <div class="key-box">
                    <p class="mb-2 fw-bold">{{ __('Response Format') }}</p>
                    <div class="resp-box">{
  "success": true|false,
  "message": "...",
  "data": { ... }
}</div>
                </div>
            </div>
        </div>

        {{-- Quick Reference --}}
        <div class="card api-card">
            <div class="card-header bg-primary text-white"><i class="ti ti-list me-2"></i>{{ __('Endpoints Quick Reference') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.78rem;">
                    <thead><tr><th>{{ __('Method') }}</th><th>{{ __('Endpoint') }}</th><th>{{ __('Authentication') }}</th></tr></thead>
                    <tbody>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/login</td><td><span class="auth-badge auth-open">App Key</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/forgot-password</td><td><span class="auth-badge auth-open">App Key</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/logout</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/change-password</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/verify-face</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/dashboard</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/clock-in</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/clock-out</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/attendance-history</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/leave-types</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/leaves</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/leave/apply</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/swipe-request</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/swipe-requests</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/profile</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/fingerprint/enroll</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/fingerprint/verify</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/fingerprint/clock-in</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-get">GET</span></td><td>/fingerprint/status</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                        <tr><td><span class="method-badge method-post">POST</span></td><td>/fingerprint/remove</td><td><span class="auth-badge auth-token">Token</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-12">

        {{-- Saved Token Bar --}}
        <div class="saved-token-bar d-none" id="savedTokenBar">
            <i class="ti ti-check-circle text-success me-1"></i>
            <strong>{{ __('Token saved:') }}</strong> <code id="savedTokenDisplay">—</code>
            <button class="btn btn-sm btn-outline-danger ms-2" onclick="clearSavedToken()" style="font-size:.65rem;padding:1px 6px;">{{ __('Clear') }}</button>
        </div>

        @php
        // All endpoints defined as data for rendering + tester
        $endpoints = [
            ['section' => '1. Authentication', 'icon' => 'ti-lock', 'items' => [
                ['method' => 'POST', 'path' => '/login', 'auth' => 'appkey', 'desc' => 'Login and get bearer token.', 'params' => [
                    ['name' => 'email', 'type' => 'string', 'req' => true, 'desc' => 'Employee email', 'default' => ''],
                    ['name' => 'password', 'type' => 'string', 'req' => true, 'desc' => 'Account password', 'default' => ''],
                    ['name' => 'device_name', 'type' => 'string', 'req' => true, 'desc' => 'Device identifier', 'default' => 'API Tester'],
                ], 'save_token' => true],
                ['method' => 'POST', 'path' => '/logout', 'auth' => 'token', 'desc' => 'Revoke current token and logout.', 'params' => []],
                ['method' => 'POST', 'path' => '/forgot-password', 'auth' => 'appkey', 'desc' => 'Send password reset email.', 'params' => [
                    ['name' => 'email', 'type' => 'string', 'req' => true, 'desc' => 'Registered email', 'default' => ''],
                ]],
                ['method' => 'POST', 'path' => '/change-password', 'auth' => 'token', 'desc' => 'Change password.', 'params' => [
                    ['name' => 'current_password', 'type' => 'string', 'req' => true, 'desc' => 'Current password', 'default' => ''],
                    ['name' => 'new_password', 'type' => 'string', 'req' => true, 'desc' => 'New password (min 6)', 'default' => ''],
                    ['name' => 'new_password_confirmation', 'type' => 'string', 'req' => true, 'desc' => 'Confirm new password', 'default' => ''],
                ]],
            ]],
            ['section' => '2. Face Verification', 'icon' => 'ti-scan', 'items' => [
                ['method' => 'POST', 'path' => '/verify-face', 'auth' => 'token', 'desc' => 'Verify employee face. Send selfie as base64.', 'params' => [
                    ['name' => 'photo', 'type' => 'text', 'req' => true, 'desc' => 'Base64 encoded image (JPEG/PNG)', 'default' => ''],
                ]],
            ]],
            ['section' => '3. Dashboard', 'icon' => 'ti-dashboard', 'items' => [
                ['method' => 'GET', 'path' => '/dashboard', 'auth' => 'token', 'desc' => 'Today\'s attendance, monthly summary, company timings.', 'params' => []],
            ]],
            ['section' => '4. Clock In / Clock Out', 'icon' => 'ti-clock', 'items' => [
                ['method' => 'POST', 'path' => '/clock-in', 'auth' => 'token', 'desc' => 'Clock in with location & optional selfie.', 'params' => [
                    ['name' => 'latitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS latitude', 'default' => ''],
                    ['name' => 'longitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS longitude', 'default' => ''],
                    ['name' => 'address', 'type' => 'string', 'req' => false, 'desc' => 'Readable address', 'default' => ''],
                    ['name' => 'photo', 'type' => 'string', 'req' => false, 'desc' => 'Base64 selfie', 'default' => ''],
                ]],
                ['method' => 'POST', 'path' => '/clock-out', 'auth' => 'token', 'desc' => 'Clock out. Same params as clock-in.', 'params' => [
                    ['name' => 'latitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS latitude', 'default' => ''],
                    ['name' => 'longitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS longitude', 'default' => ''],
                    ['name' => 'address', 'type' => 'string', 'req' => false, 'desc' => 'Readable address', 'default' => ''],
                    ['name' => 'photo', 'type' => 'string', 'req' => false, 'desc' => 'Base64 selfie', 'default' => ''],
                ]],
            ]],
            ['section' => '5. Attendance History', 'icon' => 'ti-calendar', 'items' => [
                ['method' => 'GET', 'path' => '/attendance-history', 'auth' => 'token', 'desc' => 'Attendance history for a month.', 'params' => [
                    ['name' => 'month', 'type' => 'string', 'req' => false, 'desc' => 'YYYY-MM (defaults to current)', 'default' => date('Y-m')],
                ], 'query_params' => true],
            ]],
            ['section' => '6. Leave Management', 'icon' => 'ti-calendar-off', 'items' => [
                ['method' => 'GET', 'path' => '/leave-types', 'auth' => 'token', 'desc' => 'Available leave types (Sick, Casual, etc.)', 'params' => []],
                ['method' => 'GET', 'path' => '/leaves', 'auth' => 'token', 'desc' => 'Employee leave history.', 'params' => [
                    ['name' => 'month', 'type' => 'string', 'req' => false, 'desc' => 'YYYY-MM', 'default' => date('Y-m')],
                ], 'query_params' => true],
                ['method' => 'POST', 'path' => '/leave/apply', 'auth' => 'token', 'desc' => 'Apply for leave.', 'params' => [
                    ['name' => 'leave_type_id', 'type' => 'number', 'req' => true, 'desc' => 'ID from /leave-types', 'default' => '1'],
                    ['name' => 'start_date', 'type' => 'date', 'req' => true, 'desc' => 'YYYY-MM-DD', 'default' => date('Y-m-d')],
                    ['name' => 'end_date', 'type' => 'date', 'req' => true, 'desc' => 'YYYY-MM-DD', 'default' => date('Y-m-d')],
                    ['name' => 'day_type', 'type' => 'string', 'req' => true, 'desc' => 'full_day | first_half | second_half', 'default' => 'full_day'],
                    ['name' => 'leave_reason', 'type' => 'string', 'req' => true, 'desc' => 'Reason for leave', 'default' => 'Test leave'],
                ]],
            ]],
            ['section' => '7. Swipe Request', 'icon' => 'ti-edit', 'items' => [
                ['method' => 'POST', 'path' => '/swipe-request', 'auth' => 'token', 'desc' => 'Request attendance modification.', 'params' => [
                    ['name' => 'date', 'type' => 'string', 'req' => true, 'desc' => 'YYYY-MM-DD', 'default' => ''],
                    ['name' => 'requested_status', 'type' => 'string', 'req' => true, 'desc' => 'Present | Half Day | Leave | Absent', 'default' => 'Present'],
                    ['name' => 'requested_clock_in', 'type' => 'string', 'req' => false, 'desc' => 'HH:MM', 'default' => ''],
                    ['name' => 'requested_clock_out', 'type' => 'string', 'req' => false, 'desc' => 'HH:MM', 'default' => ''],
                    ['name' => 'reason', 'type' => 'string', 'req' => true, 'desc' => 'Reason', 'default' => ''],
                ]],
                ['method' => 'GET', 'path' => '/swipe-requests', 'auth' => 'token', 'desc' => 'History of swipe requests.', 'params' => []],
            ]],
            ['section' => '8. Profile', 'icon' => 'ti-user', 'items' => [
                ['method' => 'GET', 'path' => '/profile', 'auth' => 'token', 'desc' => 'Employee profile, department, designation, shift, manager.', 'params' => []],
            ]],
            ['section' => '9. Fingerprint Biometric', 'icon' => 'ti-fingerprint', 'items' => [
                ['method' => 'POST', 'path' => '/fingerprint/enroll', 'auth' => 'token', 'desc' => 'Enroll/re-enroll the employee\'s fingerprint template. The mobile app should derive a stable hash from the device biometric SDK and send it here.', 'params' => [
                    ['name' => 'template', 'type' => 'string', 'req' => true, 'desc' => 'Device-derived fingerprint template (min 16 chars)', 'default' => ''],
                ]],
                ['method' => 'POST', 'path' => '/fingerprint/verify', 'auth' => 'token', 'desc' => 'Verify a fingerprint template against the stored enrollment.', 'params' => [
                    ['name' => 'template', 'type' => 'string', 'req' => true, 'desc' => 'Template to verify', 'default' => ''],
                ]],
                ['method' => 'POST', 'path' => '/fingerprint/clock-in', 'auth' => 'token', 'desc' => 'Verify fingerprint and clock in in a single call.', 'params' => [
                    ['name' => 'template', 'type' => 'string', 'req' => true, 'desc' => 'Device-derived fingerprint template', 'default' => ''],
                    ['name' => 'latitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS latitude', 'default' => ''],
                    ['name' => 'longitude', 'type' => 'number', 'req' => false, 'desc' => 'GPS longitude', 'default' => ''],
                    ['name' => 'address', 'type' => 'string', 'req' => false, 'desc' => 'Readable address', 'default' => ''],
                ]],
                ['method' => 'GET', 'path' => '/fingerprint/status', 'auth' => 'token', 'desc' => 'Check whether the logged-in employee has a fingerprint enrolled.', 'params' => []],
                ['method' => 'DELETE', 'path' => '/fingerprint/remove', 'auth' => 'token', 'desc' => 'Remove the employee\'s enrolled fingerprint template.', 'params' => []],
            ]],
        ];
        $epIdx = 0;
        @endphp

        @foreach($endpoints as $section)
            <h5 class="section-title mt-4"><i class="ti {{ $section['icon'] }} me-2"></i>{{ __($section['section']) }}</h5>

            @foreach($section['items'] as $ep)
                @php $epIdx++; $epId = 'ep' . $epIdx; @endphp
                <div class="card api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="method-badge {{ $ep['method'] === 'GET' ? 'method-get' : 'method-post' }}">{{ $ep['method'] }}</span>
                            <span class="api-url">{{ $ep['path'] }}</span>
                            <span class="auth-badge {{ $ep['auth'] === 'token' ? 'auth-token' : 'auth-open' }}">{{ $ep['auth'] === 'token' ? 'Token' : 'App Key' }}</span>
                            <button class="try-btn try-btn-open ms-auto" onclick="toggleTester('{{ $epId }}')">
                                <i class="ti ti-player-play me-1"></i>{{ __('Try It') }}
                            </button>
                        </div>
                        <div class="api-desc">{{ $ep['desc'] }}</div>

                        @if(!empty($ep['params']))
                        <table class="table param-tbl">
                            <thead><tr><th>{{ __('Parameter') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('Description') }}</th></tr></thead>
                            <tbody>
                                @foreach($ep['params'] as $p)
                                <tr>
                                    <td><code>{{ $p['name'] }}</code></td>
                                    <td>{{ $p['type'] }}</td>
                                    <td><span class="{{ $p['req'] ? 'req' : 'opt' }}">{{ $p['req'] ? 'Required' : 'Optional' }}</span></td>
                                    <td>{{ $p['desc'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif

                        {{-- ── INLINE API TESTER ── --}}
                        <div class="api-tester" id="{{ $epId }}">
                            <div class="row g-2">
                                @foreach($ep['params'] as $p)
                                <div class="{{ $p['name'] === 'photo' ? 'col-12' : 'col-md-6' }}">
                                    <label>{{ $p['name'] }} @if($p['req'])<span class="text-danger">*</span>@endif</label>
                                    @if($p['name'] === 'photo')
                                        {{-- Camera Capture for photo params --}}
                                        <div class="camera-capture-wrap" id="{{ $epId }}_camera_wrap">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="openCamera('{{ $epId }}')">
                                                    <i class="ti ti-camera me-1"></i>{{ __('Open Camera') }}
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success d-none" id="{{ $epId }}_snap_btn" onclick="snapPhoto('{{ $epId }}')">
                                                    <i class="ti ti-capture me-1"></i>{{ __('Capture Photo') }}
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning d-none" id="{{ $epId }}_retake_btn" onclick="openCamera('{{ $epId }}')">
                                                    <i class="ti ti-refresh me-1"></i>{{ __('Retake') }}
                                                </button>
                                                <label class="btn btn-sm btn-outline-secondary mb-0">
                                                    <i class="ti ti-upload me-1"></i>{{ __('Upload File') }}
                                                    <input type="file" accept="image/*" class="d-none" onchange="uploadPhoto('{{ $epId }}', this)">
                                                </label>
                                            </div>
                                            <div class="position-relative d-inline-block">
                                                <video id="{{ $epId }}_video" width="320" height="240" autoplay playsinline class="d-none" style="border-radius:8px;border:2px solid #4361ee;"></video>
                                                <canvas id="{{ $epId }}_canvas" width="320" height="240" class="d-none"></canvas>
                                                <img id="{{ $epId }}_preview" class="d-none" style="max-width:320px;border-radius:8px;border:2px solid #059669;">
                                            </div>
                                            <div class="mt-1" id="{{ $epId }}_photo_status" style="font-size:.75rem;color:#059669;"></div>
                                        </div>
                                        <textarea class="form-control form-control-sm d-none" data-ep="{{ $epId }}" data-param="{{ $p['name'] }}" placeholder="{{ $p['desc'] }}" id="{{ $epId }}_photo_field">{{ $p['default'] }}</textarea>
                                    @elseif($p['type'] === 'text')
                                        <textarea class="form-control form-control-sm" data-ep="{{ $epId }}" data-param="{{ $p['name'] }}" placeholder="{{ $p['desc'] }}">{{ $p['default'] }}</textarea>
                                    @elseif($p['type'] === 'date')
                                        <input type="date" class="form-control form-control-sm" data-ep="{{ $epId }}" data-param="{{ $p['name'] }}" value="{{ $p['default'] }}" placeholder="{{ $p['desc'] }}">
                                    @else
                                        <input type="text" class="form-control form-control-sm" data-ep="{{ $epId }}" data-param="{{ $p['name'] }}" value="{{ $p['default'] }}" placeholder="{{ $p['desc'] }}">
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <button class="try-btn try-btn-send" onclick="sendRequest('{{ $epId }}', '{{ $ep['method'] }}', '{{ $ep['path'] }}', {{ !empty($ep['query_params']) ? 'true' : 'false' }}, {{ !empty($ep['save_token']) ? 'true' : 'false' }})">
                                    <i class="ti ti-send me-1"></i>{{ __('Send Request') }}
                                </button>
                                <span class="ms-2 resp-loading d-none" id="{{ $epId }}_loading">{{ __('Sending...') }}</span>
                            </div>
                            <div id="{{ $epId }}_response" class="d-none">
                                <div class="resp-tabs">
                                    <div class="resp-tab active" onclick="switchTab('{{ $epId }}', 'resp')">Response</div>
                                    <div class="resp-tab" onclick="switchTab('{{ $epId }}', 'req')">Request Sent</div>
                                </div>
                                <div class="resp-tab-content active" id="{{ $epId }}_tab_resp">
                                    <div class="api-resp-live" style="border-radius:0 8px 8px 8px;">
                                        <span class="resp-status" id="{{ $epId }}_status"></span>
                                        <pre id="{{ $epId }}_body" style="margin:0;white-space:pre-wrap;word-break:break-word;"></pre>
                                    </div>
                                </div>
                                <div class="resp-tab-content" id="{{ $epId }}_tab_req">
                                    <div class="api-req-sent" style="border-radius:0 8px 8px 8px;">
                                        <pre id="{{ $epId }}_reqbody" style="margin:0;white-space:pre-wrap;word-break:break-word;"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        @endforeach

    </div>
</div>
@endsection

@push('script-page')
<script>
var API_BASE = '{{ url("/api/mobile") }}';
var APP_KEY = '{{ $mobileApiKey }}';
var SAVED_TOKEN = localStorage.getItem('hrms_api_token') || '';

// Show saved token bar on load
document.addEventListener('DOMContentLoaded', function() {
    if (SAVED_TOKEN) showTokenBar(SAVED_TOKEN);
});

function copyText(id) {
    var el = document.getElementById(id);
    navigator.clipboard.writeText(el.innerText).then(function() {
        var btn = el.nextElementSibling;
        if (btn) { btn.innerHTML = '<i class="ti ti-check"></i>'; setTimeout(function() { btn.innerHTML = '<i class="ti ti-copy"></i>'; }, 1500); }
    });
}

function toggleTester(epId) {
    var el = document.getElementById(epId);
    el.classList.toggle('open');
}

// ── Camera Capture for photo fields ──
var cameraStreams = {};

function openCamera(epId) {
    var video = document.getElementById(epId + '_video');
    var canvas = document.getElementById(epId + '_canvas');
    var preview = document.getElementById(epId + '_preview');
    var snapBtn = document.getElementById(epId + '_snap_btn');
    var retakeBtn = document.getElementById(epId + '_retake_btn');
    var status = document.getElementById(epId + '_photo_status');

    // Stop any existing stream
    if (cameraStreams[epId]) {
        cameraStreams[epId].getTracks().forEach(function(t) { t.stop(); });
    }

    preview.classList.add('d-none');
    canvas.classList.add('d-none');
    retakeBtn.classList.add('d-none');
    status.textContent = '';

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } })
        .then(function(stream) {
            cameraStreams[epId] = stream;
            video.srcObject = stream;
            video.classList.remove('d-none');
            snapBtn.classList.remove('d-none');
        })
        .catch(function(err) {
            status.style.color = '#dc2626';
            status.textContent = 'Camera error: ' + err.message;
        });
}

function snapPhoto(epId) {
    var video = document.getElementById(epId + '_video');
    var canvas = document.getElementById(epId + '_canvas');
    var preview = document.getElementById(epId + '_preview');
    var snapBtn = document.getElementById(epId + '_snap_btn');
    var retakeBtn = document.getElementById(epId + '_retake_btn');
    var field = document.getElementById(epId + '_photo_field');
    var status = document.getElementById(epId + '_photo_status');

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    var base64 = canvas.toDataURL('image/jpeg', 0.85);
    // Set just the base64 part (without data URI prefix) to the field
    field.value = base64;

    preview.src = base64;
    preview.classList.remove('d-none');

    // Stop camera
    if (cameraStreams[epId]) {
        cameraStreams[epId].getTracks().forEach(function(t) { t.stop(); });
    }
    video.classList.add('d-none');
    snapBtn.classList.add('d-none');
    retakeBtn.classList.remove('d-none');
    status.style.color = '#059669';
    status.textContent = 'Photo captured! Ready to send.';
}

function uploadPhoto(epId, input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var reader = new FileReader();
    reader.onload = function(e) {
        var base64 = e.target.result;
        var field = document.getElementById(epId + '_photo_field');
        var preview = document.getElementById(epId + '_preview');
        var status = document.getElementById(epId + '_photo_status');
        var video = document.getElementById(epId + '_video');
        var snapBtn = document.getElementById(epId + '_snap_btn');
        var retakeBtn = document.getElementById(epId + '_retake_btn');

        field.value = base64;
        preview.src = base64;
        preview.classList.remove('d-none');
        video.classList.add('d-none');
        snapBtn.classList.add('d-none');
        retakeBtn.classList.remove('d-none');

        if (cameraStreams[epId]) {
            cameraStreams[epId].getTracks().forEach(function(t) { t.stop(); });
        }

        status.style.color = '#059669';
        status.textContent = 'Image uploaded (' + file.name + '). Ready to send.';
    };
    reader.readAsDataURL(file);
}

function showTokenBar(token) {
    SAVED_TOKEN = token;
    var bar = document.getElementById('savedTokenBar');
    var display = document.getElementById('savedTokenDisplay');
    bar.classList.remove('d-none');
    display.textContent = token.length > 40 ? token.substring(0, 40) + '...' : token;
}

function clearSavedToken() {
    SAVED_TOKEN = '';
    localStorage.removeItem('hrms_api_token');
    document.getElementById('savedTokenBar').classList.add('d-none');
}

function switchTab(epId, tab) {
    // Toggle tab buttons
    var tabs = document.querySelectorAll('#' + epId + '_response .resp-tab');
    tabs.forEach(function(t) { t.classList.remove('active'); });

    // Toggle tab content
    document.getElementById(epId + '_tab_resp').classList.remove('active');
    document.getElementById(epId + '_tab_req').classList.remove('active');

    if (tab === 'req') {
        tabs[1].classList.add('active');
        document.getElementById(epId + '_tab_req').classList.add('active');
    } else {
        tabs[0].classList.add('active');
        document.getElementById(epId + '_tab_resp').classList.add('active');
    }
}

function sendRequest(epId, method, path, isQueryParams, saveToken) {
    // Collect params
    var inputs = document.querySelectorAll('[data-ep="' + epId + '"]');
    var params = {};
    inputs.forEach(function(el) {
        var val = el.value.trim();
        if (val !== '') {
            params[el.getAttribute('data-param')] = val;
        }
    });

    var url = API_BASE + path;

    // Build query string for GET
    if (method === 'GET' && Object.keys(params).length > 0) {
        var qs = new URLSearchParams(params).toString();
        url += '?' + qs;
    }

    var headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-App-Key': APP_KEY
    };

    if (SAVED_TOKEN) {
        headers['Authorization'] = 'Bearer ' + SAVED_TOKEN;
    }

    var opts = { method: method, headers: headers };
    if (method === 'POST') {
        opts.body = JSON.stringify(params);
    }

    // ── Populate "Request Sent" tab ──
    var reqLines = '';
    reqLines += '<span class="req-label">REQUEST</span>\n';
    reqLines += '<span class="req-method">' + method + '</span> <span class="req-url">' + url + '</span>\n\n';
    reqLines += '<span class="req-label">HEADERS</span>\n';
    Object.keys(headers).forEach(function(k) {
        var v = k === 'Authorization' ? 'Bearer ' + (SAVED_TOKEN ? SAVED_TOKEN.substring(0, 15) + '...' : '') : headers[k];
        reqLines += '<span class="req-header">' + k + ': ' + v + '</span>\n';
    });
    if (method === 'POST' && Object.keys(params).length > 0) {
        reqLines += '\n<span class="req-label">BODY (JSON)</span>\n';
        reqLines += '<span class="req-body">' + JSON.stringify(params, null, 2) + '</span>';
    }
    document.getElementById(epId + '_reqbody').innerHTML = reqLines;

    // UI: show loading, switch to response tab
    document.getElementById(epId + '_loading').classList.remove('d-none');
    document.getElementById(epId + '_response').classList.add('d-none');

    fetch(url, opts)
        .then(function(resp) {
            var statusCode = resp.status;
            var respHeaders = {};
            resp.headers.forEach(function(v, k) { respHeaders[k] = v; });
            return resp.text().then(function(text) {
                return { status: statusCode, text: text, headers: respHeaders };
            });
        })
        .then(function(result) {
            document.getElementById(epId + '_loading').classList.add('d-none');
            document.getElementById(epId + '_response').classList.remove('d-none');

            // Switch to response tab
            switchTab(epId, 'resp');

            var statusEl = document.getElementById(epId + '_status');
            statusEl.textContent = result.status;
            statusEl.className = 'resp-status ' + (result.status >= 200 && result.status < 300 ? 'resp-ok' : 'resp-err');

            var bodyEl = document.getElementById(epId + '_body');
            try {
                var json = JSON.parse(result.text);
                bodyEl.textContent = JSON.stringify(json, null, 2);

                // Auto-save token from login response
                if (saveToken && json.success && json.data && json.data.token) {
                    SAVED_TOKEN = json.data.token;
                    localStorage.setItem('hrms_api_token', SAVED_TOKEN);
                    showTokenBar(SAVED_TOKEN);
                }
            } catch(e) {
                bodyEl.textContent = result.text;
            }
        })
        .catch(function(err) {
            document.getElementById(epId + '_loading').classList.add('d-none');
            document.getElementById(epId + '_response').classList.remove('d-none');
            switchTab(epId, 'resp');
            document.getElementById(epId + '_status').textContent = 'ERR';
            document.getElementById(epId + '_status').className = 'resp-status resp-err';
            document.getElementById(epId + '_body').textContent = 'Network Error: ' + err.message;
        });
}
</script>
@endpush
