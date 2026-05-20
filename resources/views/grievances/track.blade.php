<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Track Anonymous Grievance') }} — {{ config('app.name', 'HRMS') }}</title>

    {{-- Tabler icons (used everywhere else in the project) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        :root {
            --grad-1: #6366f1;
            --grad-2: #8b5cf6;
            --grad-3: #ec4899;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, sans-serif;
            color: #1f2937;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 16px;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(99,102,241,.25) 0, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(236,72,153,.2) 0, transparent 40%);
            pointer-events: none;
        }
        .track-wrap { width: 100%; max-width: 720px; position: relative; z-index: 1; }

        .track-hero {
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 20px;
            padding: 28px 32px;
            color: #fff;
            margin-bottom: 18px;
            text-align: center;
        }
        .track-hero .hero-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--grad-1), var(--grad-3));
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin-bottom: 14px;
            box-shadow: 0 16px 40px -12px rgba(139,92,246,.6);
        }
        .track-hero h2 { margin: 0 0 6px; font-weight: 700; letter-spacing: -.5px; }
        .track-hero p  { margin: 0; opacity: .8; font-size: .92rem; }

        .track-card {
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 30px 60px -20px rgba(0,0,0,.35);
        }

        .form-label {
            font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: 6px;
        }
        .token-input {
            width: 100%;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 1rem;
            font-family: 'SF Mono', Consolas, Menlo, monospace;
            letter-spacing: .8px;
            transition: .15s;
            background: #f8fafc;
        }
        .token-input:focus {
            outline: 0;
            border-color: var(--grad-1);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99,102,241,.15);
        }
        .help-line { font-size: .76rem; color: #94a3b8; margin-top: 6px; }

        .btn-primary-grad {
            width: 100%;
            background: linear-gradient(135deg, var(--grad-1), var(--grad-2));
            color: #fff; border: 0;
            padding: 13px 22px; border-radius: 12px;
            font-weight: 600; font-size: .95rem;
            margin-top: 16px;
            cursor: pointer;
            transition: .18s;
            box-shadow: 0 12px 28px -10px rgba(99,102,241,.55);
        }
        .btn-primary-grad:hover { transform: translateY(-1px); filter: brightness(1.08); }
        .btn-light-secondary {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.1); color: #fff; padding: 8px 14px;
            border-radius: 10px; text-decoration: none; font-size: .84rem;
            border: 1px solid rgba(255,255,255,.2);
        }
        .btn-light-secondary:hover { background: rgba(255,255,255,.2); color: #fff; }

        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444;
            color: #991b1b; padding: 12px 16px; border-radius: 10px; font-size: .88rem;
            display: flex; align-items: center; gap: 10px; margin-bottom: 16px;
        }

        /* Result card */
        .result-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 8px 0 14px; }
        .pill {
            display: inline-block; padding: 4px 12px; border-radius: 20px;
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        }
        .pill-open  { background: #fee2e2; color: #991b1b; }
        .pill-prog  { background: #fef3c7; color: #b45309; }
        .pill-done  { background: #dcfce7; color: #166534; }
        .pill-cat   { background: #eef2ff; color: #4338ca; }
        .pill-token { background: #f1f5f9; color: #475569; font-family: monospace; }

        .desc-box {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 14px 16px; font-size: .9rem;
            color: #334155; line-height: 1.5; white-space: pre-wrap;
        }

        /* Chat thread */
        .thread {
            margin-top: 18px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
        }
        .thread h6 {
            font-weight: 700; font-size: .8rem; text-transform: uppercase;
            letter-spacing: .5px; color: #475569; margin: 0 0 12px;
        }
        .msg {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 12px 14px; margin-bottom: 10px;
        }
        .msg.from-hr {
            background: linear-gradient(135deg, #eef2ff, #fff);
            border-color: #c7d2fe;
        }
        .msg .msg-head {
            display: flex; justify-content: space-between; gap: 10px;
            font-size: .76rem; color: #64748b; margin-bottom: 6px;
        }
        .msg .msg-head strong { color: #1e293b; }
        .msg .msg-body { font-size: .9rem; color: #1e293b; line-height: 1.5; white-space: pre-wrap; }
        .msg-empty {
            text-align: center; padding: 18px; color: #94a3b8; font-size: .85rem;
            font-style: italic;
        }

        .resolved-banner {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac; border-radius: 12px;
            padding: 12px 16px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 10px;
            color: #166534; font-size: .9rem;
        }
        .resolved-banner i { font-size: 1.4rem; color: #15803d; }

        .footer-link {
            text-align: center; margin-top: 18px;
            color: rgba(255,255,255,.65); font-size: .82rem;
        }
        .footer-link a { color: rgba(255,255,255,.95); text-decoration: underline; }
    </style>
</head>
<body>
<div class="track-wrap">

    <div class="track-hero">
        <div class="hero-icon"><i class="ti ti-shield-lock"></i></div>
        <h2>{{ __('Track Anonymous Grievance') }}</h2>
        <p>{{ __('Enter your tracking token to view the current status and HR responses.') }}</p>
    </div>

    @if(session('error'))
        <div class="track-card" style="margin-bottom:14px;">
            <div class="alert-error">
                <i class="ti ti-alert-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Lookup form --}}
    @if(empty($grievance))
        <div class="track-card">
            <form method="POST" action="{{ route('grievances.track.lookup') }}" autocomplete="off">
                @csrf
                <label for="token" class="form-label">
                    <i class="ti ti-key" style="color: var(--grad-1);"></i> {{ __('Tracking Token') }}
                </label>
                <input type="text" name="token" id="token" class="token-input"
                       placeholder="GRV_XXXXXXXXXXXX"
                       value="{{ old('token') }}" required maxlength="32" autofocus>
                <div class="help-line">
                    <i class="ti ti-info-circle"></i>
                    {{ __('You received this token when you submitted your anonymous grievance. Format: GRV_ followed by 12 characters.') }}
                </div>

                <button type="submit" class="btn-primary-grad">
                    <i class="ti ti-search"></i> {{ __('Look Up Grievance') }}
                </button>
            </form>
        </div>
    @else
        {{-- Result --}}
        @php
            $st = $grievance->status;
            $pillClass = $st === 'resolved' ? 'pill-done' : ($st === 'in_progress' ? 'pill-prog' : 'pill-open');
            $pillLabel = ucfirst(str_replace('_', ' ', $st));
        @endphp
        <div class="track-card">
            @if($grievance->isResolved())
                <div class="resolved-banner">
                    <i class="ti ti-circle-check"></i>
                    <div>
                        <strong>{{ __('Resolved') }}</strong> —
                        {{ __('on :date', ['date' => optional($grievance->resolved_at)->format('d M Y')]) }}
                    </div>
                </div>
            @endif

            <h4 style="margin: 0 0 4px; font-weight: 700; color: #0f172a;">{{ $grievance->title }}</h4>

            <div class="result-meta">
                <span class="pill pill-cat">{{ $grievance->category }}</span>
                <span class="pill {{ $pillClass }}">{{ $pillLabel }}</span>
                <span class="pill pill-token" title="{{ __('Your tracking token') }}">{{ $token }}</span>
                <small style="color:#94a3b8;margin-left:auto;">
                    <i class="ti ti-calendar"></i>
                    {{ __('Submitted') }} {{ $grievance->created_at->diffForHumans() }}
                </small>
            </div>

            <div class="desc-box">{{ $grievance->description }}</div>

            <div class="thread">
                <h6><i class="ti ti-messages"></i> {{ __('HR Responses') }} ({{ $grievance->publicResponses->count() }})</h6>

                @forelse($grievance->publicResponses as $r)
                    @php
                        $isHr = $r->responder && in_array($r->responder->type ?? 'employee', ['hr','company','super admin']);
                    @endphp
                    <div class="msg {{ $isHr ? 'from-hr' : '' }}">
                        <div class="msg-head">
                            <strong>
                                @if($isHr)
                                    <i class="ti ti-shield-check"></i> {{ __('HR Team') }}
                                @else
                                    <i class="ti ti-user"></i> {{ __('You') }}
                                @endif
                            </strong>
                            <span>{{ $r->created_at->format('d M Y · h:i A') }}</span>
                        </div>
                        <div class="msg-body">{{ $r->message }}</div>
                    </div>
                @empty
                    <div class="msg-empty">
                        <i class="ti ti-mail-off" style="font-size:1.6rem;display:block;opacity:.5;margin-bottom:4px;"></i>
                        {{ __('No responses yet. HR will respond shortly.') }}
                    </div>
                @endforelse
            </div>

            <div style="display:flex;gap:8px;margin-top:18px;">
                <a href="{{ route('grievances.track') }}" class="btn-primary-grad" style="text-align:center;text-decoration:none;flex:1;">
                    <i class="ti ti-search"></i> {{ __('Track Another') }}
                </a>
                <button type="button" class="btn-primary-grad" style="flex:0 0 auto;background:#fff;color:#475569;border:1px solid #e2e8f0;box-shadow:none;" onclick="window.print();">
                    <i class="ti ti-printer"></i>
                </button>
            </div>
        </div>
    @endif

    <div class="footer-link">
        <a href="{{ route('login') }}">
            <i class="ti ti-arrow-left"></i> {{ __('Back to Login') }}
        </a>
    </div>
</div>
</body>
</html>
