@php
    use App\Models\Utility;
    use App\Models\Plan;

    $sup_logo        = Utility::get_file('uploads/logo');
    $company_logo    = Utility::GetLogo();
    $logo_main_url   = rtrim($sup_logo, '/') . '/' . (!empty($company_logo) ? $company_logo : 'logo-dark.png');
    $adminSettings   = Utility::settings();
    $getseo          = Utility::getSeoSetting();
    $metatitle       = $getseo['meta_title'] ?? config('app.name');
    $metadesc        = $getseo['meta_description'] ?? 'Complete HR Management System';
    $setting         = Utility::colorset();
    $SITE_RTL        = Utility::getValByName('SITE_RTL');
    $color           = !empty($setting['theme_color']) ? $setting['theme_color'] : 'theme-3';
    $appName         = Utility::getValByName('title_text') ?: config('app.name', 'HRMS');
    $plans           = Plan::orderBy('price', 'ASC')->where('is_disable', '!=', 0)->get();
    $admin_payment   = Utility::getAdminPaymentSetting();
    $currSymbol      = !empty($admin_payment['currency_symbol']) ? $admin_payment['currency_symbol'] : ($adminSettings['site_currency_symbol'] ?? '$');
    $lang            = Utility::getValByName('default_language') ?? 'en';
    if ($lang == 'ar' || $lang == 'he') { $SITE_RTL = 'on'; }
    $isDark          = isset($setting['cust_darklayout']) && $setting['cust_darklayout'] == 'on';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $SITE_RTL == 'on' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $metatitle ?: $appName }} — Modern HR Management Platform</title>
    <meta name="description" content="{{ $metadesc }}">
    <meta property="og:title" content="{{ $metatitle }}">
    <meta property="og:description" content="{{ $metadesc }}">
    <link rel="icon" href="{{ $sup_logo }}/favicon.png" type="image/x-icon" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/landingpage/css/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/landingpage/css/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/landingpage/css/hrms-landing.css') }}">
</head>

<body class="hrms-lp{{ $isDark ? ' lp-dark' : '' }}">

{{-- ═══════════════════════════════════════════════════════════════
     NAVBAR — Glassmorphism sticky header
     ═══════════════════════════════════════════════════════════════ --}}
