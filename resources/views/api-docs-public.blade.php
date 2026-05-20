@php
    try {
        $settings = \App\Models\Utility::settings();
    } catch (\Throwable $e) {
        $settings = [];
    }
    $companyName = $settings['company_name'] ?? config('app.name', 'HRMS');
    try {
        $logoPath = \App\Models\Utility::get_file('uploads/logo/');
    } catch (\Throwable $e) {
        $logoPath = '';
    }
    // get_company_logo() references Auth::user()->id which errors for guests — guard it.
    $companyLogo = '';
    if (\Auth::check()) {
        try { $companyLogo = \App\Models\Utility::get_company_logo(); } catch (\Throwable $e) { $companyLogo = ''; }
    } else {
        try { $companyLogo = \App\Models\Utility::getValByName('company_logo'); } catch (\Throwable $e) { $companyLogo = ''; }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('API Documentation') }} — {{ $companyName }}</title>
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .top-bar { background: linear-gradient(135deg, #1e293b 0%, #4361ee 100%); color: #fff; padding: 24px 0; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .top-bar h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .top-bar p { font-size: .85rem; opacity: .85; margin: 4px 0 0; }
        .top-bar .brand-logo { max-height: 44px; margin-right: 14px; border-radius: 6px; background: #fff; padding: 4px; }
        .docs-container { max-width: 1200px; margin: 32px auto; padding: 0 16px; }
        .api-card { border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 16px; background: #fff; }
        .api-card .card-header { padding: 12px 16px; font-weight: 600; font-size: .9rem; border-bottom: 1px solid #e2e8f0; border-radius: 10px 10px 0 0; }
        .api-endpoint { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
        .api-endpoint:last-child { border-bottom: none; }
        .method-badge { font-size: .7rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-family: monospace; }
        .method-get { background: #dcfce7; color: #166534; }
        .method-post { background: #dbeafe; color: #1e40af; }
        .api-url { font-family: monospace; font-size: .85rem; font-weight: 600; color: #334155; }
        .api-desc { font-size: .8rem; color: #64748b; margin-top: 6px; }
        .param-tbl { font-size: .8rem; margin-top: 10px; width: 100%; }
        .param-tbl th { background: #f8fafc; font-weight: 600; padding: 6px 8px; text-align: left; }
        .param-tbl td { padding: 6px 8px; border-top: 1px solid #f1f5f9; }
        .param-tbl .req { color: #dc2626; font-size: .7rem; font-weight: 600; }
        .param-tbl .opt { color: #64748b; font-size: .7rem; }
        .resp-box { background: #1e293b; color: #e2e8f0; padding: 12px; border-radius: 8px; font-size: .78rem; font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 320px; margin-top: 10px; }
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .info-box code { background: #1e293b; color: #fbbf24; padding: 2px 6px; border-radius: 4px; font-size: .82rem; }
        .auth-badge { font-size: .65rem; padding: 3px 7px; border-radius: 3px; font-weight: 600; }
        .auth-open { background: #dcfce7; color: #166534; }
        .auth-token { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 1.1rem; font-weight: 700; margin: 24px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #4361ee; color: #1e293b; }
        .public-notice { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: .82rem; color: #78350f; }
        .public-notice strong { color: #92400e; }
        code { color: #4361ee; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container d-flex align-items-center">
            @if(!empty($companyLogo))
                <img src="{{ $logoPath . $companyLogo }}" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
            @endif
            <div>
                <h1><i class="ti ti-api me-2"></i>{{ __('Mobile App API Documentation') }}</h1>
                <p>{{ $companyName }} — {{ __('Public reference for developers building mobile integrations') }}</p>
            </div>
        </div>
    </div>

    <div class="docs-container">
        <div class="mb-3">
            <a href="{{ route('api-docs.postman-download') }}" download class="btn btn-primary">
                <i class="ti ti-download me-1"></i>{{ __('Download Postman Collection') }}
            </a>
        </div>
        <div class="public-notice">
            <i class="ti ti-info-circle me-1"></i>
            <strong>{{ __('Public Documentation:') }}</strong>
            {{ __('This page lists all available endpoints and their parameters. API keys and authentication tokens are not displayed here for security reasons. Contact your administrator to obtain credentials.') }}
        </div>

        <div class="row">
            <div class="col-lg-4 col-12">
                {{-- Configuration --}}
                <div class="api-card">
                    <div class="card-header bg-primary text-white"><i class="ti ti-settings me-2"></i>{{ __('API Configuration') }}</div>
                    <div class="p-3">
                        <div class="info-box mb-3">
                            <p class="mb-2 fw-bold">{{ __('Base URL') }}</p>
                            <code>{{ url('/api/mobile') }}</code>
                        </div>

                        <div class="info-box mb-3">
                            <p class="mb-2 fw-bold">{{ __('Authentication') }}</p>
                            <p class="mb-1" style="font-size:.82rem;">{{ __('Type:') }} <strong>Bearer Token (Sanctum)</strong></p>
                            <p class="mb-1" style="font-size:.82rem;">{{ __('Header:') }} <code>Authorization: Bearer {token}</code></p>
                            <p class="mb-1" style="font-size:.82rem;">{{ __('Header:') }} <code>X-App-Key: {your_app_key}</code></p>
                            <p class="mb-0" style="font-size:.82rem;">{{ __('Get token from') }} <code>POST /login</code></p>
                        </div>

                        <div class="info-box mb-3">
                            <p class="mb-2 fw-bold">{{ __('Content Type') }}</p>
                            <code>Content-Type: application/json</code><br>
                            <code>Accept: application/json</code>
                            <p class="mb-0 mt-2" style="font-size:.78rem; color:#7c2d12;">
                                {{ __('Important: All /api/mobile endpoints require X-App-Key header. Protected routes also require bearer token.') }}
                            </p>
                        </div>

                        <div class="info-box">
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
                <div class="api-card">
                    <div class="card-header bg-dark text-white"><i class="ti ti-list me-2"></i>{{ __('Endpoints Quick Reference') }}</div>
                    <div class="p-0">
                        <table class="table table-sm mb-0" style="font-size:.78rem;">
                            <thead><tr><th>{{ __('Method') }}</th><th>{{ __('Endpoint') }}</th><th>{{ __('Auth') }}</th></tr></thead>
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-12">
                {{-- 1. AUTH --}}
                <h5 class="section-title"><i class="ti ti-lock me-2"></i>{{ __('1. Authentication') }}</h5>

                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/login</span>
                            <span class="auth-badge auth-open">App Key</span>
                        </div>
                        <div class="api-desc">{{ __('Login and get bearer token. Use this token in all subsequent requests.') }}</div>
                        <table class="param-tbl">
                            <thead><tr><th>{{ __('Parameter') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('Description') }}</th></tr></thead>
                            <tbody>
                                <tr><td><code>email</code></td><td>string</td><td><span class="req">Required</span></td><td>Employee email</td></tr>
                                <tr><td><code>password</code></td><td>string</td><td><span class="req">Required</span></td><td>Account password</td></tr>
                                <tr><td><code>device_name</code></td><td>string</td><td><span class="req">Required</span></td><td>Device identifier (e.g. "iPhone 15")</td></tr>
                            </tbody>
                        </table>
                        <div class="resp-box">{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "1|abc123xyz...",
    "user": { "id": 7, "name": "Aarti", "email": "aarti@company.com", "type": "employee" },
    "employee": { "id": 5, "employee_id": "EMP005", "department": "IT", "designation": "Developer", "shift": { "name": "Morning", "start": "09:00", "end": "18:00" } }
  }
}</div>
                    </div>
                </div>

                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/logout</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Revoke current token and logout.') }}</div>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/forgot-password</span>
                            <span class="auth-badge auth-open">App Key</span>
                        </div>
                        <table class="param-tbl">
                            <tbody><tr><td><code>email</code></td><td>string</td><td><span class="req">Required</span></td><td>Registered email address</td></tr></tbody>
                        </table>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/change-password</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <table class="param-tbl">
                            <tbody>
                                <tr><td><code>current_password</code></td><td>string</td><td><span class="req">Required</span></td><td>Current password</td></tr>
                                <tr><td><code>new_password</code></td><td>string</td><td><span class="req">Required</span></td><td>New password (min 6 chars)</td></tr>
                                <tr><td><code>new_password_confirmation</code></td><td>string</td><td><span class="req">Required</span></td><td>Confirm new password</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. FACE VERIFICATION --}}
                <h5 class="section-title"><i class="ti ti-scan me-2"></i>{{ __('2. Face Verification') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/verify-face</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Verify employee face after login. Send selfie as base64.') }}</div>
                        <table class="param-tbl">
                            <tbody><tr><td><code>photo</code></td><td>string</td><td><span class="req">Required</span></td><td>Base64 encoded image (JPEG/PNG)</td></tr></tbody>
                        </table>
                        <div class="resp-box">{ "success": true, "data": { "match": true, "confidence": 92.5 } }</div>
                    </div>
                </div>

                {{-- 3. DASHBOARD --}}
                <h5 class="section-title"><i class="ti ti-dashboard me-2"></i>{{ __('3. Dashboard') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/dashboard</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Get today\'s attendance status, monthly summary, and company timings.') }}</div>
                        <div class="resp-box">{
  "data": {
    "today": { "status": "Present", "clock_in": "09:02:00", "clock_out": "00:00:00", "is_clocked_in": true, "is_clocked_out": false },
    "month_summary": { "working_days": 22, "present": 17, "half_day": 3, "absent": 1, "leave": 1 },
    "company": { "start_time": "09:00", "end_time": "18:00" }
  }
}</div>
                    </div>
                </div>

                {{-- 4. CLOCK IN/OUT --}}
                <h5 class="section-title"><i class="ti ti-clock me-2"></i>{{ __('4. Clock In / Clock Out') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/clock-in</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Clock in with live location and optional selfie photo.') }}</div>
                        <table class="param-tbl">
                            <tbody>
                                <tr><td><code>latitude</code></td><td>number</td><td><span class="opt">Optional</span></td><td>GPS latitude</td></tr>
                                <tr><td><code>longitude</code></td><td>number</td><td><span class="opt">Optional</span></td><td>GPS longitude</td></tr>
                                <tr><td><code>address</code></td><td>string</td><td><span class="opt">Optional</span></td><td>Readable address</td></tr>
                                <tr><td><code>photo</code></td><td>string</td><td><span class="opt">Optional</span></td><td>Base64 selfie (face verified)</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/clock-out</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Clock out. Same parameters as clock-in.') }}</div>
                    </div>
                </div>

                {{-- 5. ATTENDANCE HISTORY --}}
                <h5 class="section-title"><i class="ti ti-calendar me-2"></i>{{ __('5. Attendance History') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/attendance-history?month=2026-03</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <table class="param-tbl">
                            <tbody><tr><td><code>month</code></td><td>string</td><td><span class="opt">Optional</span></td><td>YYYY-MM format (defaults to current month)</td></tr></tbody>
                        </table>
                    </div>
                </div>

                {{-- 6. LEAVE --}}
                <h5 class="section-title"><i class="ti ti-calendar-off me-2"></i>{{ __('6. Leave Management') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/leave-types</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Get available leave types (Sick, Casual, etc.)') }}</div>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/leaves?month=2026-03</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Get employee\'s leave history.') }}</div>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/leave/apply</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <table class="param-tbl">
                            <tbody>
                                <tr><td><code>leave_type_id</code></td><td>integer</td><td><span class="req">Required</span></td><td>ID from /leave-types</td></tr>
                                <tr><td><code>start_date</code></td><td>date</td><td><span class="req">Required</span></td><td>YYYY-MM-DD</td></tr>
                                <tr><td><code>end_date</code></td><td>date</td><td><span class="req">Required</span></td><td>YYYY-MM-DD (>= start_date)</td></tr>
                                <tr><td><code>day_type</code></td><td>string</td><td><span class="req">Required</span></td><td>full_day | first_half | second_half</td></tr>
                                <tr><td><code>leave_reason</code></td><td>string</td><td><span class="req">Required</span></td><td>Reason for leave</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 7. SWIPE REQUEST --}}
                <h5 class="section-title"><i class="ti ti-edit me-2"></i>{{ __('7. Swipe Request') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-post">POST</span>
                            <span class="api-url">/swipe-request</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Request attendance modification (wrong clock time correction).') }}</div>
                        <table class="param-tbl">
                            <tbody>
                                <tr><td><code>date</code></td><td>date</td><td><span class="req">Required</span></td><td>YYYY-MM-DD</td></tr>
                                <tr><td><code>requested_status</code></td><td>string</td><td><span class="req">Required</span></td><td>Present | Half Day | Leave | Absent</td></tr>
                                <tr><td><code>requested_clock_in</code></td><td>time</td><td><span class="opt">Optional</span></td><td>HH:MM format</td></tr>
                                <tr><td><code>requested_clock_out</code></td><td>time</td><td><span class="opt">Optional</span></td><td>HH:MM format</td></tr>
                                <tr><td><code>reason</code></td><td>string</td><td><span class="req">Required</span></td><td>Reason for modification</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/swipe-requests</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Get history of swipe modification requests.') }}</div>
                    </div>
                </div>

                {{-- 8. PROFILE --}}
                <h5 class="section-title"><i class="ti ti-user me-2"></i>{{ __('8. Profile') }}</h5>
                <div class="api-card">
                    <div class="api-endpoint">
                        <div class="d-flex align-items-center gap-2">
                            <span class="method-badge method-get">GET</span>
                            <span class="api-url">/profile</span>
                            <span class="auth-badge auth-token">Token</span>
                        </div>
                        <div class="api-desc">{{ __('Get employee profile, department, designation, shift, and reporting manager.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center my-4" style="color:#94a3b8;font-size:.8rem;">
            &copy; {{ date('Y') }} {{ $companyName }} — {{ __('API Documentation') }}
        </div>
    </div>
</body>
</html>
