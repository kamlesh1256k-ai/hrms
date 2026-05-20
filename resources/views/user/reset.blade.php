@php
    // Detect: is this loaded standalone (full page) or inside a modal?
    $isStandalone = !request()->ajax() && !request()->wantsJson() && !request()->header('X-Requested-With');
@endphp

@if ($isStandalone)
    @extends('layouts.admin')

    @section('page-title', __('Reset Password'))

    @section('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('user.index') }}">{{ __('Users') }}</a></li>
        <li class="breadcrumb-item">{{ __('Reset Password') }}</li>
    @endsection

    @push('css-page')
        <style>
            .rp-wrap { max-width: 520px; margin: 24px auto; }
            .rp-card { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 6px 24px rgba(15, 23, 42, .06); overflow: hidden; }
            .rp-hero {
                background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
                padding: 28px 28px 24px;
                color: #fff;
                position: relative;
            }
            .rp-hero::after {
                content: '';
                position: absolute;
                top: -40px; right: -40px;
                width: 160px; height: 160px;
                background: rgba(255,255,255,.08);
                border-radius: 50%;
            }
            .rp-hero-icon {
                width: 56px; height: 56px; border-radius: 14px;
                background: rgba(255,255,255,.2);
                display: flex; align-items: center; justify-content: center;
                font-size: 28px; margin-bottom: 14px;
                position: relative; z-index: 1;
            }
            .rp-hero h4 { margin: 0; font-weight: 700; font-size: 1.25rem; position: relative; z-index: 1; }
            .rp-hero p { margin: 6px 0 0; opacity: .9; font-size: .8125rem; position: relative; z-index: 1; }
            .rp-user-chip {
                display: inline-flex; align-items: center; gap: 8px;
                background: rgba(255,255,255,.2);
                padding: 6px 12px; border-radius: 100px;
                font-size: .8125rem; font-weight: 600;
                margin-top: 12px;
                position: relative; z-index: 1;
            }
            .rp-body { padding: 28px; background: #fff; }
            .rp-field { margin-bottom: 20px; }
            .rp-label { font-size: .8125rem; font-weight: 600; color: #1f2937; margin-bottom: 6px; display: block; }
            .rp-input-wrap { position: relative; }
            .rp-input {
                width: 100%;
                padding: 12px 44px 12px 14px;
                border: 1.5px solid #e2e8f0;
                border-radius: 10px;
                font-size: .9375rem;
                transition: all .15s;
                background: #f9fafb;
            }
            .rp-input:focus {
                outline: none;
                border-color: #0d9488;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(13, 148, 136, .12);
            }
            .rp-toggle {
                position: absolute;
                right: 12px; top: 50%;
                transform: translateY(-50%);
                background: none; border: none;
                color: #64748b; cursor: pointer;
                padding: 4px;
                font-size: 1.1rem;
            }
            .rp-toggle:hover { color: #0d9488; }
            .rp-strength {
                margin-top: 10px;
                display: flex; gap: 4px;
            }
            .rp-strength-bar {
                flex: 1; height: 4px;
                background: #e5e7eb;
                border-radius: 100px;
                transition: background .2s;
            }
            .rp-strength-bar.active-weak    { background: #ef4444; }
            .rp-strength-bar.active-medium  { background: #f59e0b; }
            .rp-strength-bar.active-strong  { background: #10b981; }
            .rp-strength-label { font-size: .75rem; font-weight: 600; margin-top: 6px; color: #64748b; }
            .rp-match {
                font-size: .75rem; margin-top: 6px; font-weight: 500;
                display: flex; align-items: center; gap: 4px;
            }
            .rp-match.match    { color: #10b981; }
            .rp-match.mismatch { color: #ef4444; }
            .rp-rules {
                background: #f8fafc; border: 1px solid #e2e8f0;
                border-radius: 10px; padding: 12px 14px;
                margin: 8px 0 20px; font-size: .75rem;
            }
            .rp-rules-title { font-weight: 700; color: #334155; margin-bottom: 6px; font-size: .75rem; }
            .rp-rule { display: flex; align-items: center; gap: 6px; color: #64748b; padding: 2px 0; }
            .rp-rule.met { color: #10b981; }
            .rp-rule i { width: 14px; }
            .rp-actions { display: flex; gap: 10px; margin-top: 24px; }
            .rp-btn {
                flex: 1; padding: 11px 16px;
                border-radius: 10px; font-weight: 600;
                font-size: .9375rem;
                border: none; cursor: pointer;
                transition: all .15s;
                display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            }
            .rp-btn-primary { background: #0d9488; color: #fff; }
            .rp-btn-primary:hover { background: #0f766e; }
            .rp-btn-primary:disabled { background: #cbd5e1; cursor: not-allowed; }
            .rp-btn-secondary {
                background: #f1f5f9; color: #475569;
            }
            .rp-btn-secondary:hover { background: #e2e8f0; }
        </style>
    @endpush

    @section('content')
        <div class="rp-wrap">
            <div class="rp-card">
                @include('user.partials.reset_form_body', ['user' => $user, 'standalone' => true])
            </div>
        </div>
    @endsection
@else
    {{-- Modal context --}}
    @include('user.partials.reset_form_body', ['user' => $user, 'standalone' => false])
@endif