<header class="lp-navbar" id="page-top">
    <div class="lp-container">
        <nav class="lp-nav" id="lp-nav">
            <a href="{{ url('/') }}" class="lp-brand" style="display:flex;align-items:center;gap:.85rem;text-decoration:none;">
                <img src="{{ $logo_main_url . '?' . time() }}" alt="{{ $appName }}" style="height:90px;width:auto;object-fit:contain;"
                     onerror="this.style.display='none';">
                <div style="display:flex;flex-direction:column;line-height:1.1;gap:6px;">
                    <span style="font-size:1.65rem;font-weight:800;color:#1e3a8a;letter-spacing:-.02em;border-bottom:1.5px solid #1e3a8a;padding-bottom:1px;align-self:flex-start;">Jemini</span>
                    <span style="font-size:.7rem;font-weight:500;color:#64748b;letter-spacing:.06em;white-space:nowrap;text-transform:uppercase;">By People, For People</span>
                </div>
            </a>

            <div class="lp-nav-menu" id="lp-nav-menu">
                <a href="#features" class="lp-nav-link">{{ __('Why Choose Us') }}</a>
                <a href="#modules" class="lp-nav-link">{{ __('Modules') }}</a>
                <a href="#how-it-works" class="lp-nav-link">{{ __('How It Works') }}</a>
                @if($plans->isNotEmpty())
                <a href="#pricing" class="lp-nav-link">{{ __('Pricing') }}</a>
                @endif
                <a href="#faq" class="lp-nav-link">{{ __('FAQ') }}</a>
            </div>

            <div class="lp-nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="lp-btn lp-btn-ghost lp-btn-sm">{{ __('Dashboard') }}</a>
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">@csrf
                        <button type="submit" class="lp-btn lp-btn-primary lp-btn-sm">{{ __('Logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost lp-btn-sm">{{ __('Sign In') }}</a>
                    <a href="#" onclick="openDemoModal();return false;" class="lp-btn lp-btn-primary lp-btn-sm"><i class="ti ti-rocket"></i> {{ __('Free Demo') }}</a>
                @endauth
            </div>

            <button class="lp-hamburger" id="lp-hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </div>
</header>

{{-- ═══════════════════════════════════════════════════════════════
     HERO — Split layout with SVG dashboard mockup
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-hero">
    <div class="lp-hero-bg-shapes">
        <div class="lp-hero-shape lp-hero-shape-1"></div>
        <div class="lp-hero-shape lp-hero-shape-2"></div>
        <div class="lp-hero-shape lp-hero-shape-3"></div>
    </div>
    <div class="lp-container">
        <div class="lp-hero-grid">
            <div class="lp-hero-content">
                <h1 class="lp-hero-title">
                    {{ __('Your Trusted') }}
                    <span class="lp-hero-title-accent">{{ __('People Partner') }}</span>
                </h1>
                <p class="lp-hero-desc">
                    {{ __('Streamline HR, payroll, attendance, and compliance in one unified platform. Save 10+ hours every week and let your team focus on people, not paperwork.') }}
                </p>
                <div class="lp-hero-ctas">
                    <a href="#" onclick="openDemoModal();return false;" class="lp-btn lp-btn-white lp-btn-lg">
                        <i class="ti ti-rocket" style="font-size:.85em;"></i>
                        {{ __('Free Demo') }}
                    </a>
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-outline-white lp-btn-lg">
                        <i class="ti ti-login" style="font-size:.85em;"></i>
                        {{ __('Sign In') }}
                    </a>
                </div>
            </div>

            {{-- Dashboard Mockup --}}
            <div class="lp-hero-visual">
                <div class="lp-dashboard-mockup">
                    <div class="lp-mockup-topbar">
                        <div class="lp-mockup-dots">
                            <span style="background:#ff5f57;"></span>
                            <span style="background:#febc2e;"></span>
                            <span style="background:#28c840;"></span>
                        </div>
                        <div class="lp-mockup-url">
                            <i class="ti ti-lock" style="font-size:.65rem;color:#22c55e;"></i>
                            <span>{{ request()->getHost() }}/dashboard</span>
                        </div>
                    </div>
                    <div class="lp-mockup-body">
                        <div class="lp-mockup-sidebar">
                            <div class="lp-mockup-sidebar-item active"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></div>
                            <div class="lp-mockup-sidebar-item"><i class="ti ti-users"></i><span>Employees</span></div>
                            <div class="lp-mockup-sidebar-item"><i class="ti ti-calendar"></i><span>Attendance</span></div>
                            <div class="lp-mockup-sidebar-item"><i class="ti ti-report-money"></i><span>Payroll</span></div>
                            <div class="lp-mockup-sidebar-item"><i class="ti ti-beach"></i><span>Leave</span></div>
                            <div class="lp-mockup-sidebar-item"><i class="ti ti-briefcase"></i><span>Recruitment</span></div>
                        </div>
                        <div class="lp-mockup-main">
                            <div class="lp-mockup-kpi-row">
                                <div class="lp-mockup-kpi" style="--kpi-color:#3b82f6;">
                                    <div class="lp-mockup-kpi-icon"><i class="ti ti-users"></i></div>
                                    <div class="lp-mockup-kpi-info"><span class="lp-mockup-kpi-val">248</span><span class="lp-mockup-kpi-label">Total Employees</span></div>
                                </div>
                                <div class="lp-mockup-kpi" style="--kpi-color:#10b981;">
                                    <div class="lp-mockup-kpi-icon"><i class="ti ti-user-check"></i></div>
                                    <div class="lp-mockup-kpi-info"><span class="lp-mockup-kpi-val">231</span><span class="lp-mockup-kpi-label">Present Today</span></div>
                                </div>
                                <div class="lp-mockup-kpi" style="--kpi-color:#f59e0b;">
                                    <div class="lp-mockup-kpi-icon"><i class="ti ti-beach"></i></div>
                                    <div class="lp-mockup-kpi-info"><span class="lp-mockup-kpi-val">12</span><span class="lp-mockup-kpi-label">On Leave</span></div>
                                </div>
                            </div>
                            <div class="lp-mockup-chart">
                                <div class="lp-mockup-chart-title">Attendance Overview</div>
                                <div class="lp-mockup-bars">
                                    <div class="lp-mockup-bar" style="--bar-h:70%;"><span>Mon</span></div>
                                    <div class="lp-mockup-bar" style="--bar-h:85%;"><span>Tue</span></div>
                                    <div class="lp-mockup-bar" style="--bar-h:92%;"><span>Wed</span></div>
                                    <div class="lp-mockup-bar" style="--bar-h:78%;"><span>Thu</span></div>
                                    <div class="lp-mockup-bar" style="--bar-h:88%;"><span>Fri</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Floating badges --}}
                <div class="lp-float-badge lp-float-badge-1">
                    <i class="ti ti-check" style="color:#10b981;"></i>
                    <span>Payroll Processed</span>
                </div>
                <div class="lp-float-badge lp-float-badge-2">
                    <i class="ti ti-trending-up" style="color:#3b82f6;"></i>
                    <span>93% Attendance</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Logos strip & stats hidden — will add real data soon --}}

{{-- ═══════════════════════════════════════════════════════════════
     FEATURES — Showcase 3 main features with visuals
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-section lp-section-white" id="features">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('Why Choose Us') }}</span>
            <h2 class="lp-heading" style="display:inline-flex;align-items:center;gap:.6rem;justify-content:center;flex-wrap:wrap;">
                {{ __('Everything Your HR Team Needs') }}
                <button type="button" onclick="openSpecialBenefits()" aria-label="View special benefits"
                    style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:50%;border:none;cursor:pointer;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-size:1.5rem;line-height:1;box-shadow:0 8px 22px -6px rgba(37,99,235,.55);transition:transform .25s ease,box-shadow .25s ease;animation:sbPulse 2.4s ease-in-out infinite;"
                    onmouseover="this.style.transform='scale(1.12) rotate(90deg)';"
                    onmouseout="this.style.transform='scale(1) rotate(0)';">
                    <i class="ti ti-plus"></i>
                </button>
            </h2>
            <p class="lp-subheading">{{ __('A unified platform that replaces scattered tools and spreadsheets with one powerful, intuitive system.') }}</p>
        </div>

        {{-- ═══ SPECIAL BENEFITS MODAL ═══ --}}
        <div id="sb-overlay" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(15,23,42,.7);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);overflow-y:auto;padding:2rem 1rem;" onclick="if(event.target===this)closeSpecialBenefits()">
            <div style="background:#fff;border-radius:24px;box-shadow:0 30px 70px rgba(0,0,0,.3);max-width:880px;width:100%;margin:auto;padding:2.5rem;position:relative;animation:sbSlideIn .3s cubic-bezier(.34,1.4,.64,1) both;">
                <button onclick="closeSpecialBenefits()" style="position:absolute;top:1rem;right:1rem;background:#f1f5f9;border:none;cursor:pointer;color:#475569;width:36px;height:36px;border-radius:50%;font-size:1.2rem;display:flex;align-items:center;justify-content:center;" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
                <div style="text-align:center;margin-bottom:2rem;">
                    <span style="display:inline-block;background:linear-gradient(135deg,#eff6ff,#f5f3ff);color:#7c3aed;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;border-radius:99px;padding:.3rem 1rem;margin-bottom:.75rem;">
                        <i class="ti ti-sparkles"></i> {{ __('Exclusive Features') }}
                    </span>
                    <h3 style="font-size:1.85rem;font-weight:800;color:#0f172a;margin:0 0 .5rem;line-height:1.2;">{{ __('Built-In Premium Add-Ons') }}</h3>
                    <p style="color:#64748b;font-size:.95rem;margin:0;max-width:560px;margin:0 auto;">{{ __('Powerful tools we have engineered into the platform that competitors charge extra for.') }}</p>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
                    {{-- Activity Tracker --}}
                    <div style="background:linear-gradient(135deg,#eff6ff,#fff);border:1px solid #dbeafe;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(37,99,235,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-device-desktop-analytics"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('Activity Tracker') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('Auto screenshots, app & website tracking, idle-time detection — full transparency for remote teams.') }}</p>
                    </div>

                    {{-- Chat System --}}
                    <div style="background:linear-gradient(135deg,#f5f3ff,#fff);border:1px solid #ede9fe;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(124,58,237,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-message-circle-2"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('Built-In Chat System') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('Real-time team messaging, group channels & file sharing — no need for Slack or third-party tools.') }}</p>
                    </div>

                    {{-- Screen Capture --}}
                    <div style="background:linear-gradient(135deg,#ecfdf5,#fff);border:1px solid #d1fae5;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(16,185,129,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#10b981,#047857);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-screenshot"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('Screen Capture') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('Periodic screenshot snapshots for productivity insights, viewable only by authorised admins.') }}</p>
                    </div>

                    {{-- AI / ChatGPT --}}
                    <div style="background:linear-gradient(135deg,#fef3c7,#fff);border:1px solid #fde68a;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(245,158,11,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#b45309);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-brain"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('AI Assistant') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('ChatGPT-powered HR helper for drafting policies, JDs, offer letters & answering employee queries.') }}</p>
                    </div>

                    {{-- Desktop App --}}
                    <div style="background:linear-gradient(135deg,#fef2f2,#fff);border:1px solid #fecaca;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(239,68,68,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-device-laptop"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('Desktop App') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('Native Windows/Mac app for one-click clock-in, activity tracking & instant notifications.') }}</p>
                    </div>

                    {{-- Facial Recognition --}}
                    <div style="background:linear-gradient(135deg,#ecfeff,#fff);border:1px solid #cffafe;border-radius:16px;padding:1.5rem;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px -15px rgba(6,182,212,.3)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#06b6d4,#0e7490);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem;"><i class="ti ti-scan-eye"></i></div>
                        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .4rem;">{{ __('Face Recognition') }}</h4>
                        <p style="font-size:.85rem;color:#64748b;line-height:1.55;margin:0;">{{ __('AI-powered facial attendance with anti-spoofing — no extra biometric hardware required.') }}</p>
                    </div>
                </div>

            </div>
        </div>
        <style>
            @keyframes sbPulse { 0%,100%{box-shadow:0 8px 22px -6px rgba(37,99,235,.55);} 50%{box-shadow:0 8px 28px -4px rgba(124,58,237,.75);} }
            @keyframes sbSlideIn { from{opacity:0;transform:translateY(-20px) scale(.97);} to{opacity:1;transform:translateY(0) scale(1);} }
        </style>
        <script>
            function openSpecialBenefits(){ document.getElementById('sb-overlay').style.display='block'; document.body.style.overflow='hidden'; }
            function closeSpecialBenefits(){ document.getElementById('sb-overlay').style.display='none'; document.body.style.overflow=''; }
            document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeSpecialBenefits(); });
        </script>

        {{-- Feature 1: Left text, Right visual --}}
        <div class="lp-feature-row">
            <div class="lp-feature-text">
                <span class="lp-feature-num">01</span>
                <h3>{{ __('Smart Employee Management') }}</h3>
                <p>{{ __('Centralize every employee detail — profiles, documents, org charts, designations, and departments. Find anyone in seconds with powerful search and filters.') }}</p>
                <ul class="lp-feature-list">
                    <li><i class="ti ti-check"></i> {{ __('Complete employee profiles & directories') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Document management with version control') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Org chart visualization') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Custom fields & departments') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Screen capture') }}</li>
                </ul>
            </div>
            <div class="lp-feature-visual">
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 20px 50px -20px rgba(15,23,42,.12);overflow:hidden;max-width:430px;margin-left:auto;">
                    {{-- Card header --}}
                    <div style="padding:1.1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:#0f172a;line-height:1.2;">{{ __('Team Directory') }}</div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">248 {{ __('members') }} &middot; 6 {{ __('departments') }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:.4rem;background:#f1f5f9;border-radius:8px;padding:.35rem .6rem;">
                            <i class="ti ti-search" style="color:#64748b;font-size:.85rem;"></i>
                            <span style="font-size:.7rem;color:#94a3b8;">{{ __('Search...') }}</span>
                        </div>
                    </div>

                    {{-- Member rows --}}
                    <div style="padding:.5rem;">
                        {{-- Row 1 --}}
                        <div style="display:flex;align-items:center;gap:.85rem;padding:.75rem .85rem;border-radius:12px;transition:background .2s;cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;">AK</div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;background:#10b981;border:2px solid #fff;border-radius:50%;"></span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.9rem;font-weight:600;color:#0f172a;line-height:1.25;">Aisha Khan</div>
                                <div style="font-size:.75rem;color:#64748b;display:flex;align-items:center;gap:.35rem;margin-top:1px;">
                                    <i class="ti ti-briefcase" style="font-size:.7rem;"></i>
                                    Sr. Developer
                                </div>
                            </div>
                            <span style="font-size:.65rem;font-weight:600;color:#3b82f6;background:#eff6ff;padding:.2rem .55rem;border-radius:6px;">Engineering</span>
                        </div>

                        {{-- Row 2 --}}
                        <div style="display:flex;align-items:center;gap:.85rem;padding:.75rem .85rem;border-radius:12px;transition:background .2s;cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#10b981,#0ea5e9);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;">JM</div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;background:#10b981;border:2px solid #fff;border-radius:50%;"></span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.9rem;font-weight:600;color:#0f172a;line-height:1.25;">James Mitchell</div>
                                <div style="font-size:.75rem;color:#64748b;display:flex;align-items:center;gap:.35rem;margin-top:1px;">
                                    <i class="ti ti-briefcase" style="font-size:.7rem;"></i>
                                    Product Manager
                                </div>
                            </div>
                            <span style="font-size:.65rem;font-weight:600;color:#0ea5e9;background:#ecfeff;padding:.2rem .55rem;border-radius:6px;">Product</span>
                        </div>

                        {{-- Row 3 --}}
                        <div style="display:flex;align-items:center;gap:.85rem;padding:.75rem .85rem;border-radius:12px;transition:background .2s;cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;">PS</div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;background:#f59e0b;border:2px solid #fff;border-radius:50%;"></span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.9rem;font-weight:600;color:#0f172a;line-height:1.25;">Priya Sharma</div>
                                <div style="font-size:.75rem;color:#64748b;display:flex;align-items:center;gap:.35rem;margin-top:1px;">
                                    <i class="ti ti-calendar-off" style="font-size:.7rem;color:#f59e0b;"></i>
                                    On Leave &middot; back Mon
                                </div>
                            </div>
                            <span style="font-size:.65rem;font-weight:600;color:#ef4444;background:#fef2f2;padding:.2rem .55rem;border-radius:6px;">HR</span>
                        </div>

                        {{-- Row 4 --}}
                        <div style="display:flex;align-items:center;gap:.85rem;padding:.75rem .85rem;border-radius:12px;transition:background .2s;cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;">RK</div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;background:#10b981;border:2px solid #fff;border-radius:50%;"></span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.9rem;font-weight:600;color:#0f172a;line-height:1.25;">Rohan Kumar</div>
                                <div style="font-size:.75rem;color:#64748b;display:flex;align-items:center;gap:.35rem;margin-top:1px;">
                                    <i class="ti ti-briefcase" style="font-size:.7rem;"></i>
                                    Designer
                                </div>
                            </div>
                            <span style="font-size:.65rem;font-weight:600;color:#8b5cf6;background:#f5f3ff;padding:.2rem .55rem;border-radius:6px;">Design</span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div style="padding:.85rem 1.25rem;border-top:1px solid #f1f5f9;background:#fafbfc;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:.75rem;color:#64748b;">Showing 4 of 248</span>
                        <a href="#" style="font-size:.75rem;font-weight:600;color:#2563eb;text-decoration:none;display:flex;align-items:center;gap:.25rem;" onclick="return false;">
                            View all <i class="ti ti-arrow-right" style="font-size:.85rem;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature 2: Right text, Left visual (reversed) --}}
        <div class="lp-feature-row lp-feature-row-reverse">
            <div class="lp-feature-text">
                <span class="lp-feature-num">02</span>
                <h3>{{ __('Automated Payroll & Compliance') }}</h3>
                <p>{{ __('Run payroll in one click with auto-calculated taxes, statutory deductions (PF, ESI, TDS), and instant payslip generation. Multi-currency supported.') }}</p>
                <ul class="lp-feature-list">
                    <li><i class="ti ti-check"></i> {{ __('One-click salary processing') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Auto PF, ESI, TDS & statutory compliance') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('20+ payment gateway integrations') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('PDF payslips & bulk email delivery') }}</li>
                </ul>
            </div>
            <div class="lp-feature-visual">
                <div class="lp-payroll-mockup">
                    <div class="lp-payroll-header">
                        <span class="lp-payroll-title">{{ __('Payroll Summary') }}</span>
                        <span class="lp-payroll-month">March 2026</span>
                    </div>
                    <div class="lp-payroll-row">
                        <span>{{ __('Basic Salary') }}</span>
                        <span class="lp-payroll-amount">₹45,000</span>
                    </div>
                    <div class="lp-payroll-row">
                        <span>{{ __('HRA') }}</span>
                        <span class="lp-payroll-amount">₹18,000</span>
                    </div>
                    <div class="lp-payroll-row lp-payroll-deduction">
                        <span>{{ __('PF Deduction') }}</span>
                        <span class="lp-payroll-amount">-₹5,400</span>
                    </div>
                    <div class="lp-payroll-row lp-payroll-deduction">
                        <span>{{ __('TDS') }}</span>
                        <span class="lp-payroll-amount">-₹3,750</span>
                    </div>
                    <div class="lp-payroll-total">
                        <span>{{ __('Net Pay') }}</span>
                        <span class="lp-payroll-total-amount">₹53,850</span>
                    </div>
                    <div class="lp-payroll-status">
                        <i class="ti ti-circle-check-filled" style="color:#10b981;"></i>
                        {{ __('Processed & Sent') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature 3: Left text, Right visual --}}
        <div class="lp-feature-row">
            <div class="lp-feature-text">
                <span class="lp-feature-num">03</span>
                <h3>{{ __('Attendance & Leave Tracking') }}</h3>
                <p>{{ __('Multiple clock-in methods: web, mobile GPS, biometric, or facial recognition. Real-time tracking with automated leave balance calculations.') }}</p>
                <ul class="lp-feature-list">
                    <li><i class="ti ti-check"></i> {{ __('Web, mobile & biometric clock-in') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Custom leave types & approval workflows') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Real-time attendance dashboard') }}</li>
                    <li><i class="ti ti-check"></i> {{ __('Overtime & shift management') }}</li>
                </ul>
            </div>
            <div class="lp-feature-visual">
                <div class="lp-attendance-mockup">
                    <div class="lp-attendance-header">
                        <span>{{ __('Today\'s Attendance') }}</span>
                        <span class="lp-attendance-date">27 Mar, 2026</span>
                    </div>
                    <div class="lp-attendance-ring-row">
                        <div class="lp-attendance-ring">
                            <svg viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#3b82f6" stroke-width="10" stroke-dasharray="290 24" stroke-linecap="round" transform="rotate(-90 60 60)"/>
                            </svg>
                            <div class="lp-ring-text">
                                <span class="lp-ring-num">93%</span>
                                <span class="lp-ring-label">Present</span>
                            </div>
                        </div>
                        <div class="lp-attendance-breakdown">
                            <div class="lp-attend-item"><span class="lp-attend-dot" style="background:#3b82f6;"></span>{{ __('Present') }}<strong>231</strong></div>
                            <div class="lp-attend-item"><span class="lp-attend-dot" style="background:#f59e0b;"></span>{{ __('Late') }}<strong>8</strong></div>
                            <div class="lp-attend-item"><span class="lp-attend-dot" style="background:#ef4444;"></span>{{ __('Absent') }}<strong>5</strong></div>
                            <div class="lp-attend-item"><span class="lp-attend-dot" style="background:#8b5cf6;"></span>{{ __('On Leave') }}<strong>12</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     MODULES GRID — All HR modules in beautiful cards
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-section lp-section-soft" id="modules">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('Complete HR Suite') }}</span>
            <h2 class="lp-heading">{{ __('12+ Powerful Modules') }}</h2>
            <p class="lp-subheading">{{ __('Every tool your HR department needs — from recruitment to retirement.') }}</p>
        </div>
        <div class="lp-modules-grid">
            @php
            $modules = [
                ['icon'=>'ti-users','title'=>__('Employee Directory'),'desc'=>__('Centralized profiles, documents & org charts'),'color'=>'#3b82f6'],
                ['icon'=>'ti-fingerprint','title'=>__('Attendance'),'desc'=>__('Web, biometric & GPS-based tracking'),'color'=>'#8b5cf6'],
                ['icon'=>'ti-calendar-event','title'=>__('Leave Management'),'desc'=>__('Custom policies, accruals & approvals'),'color'=>'#10b981'],
                ['icon'=>'ti-report-money','title'=>__('Payroll'),'desc'=>__('Auto salary, deductions & payslips'),'color'=>'#f59e0b'],
                ['icon'=>'ti-scale','title'=>__('Compliance'),'desc'=>__('PF, ESI, TDS & statutory filings'),'color'=>'#ef4444'],
                ['icon'=>'ti-chart-arrows','title'=>__('Performance'),'desc'=>__('KPIs, OKRs & 360° reviews'),'color'=>'#06b6d4'],
                ['icon'=>'ti-briefcase','title'=>__('Recruitment'),'desc'=>__('ATS, job posting & onboarding'),'color'=>'#ec4899'],
                ['icon'=>'ti-school','title'=>__('Training'),'desc'=>__('Programs, certifications & tracking'),'color'=>'#14b8a6'],
                ['icon'=>'ti-device-laptop','title'=>__('Assets'),'desc'=>__('Track company devices & equipment'),'color'=>'#6366f1'],
                ['icon'=>'ti-receipt','title'=>__('Expenses'),'desc'=>__('Submit, approve & reimburse claims'),'color'=>'#f97316'],
                ['icon'=>'ti-chart-pie','title'=>__('Reports'),'desc'=>__('50+ reports with Excel & PDF export'),'color'=>'#0ea5e9'],
                ['icon'=>'ti-file-certificate','title'=>__('Contracts'),'desc'=>__('Generate & e-sign agreements'),'color'=>'#84cc16'],
            ];
            @endphp
            @foreach($modules as $idx => $mod)
            <div class="lp-module-card lp-module-card-horiz">
                <div class="lp-module-icon-h" style="color: {{ $mod['color'] }};">
                    <i class="ti {{ $mod['icon'] }}"></i>
                </div>
                <div class="lp-module-body">
                    <h3 class="lp-module-title">{{ $mod['title'] }}</h3>
                    <p class="lp-module-desc">{{ $mod['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     HOW IT WORKS — 3 steps
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-section lp-section-white" id="how-it-works">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('Get Started') }}</span>
            <h2 class="lp-heading">{{ __('Up and Running in 3 Steps') }}</h2>
            <p class="lp-subheading">{{ __('No complex setup required. Get your entire HR system live in under 5 minutes.') }}</p>
        </div>
        <div class="lp-steps">
            <div class="lp-steps-line"></div>
            <div class="lp-step">
                <div class="lp-step-icon">
                    <span>1</span>
                </div>
                <h3>{{ __('Create Account') }}</h3>
                <p>{{ __('Sign up with your work email. Set up company profile, departments, and team structure.') }}</p>
            </div>
            <div class="lp-step">
                <div class="lp-step-icon">
                    <span>2</span>
                </div>
                <h3>{{ __('Add Your Team') }}</h3>
                <p>{{ __('Import employees via Excel or add them manually. Configure roles, salaries, and leave policies.') }}</p>
            </div>
            <div class="lp-step">
                <div class="lp-step-icon">
                    <span>3</span>
                </div>
                <h3>{{ __('Automate & Scale') }}</h3>
                <p>{{ __('Attendance syncs automatically, payroll runs itself, reports generate instantly. You focus on strategy.') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     INTEGRATIONS — hidden for now
     ═══════════════════════════════════════════════════════════════ --}}
@if(false)
<section class="lp-section lp-section-dark-gradient">
    <div class="lp-container">
        <div class="lp-integrations-content">
            <div class="lp-integrations-text">
                <span class="lp-tag lp-tag-light">{{ __('Integrations') }}</span>
                <h2 class="lp-heading lp-heading-white">{{ __('Connects With Your') }}<br>{{ __('Favorite Tools') }}</h2>
                <p class="lp-subheading lp-subheading-light">{{ __('Seamlessly integrate with 20+ payment gateways, communication tools, and cloud storage providers.') }}</p>
                <div class="lp-integrations-list">
                    <div class="lp-int-item"><i class="ti ti-brand-stripe"></i><span>Stripe</span></div>
                    <div class="lp-int-item"><i class="ti ti-brand-paypal"></i><span>PayPal</span></div>
                    <div class="lp-int-item"><i class="ti ti-mail"></i><span>SMTP</span></div>
                    <div class="lp-int-item"><i class="ti ti-brand-zoom"></i><span>Zoom</span></div>
                    <div class="lp-int-item"><i class="ti ti-brand-slack"></i><span>Slack</span></div>
                    <div class="lp-int-item"><i class="ti ti-cloud"></i><span>AWS S3</span></div>
                </div>
            </div>
            <div class="lp-integrations-visual">
                <div class="lp-int-grid">
                    @foreach(['Razorpay','Paytm','Paystack','Flutterwave','Mercado Pago','Mollie','Skrill','Cashfree','Aamarpay','PayTR','Xendit','Twilio'] as $gw)
                    <div class="lp-int-card">{{ $gw }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     PRICING
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-section lp-section-soft" id="pricing">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('Pricing') }}</span>
            <h2 class="lp-heading">{{ __('Simple, Transparent Pricing') }}</h2>
            <p class="lp-subheading">{{ __('No hidden fees. No surprises. Choose the plan that fits your team.') }}</p>
        </div>
        <div class="lp-pricing-grid">
            @php
            $pricing_plans = [
                [
                    'name' => 'Free Plan',
                    'price_html' => '<div class="lp-price-amount" style="font-size:1.75rem;font-weight:800;color:#10b981;">' . __('Free for 30 days') . '</div><div class="lp-price-period">' . __('No credit card required') . '</div>',
                    'desc' => __('Try Jemini risk-free. Full access to all core HR modules for 30 days, no credit card needed.'),
                    'features' => [
                        ['t' => __('Unlimited Users'), 'ok' => true],
                        ['t' => __('Unlimited Employees'), 'ok' => true],
                        ['t' => __('Core HR Modules'), 'ok' => true],
                        ['t' => __('Email Support'), 'ok' => true],
                        ['t' => __('AI / ChatGPT'), 'ok' => false],
                    ],
                    'cta' => __('Sign In'),
                    'featured' => false,
                    'badge' => null,
                ],
                [
                    'name' => 'Premium',
                    'price_html' => '<div class="lp-price-amount"><span class="lp-price-currency">₹</span><span class="lp-price-value">99</span></div><div class="lp-price-period">/' . __('user / month') . '</div>',
                    'desc' => __('For growing teams. Everything in Free plus unlimited employees, payroll, attendance & advanced reporting.'),
                    'features' => [
                        ['t' => __('Unlimited Users'), 'ok' => true],
                        ['t' => __('Unlimited Employees'), 'ok' => true],
                        ['t' => __('All HR Modules'), 'ok' => true],
                        ['t' => __('Payroll & Compliance'), 'ok' => true],
                        ['t' => __('Priority Support'), 'ok' => true],
                        ['t' => __('AI / ChatGPT'), 'ok' => true],
                    ],
                    'cta' => __('Sign In'),
                    'featured' => false,
                    'badge' => null,
                ],
                [
                    'name' => 'Ultimate',
                    'price_html' => '<div class="lp-price-amount"><span class="lp-price-currency">₹</span><span class="lp-price-value">144</span></div><div class="lp-price-period">/' . __('user / month') . '</div>',
                    'desc' => __('For large enterprises. Everything in Premium plus screen monitoring, custom integrations & dedicated account manager.'),
                    'features' => [
                        ['t' => __('Unlimited Users'), 'ok' => true],
                        ['t' => __('Unlimited Employees'), 'ok' => true],
                        ['t' => __('Unlimited Storage'), 'ok' => true],
                        ['t' => __('All HR Modules'), 'ok' => true],
                        ['t' => __('Activity Tracker & Screen Capture'), 'ok' => true],
                        ['t' => __('Custom Integrations & API'), 'ok' => true],
                        ['t' => __('Dedicated Account Manager'), 'ok' => true],
                        ['t' => __('AI / ChatGPT (Unlimited)'), 'ok' => true],
                    ],
                    'cta' => __('Sign In'),
                    'featured' => false,
                    'badge' => null,
                ],
            ];
            @endphp
            @foreach($pricing_plans as $k => $p)
            <div class="lp-price-card{{ $p['featured'] ? ' lp-price-featured' : '' }}">
                @if($p['badge'])
                <div class="lp-price-popular">{{ $p['badge'] }}</div>
                @endif
                <div class="lp-price-header">
                    <h3 class="lp-price-name">{{ $p['name'] }}</h3>
                    {!! $p['price_html'] !!}
                    <p class="lp-price-desc">{{ $p['desc'] }}</p>
                </div>
                <ul class="lp-price-features">
                    @foreach($p['features'] as $f)
                    <li>
                        @if($f['ok'])
                            <i class="ti ti-check"></i>
                        @else
                            <i class="ti ti-x" style="color:#ef4444;"></i>
                        @endif
                        {{ $f['t'] }}
                    </li>
                    @endforeach
                </ul>
                @if(!isset($adminSettings['disable_signup_button']) || $adminSettings['disable_signup_button'] != 'on')
                <a href="{{ route('register') }}" class="lp-btn lp-btn-primary lp-btn-md lp-btn-block">
                    {{ $p['cta'] }} <i class="ti ti-arrow-right"></i>
                </a>
                @else
                <a href="{{ route('login') }}" class="lp-btn lp-btn-primary lp-btn-md lp-btn-block">
                    {{ __('Sign In') }} <i class="ti ti-arrow-right"></i>
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- TESTIMONIALS — hidden until genuine reviews arrive
@if(false)
<section class="lp-section lp-section-white" id="testimonials-hidden">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('Customer Stories') }}</span>
            <h2 class="lp-heading">{{ __('Loved by HR Teams Worldwide') }}</h2>
            <p class="lp-subheading">{{ __('See what our customers have to say about their experience.') }}</p>
        </div>
        <div class="lp-testimonials-grid">
            @php
            $testimonials = [
                ['q'=>'We moved from spreadsheets to this HRMS and cut payroll processing time by 80%. The statutory compliance module alone saves us 2 days every month.','name'=>'Priya Sharma','role'=>'HR Director, TechCorp India','init'=>'PS','grad'=>'linear-gradient(135deg,#3b82f6,#8b5cf6)'],
                ['q'=>'The attendance tracking with facial recognition is incredible. Our field teams clock in from their phones and the data is always accurate and real-time.','name'=>'James Mitchell','role'=>'Operations Manager, BuildCo UK','init'=>'JM','grad'=>'linear-gradient(135deg,#ec4899,#f59e0b)'],
                ['q'=>'Onboarding new employees used to take days. Now it takes minutes. The document management and contract signing features are genuinely world-class.','name'=>'Sarah Al-Rashidi','role'=>'People Lead, FinanceHub UAE','init'=>'SA','grad'=>'linear-gradient(135deg,#10b981,#06b6d4)'],
            ];
            @endphp
            @foreach($testimonials as $t)
            <div class="lp-testi-card">
                <div class="lp-testi-stars">
                    @for($i=0;$i<5;$i++)<i class="ti ti-star-filled"></i>@endfor
                </div>
                <blockquote class="lp-testi-quote">&ldquo;{{ $t['q'] }}&rdquo;</blockquote>
                <div class="lp-testi-author">
                    <div class="lp-testi-avatar lp-testi-grad-{{ $loop->index }}">{{ $t['init'] }}</div>
                    <div>
                        <div class="lp-testi-name">{{ $t['name'] }}</div>
                        <div class="lp-testi-role">{{ $t['role'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
{{-- end testimonials hidden --}}

{{-- ═══════════════════════════════════════════════════════════════
     FAQ
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-section lp-section-soft" id="faq">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-tag">{{ __('FAQ') }}</span>
            <h2 class="lp-heading">{{ __('Got Questions?') }}</h2>
            <p class="lp-subheading">{{ __('Everything you need to know about the platform.') }}</p>
        </div>
        <div class="lp-faq-list">
            @php
            $faqs = [
                [__('Is there a free trial available?'), __('Yes! You can start with a free trial — no credit card required. Explore all features and modules before committing to a plan.')],
                [__('How is attendance tracked?'), __('We support multiple methods: web clock-in/out, biometric device integration, mobile GPS-based check-in, and AI-powered facial recognition.')],
                [__('Can I import existing employee data?'), __('Absolutely. Import employees via Excel/CSV using our built-in template. We also support bulk import for attendance records, holidays, and more.')],
                [__('Does it handle multi-country payroll?'), __('Yes. Multi-currency payroll with country-specific statutory deductions (PF, ESI, TDS for India; NI, PAYE for UK, etc.) is fully supported.')],
                [__('Is the platform available in multiple languages?'), __('Yes — 15+ languages including Arabic (RTL), Hindi, Spanish, French, German, and more. Language can be switched per user.')],
                [__('How secure is my data?'), __('Bank-grade security: AES-256 encryption at rest, SOC 2 compliance framework, role-based access control, complete audit logs, and optional two-factor authentication.')],
                [__('Can I monitor employee screen?'), __('Yes. Our desktop activity tracker captures periodic screenshots, tracks active applications and websites, and logs idle time — all with full transparency to the employee. Screenshots can be enabled or disabled per team and viewed only by authorised admins.')],
            ];
            @endphp
            @foreach($faqs as $faq)
            <div class="lp-faq-item">
                <button class="lp-faq-question" type="button">
                    <span>{{ $faq[0] }}</span>
                    <i class="ti ti-plus"></i>
                </button>
                <div class="lp-faq-answer">
                    <p>{{ $faq[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     CTA BANNER
     ═══════════════════════════════════════════════════════════════ --}}
<section class="lp-cta-section">
    <div class="lp-container">
        <div class="lp-cta-card">
            <div class="lp-cta-bg-pattern"></div>
            <h2>{{ __('Ready to Transform Your HR?') }}</h2>
            <p>{{ __('Setup takes less than 5 minutes — no credit card required. Start your free demo today.') }}</p>
            <div class="lp-cta-actions">
                <a href="#" onclick="openDemoModal();return false;" class="lp-btn lp-btn-white lp-btn-lg">
                    <i class="ti ti-rocket"></i> {{ __('Free Demo') }}
                </a>
                <a href="{{ route('login') }}" class="lp-btn lp-btn-outline-white lp-btn-lg">
                    <i class="ti ti-login"></i> {{ __('Sign In') }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════════════ --}}
<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer-grid">
            <div class="lp-footer-brand">
                <a href="{{ url('/') }}" class="lp-footer-logo" style="display:flex;align-items:center;gap:.7rem;text-decoration:none;">
                    <img src="{{ $logo_main_url . '?' . time() }}" alt="{{ $appName }}"
                         style="background:#fff;border-radius:8px;padding:4px;"
                         onerror="this.style.display='none';">
                    <span style="font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.02em;border-bottom:2px solid #fff;padding-bottom:2px;">Jemini</span>
                </a>
                <p>{{ __('The complete HR platform for modern businesses. Automate your entire HR operation and focus on what matters — your people.') }}</p>
                <div class="lp-footer-socials">
                    <a href="#" aria-label="LinkedIn"><i class="ti ti-brand-linkedin"></i></a>
                    <a href="#" aria-label="Twitter"><i class="ti ti-brand-twitter"></i></a>
                    <a href="#" aria-label="Facebook"><i class="ti ti-brand-facebook"></i></a>
                    <a href="#" aria-label="YouTube"><i class="ti ti-brand-youtube"></i></a>
                </div>
            </div>
            <div>
                <h5>{{ __('Product') }}</h5>
                <ul>
                    <li><a href="#features">{{ __('Features') }}</a></li>
                    <li><a href="#modules">{{ __('Modules') }}</a></li>
                    <li><a href="#pricing">{{ __('Pricing') }}</a></li>
                    <li><a href="#how-it-works">{{ __('How It Works') }}</a></li>
                    <li><a href="#faq">{{ __('FAQ') }}</a></li>
                </ul>
            </div>
            <div>
                <h5>{{ __('Modules') }}</h5>
                <ul>
                    <li><a href="{{ route('login') }}">{{ __('Payroll') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('Attendance') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('Recruitment') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('Performance') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('Reports') }}</a></li>
                </ul>
            </div>
            <div>
                <h5>{{ __('Account') }}</h5>
                <ul>
                    <li><a href="{{ route('login') }}">{{ __('Login') }}</a></li>
                    @if(!isset($adminSettings['disable_signup_button']) || $adminSettings['disable_signup_button'] != 'on')
                    <li><a href="{{ route('register') }}">{{ __('Register') }}</a></li>
                    @endif
                    @if(Route::has('password.request'))
                    <li><a href="{{ route('password.request', app()->getLocale()) }}">{{ __('Forgot Password') }}</a></li>
                    @endif
                </ul>
                <div class="lp-footer-support">
                    <span>{{ __('Need help?') }}</span>
                    <a href="mailto:hello@jemini.co.in">{{ __('Contact Support: hello@jemini.co.in') }} &rarr;</a>
                </div>
            </div>
        </div>
        <div class="lp-footer-bottom">
            <span>&copy; {{ date('Y') }} {{ $appName }}. {{ __('All rights reserved.') }}</span>
        </div>
    </div>
</footer>

{{-- ═══════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════ --}}
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor-all.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script>
(function(){
    // Mobile nav
    var hamburger = document.getElementById('lp-hamburger');
    var menu = document.getElementById('lp-nav-menu');
    if(hamburger && menu) {
        hamburger.addEventListener('click', function(){
            hamburger.classList.toggle('is-active');
            menu.classList.toggle('is-open');
        });
    }

    // FAQ accordion
    document.querySelectorAll('.lp-faq-question').forEach(function(btn){
        btn.addEventListener('click', function(){
            var item = btn.closest('.lp-faq-item');
            var isOpen = item.classList.contains('is-open');
            document.querySelectorAll('.lp-faq-item.is-open').forEach(function(el){ el.classList.remove('is-open'); });
            if(!isOpen) item.classList.add('is-open');
        });
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(function(a){
        a.addEventListener('click', function(e){
            var t = document.querySelector(a.getAttribute('href'));
            if(t){ e.preventDefault(); t.scrollIntoView({behavior:'smooth',block:'start'}); if(menu) menu.classList.remove('is-open'); if(hamburger) hamburger.classList.remove('is-active'); }
        });
    });

    // Scroll reveal animation
    var observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
            if(entry.isIntersecting){ entry.target.classList.add('is-visible'); }
        });
    }, {threshold:0.1, rootMargin:'0px 0px -40px 0px'});
    document.querySelectorAll('.lp-feature-row, .lp-module-card, .lp-step, .lp-testi-card, .lp-stat, .lp-price-card').forEach(function(el){ observer.observe(el); });

    // Navbar scroll effect
    var navbar = document.querySelector('.lp-navbar');
    if(navbar){
        window.addEventListener('scroll', function(){
            navbar.classList.toggle('is-scrolled', window.scrollY > 20);
        });
    }
})();
</script>

@php $cookieSetting = Utility::getCookieSetting(); @endphp
@if(isset($cookieSetting['enable_cookie']) && $cookieSetting['enable_cookie'] == 'on')
    @include('layouts.cookie_consent')
@endif

{{-- ═══ FREE DEMO MODAL ═══ --}}
@php
$demo_industries = ['Agriculture & Farming','Automotive','Banking & Financial Services','Biotechnology','Chemicals','Construction','Consumer Goods','Defense & Aerospace','Education & Training','Energy & Utilities','Entertainment & Media','Fashion & Apparel','Food & Beverage','Government & Public Sector','Healthcare & Pharmaceuticals','Hospitality & Tourism','Human Resources','Information Technology (IT)','Insurance','Legal Services','Logistics & Supply Chain','Manufacturing','Marketing & Advertising','Mining & Metals','Nonprofit / NGO','Oil & Gas','Real Estate','Retail & E-commerce','Telecommunications','Transportation','Travel Services','Warehousing','Wholesale Distribution','Sports & Fitness','Research & Development','Security Services','Software & SaaS','Electronics & Semiconductors','Environmental Services','Consulting Services','Printing & Publishing','Architecture & Interior Design','Event Management','Photography & Videography','Import & Export','Marine & Shipping','Religious Institutions','Freelance / Self-Employed','Startups','Other'];
@endphp

<div id="fd-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);overflow-y:auto;padding:2rem 1rem;">
    <div id="fd-modal" style="background:#fff;border-radius:20px;box-shadow:0 25px 60px rgba(0,0,0,.25);max-width:520px;width:100%;margin:auto;padding:2.5rem 2.5rem 2rem;position:relative;animation:fdSlideIn .28s cubic-bezier(.34,1.4,.64,1) both;">
        <button onclick="closeDemoModal()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.4rem;line-height:1;padding:.25rem;" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>

        <div id="fd-modal-header" style="text-align:center;margin-bottom:1.75rem;">
            <span style="display:inline-block;background:#eff6ff;color:#2563eb;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-radius:99px;padding:.25rem .9rem;margin-bottom:.65rem;"><i class="ti ti-rocket"></i> {{ __('Free Demo') }}</span>
            <h2 style="font-size:1.55rem;font-weight:800;color:#0f172a;margin:0 0 .4rem;line-height:1.2;">{{ __('Try') }} {{ $appName }} {{ __('Free') }}</h2>
            <p style="color:#64748b;font-size:.9rem;margin:0;">{{ __('Fill in your details — our team will reach out shortly.') }}</p>
        </div>

        <div id="fd-modal-success" style="display:none;text-align:center;padding:2rem 0 1rem;">
            <i class="ti ti-circle-check-filled" style="font-size:3.5rem;color:#10b981;"></i>
            <h3 style="font-size:1.4rem;font-weight:700;color:#0f172a;margin:1rem 0 .5rem;">{{ __('Request Received!') }}</h3>
            <p style="color:#64748b;font-size:.95rem;line-height:1.55;margin:0 0 .5rem;">{{ __('Your demo account has been created. We have emailed your login credentials to the address you provided — please check your inbox (and spam folder) in a minute or two.') }}</p>
            <button type="button" onclick="closeDemoModal()" style="display:inline-block;margin-top:1.25rem;background:#2563eb;color:#fff;border:none;cursor:pointer;padding:.65rem 1.75rem;border-radius:10px;font-weight:600;font-size:.9rem;">{{ __('Close') }}</button>
        </div>

        <form id="fd-modal-form" novalidate>
            @csrf
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;">{{ __('Your Name') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" id="fdm_name" class="fdm-input" placeholder="{{ __('e.g. Rahul Sharma') }}" autocomplete="name">
                <div class="fdm-err" id="fdm_err_name">{{ __('Please enter your name.') }}</div>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;">{{ __('Company Name') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="company" id="fdm_company" class="fdm-input" placeholder="{{ __('e.g. Miraix Technologies') }}" autocomplete="organization">
                <div class="fdm-err" id="fdm_err_company">{{ __('Please enter your company name.') }}</div>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;">{{ __('Work Email') }} <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" id="fdm_email" class="fdm-input" placeholder="{{ __('you@company.com') }}" autocomplete="email">
                <div class="fdm-err" id="fdm_err_email">{{ __('Please enter a valid email.') }}</div>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;">{{ __('Phone Number') }}</label>
                <input type="tel" name="phone" id="fdm_phone" class="fdm-input" placeholder="{{ __('e.g. +91 98765 43210') }}" autocomplete="tel">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.4rem;">{{ __('Employee Strength') }} <span style="color:#ef4444;">*</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;" id="fdm-strength-group">
                    @foreach(['0–10','11–30','31–50','50 & above'] as $opt)
                    <label class="fdm-radio-label">
                        <input type="radio" name="strength" value="{{ $opt }}">
                        <span>{{ $opt }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="fdm-err" id="fdm_err_strength">{{ __('Please select employee strength.') }}</div>
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;">{{ __('Industry') }} <span style="color:#ef4444;">*</span></label>
                <select name="industry" id="fdm_industry" class="fdm-input" style="cursor:pointer;">
                    <option value="">— {{ __('Select Industry') }} —</option>
                    @foreach($demo_industries as $ind)
                    <option value="{{ $ind }}">{{ $ind }}</option>
                    @endforeach
                </select>
                <div class="fdm-err" id="fdm_err_industry">{{ __('Please select your industry.') }}</div>
            </div>
            <button type="submit" id="fdm-submit-btn" style="width:100%;background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;border:none;border-radius:12px;padding:.85rem;font-size:.95rem;font-weight:700;cursor:pointer;font-family:inherit;letter-spacing:.01em;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <i class="ti ti-rocket"></i> {{ __('Request My Free Demo') }}
            </button>
        </form>
    </div>
</div>

<style>
@keyframes fdSlideIn { from { opacity:0; transform:translateY(-24px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.fdm-input { width:100%;padding:.6rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.9rem;font-family:inherit;color:#0f172a;background:#fff;outline:none;transition:border-color .2s;box-sizing:border-box; }
.fdm-input:focus { border-color:#3b82f6; }
.fdm-input.is-err { border-color:#ef4444; }
.fdm-err { font-size:.76rem;color:#ef4444;margin-top:.2rem;display:none; }
.fdm-radio-label { display:flex;align-items:center;gap:.45rem;padding:.5rem .8rem;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:.85rem;color:#374151;transition:border-color .2s,background .2s;user-select:none; }
.fdm-radio-label:hover { border-color:#3b82f6;background:#eff6ff; }
.fdm-radio-label.is-checked { border-color:#3b82f6;background:#eff6ff;color:#1e40af;font-weight:600; }
.fdm-radio-label input[type=radio] { accent-color:#3b82f6;margin:0; }
</style>

<script>
function openDemoModal(){
    document.getElementById('fd-overlay').style.display='block';
    document.body.style.overflow='hidden';
}
function closeDemoModal(){
    document.getElementById('fd-overlay').style.display='none';
    document.body.style.overflow='';
}
// Close on backdrop click
document.getElementById('fd-overlay').addEventListener('click',function(e){
    if(e.target===this) closeDemoModal();
});
// Esc key
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeDemoModal(); });

// Radio highlight
document.querySelectorAll('.fdm-radio-label input[type=radio]').forEach(function(r){
    r.addEventListener('change',function(){
        document.querySelectorAll('.fdm-radio-label').forEach(function(l){ l.classList.remove('is-checked'); });
        r.closest('.fdm-radio-label').classList.add('is-checked');
        document.getElementById('fdm_err_strength').style.display='none';
    });
});

// Clear errors on input
['fdm_name','fdm_company','fdm_email','fdm_industry'].forEach(function(id){
    var el=document.getElementById(id);
    if(el) el.addEventListener('input',function(){ el.classList.remove('is-err'); document.getElementById('fdm_err_'+id.replace('fdm_','')).style.display='none'; });
});

document.getElementById('fd-modal-form').addEventListener('submit',function(e){
    e.preventDefault();
    var ok=true;
    function chk(fieldId,errId,test){ var el=document.getElementById(fieldId); var err=document.getElementById(errId); if(!test(el.value.trim())){ el.classList.add('is-err'); err.style.display='block'; ok=false; } else { el.classList.remove('is-err'); err.style.display='none'; } }
    chk('fdm_name','fdm_err_name',function(v){ return v.length>0; });
    chk('fdm_company','fdm_err_company',function(v){ return v.length>0; });
    chk('fdm_email','fdm_err_email',function(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); });
    chk('fdm_industry','fdm_err_industry',function(v){ return v.length>0; });
    var strength=document.querySelector('.fdm-radio-label input[type=radio]:checked');
    if(!strength){ document.getElementById('fdm_err_strength').style.display='block'; ok=false; } else { document.getElementById('fdm_err_strength').style.display='none'; }
    if(!ok) return;

    var btn=document.getElementById('fdm-submit-btn');
    btn.disabled=true;
    btn.innerHTML='<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;display:inline-block;"></i> {{ __("Submitting...") }}';

    var fd=new FormData(document.getElementById('fd-modal-form'));
    fetch('{{ route("free-demo.submit") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':fd.get('_token'),'Accept':'application/json'},
        body:fd
    }).then(function(r){ return r.json(); }).then(function(data){
        if(data.success){
            document.getElementById('fd-modal-form').style.display='none';
            document.getElementById('fd-modal-header').style.display='none';
            document.getElementById('fd-modal-success').style.display='block';
        } else {
            btn.disabled=false;
            btn.innerHTML='<i class="ti ti-rocket"></i> {{ __("Request My Free Demo") }}';
            alert(data.message||'{{ __("Something went wrong.") }}');
        }
    }).catch(function(){
        btn.disabled=false;
        btn.innerHTML='<i class="ti ti-rocket"></i> {{ __("Request My Free Demo") }}';
        alert('{{ __("Network error. Please try again.") }}');
    });
});
</script>
<style>@keyframes spin { to { transform:rotate(360deg); } }</style>

</body>
</html>
