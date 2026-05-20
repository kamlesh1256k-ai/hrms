@php
    use App\Models\Utility;
    $sup_logo     = Utility::get_file('uploads/logo');
    $company_logo = Utility::GetLogo();
    $logo_main_url = rtrim($sup_logo, '/') . '/' . (!empty($company_logo) ? $company_logo : 'logo-dark.png');
    $appName      = Utility::getValByName('title_text') ?: config('app.name', 'HRMS');
    $setting      = Utility::colorset();
    $isDark       = isset($setting['cust_darklayout']) && $setting['cust_darklayout'] == 'on';
    $SITE_RTL     = Utility::getValByName('SITE_RTL');

    $industries = [
        'Agriculture & Farming','Automotive','Banking & Financial Services','Biotechnology','Chemicals',
        'Construction','Consumer Goods','Defense & Aerospace','Education & Training','Energy & Utilities',
        'Entertainment & Media','Fashion & Apparel','Food & Beverage','Government & Public Sector',
        'Healthcare & Pharmaceuticals','Hospitality & Tourism','Human Resources','Information Technology (IT)',
        'Insurance','Legal Services','Logistics & Supply Chain','Manufacturing','Marketing & Advertising',
        'Mining & Metals','Nonprofit / NGO','Oil & Gas','Real Estate','Retail & E-commerce',
        'Telecommunications','Transportation','Travel Services','Warehousing','Wholesale Distribution',
        'Sports & Fitness','Research & Development','Security Services','Software & SaaS',
        'Electronics & Semiconductors','Environmental Services','Consulting Services','Printing & Publishing',
        'Architecture & Interior Design','Event Management','Photography & Videography','Import & Export',
        'Marine & Shipping','Religious Institutions','Freelance / Self-Employed','Startups','Other',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $SITE_RTL == 'on' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('Free Demo') }} — {{ $appName }}</title>
    <link rel="icon" href="{{ $sup_logo }}/favicon.png" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/landingpage/css/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/landingpage/css/hrms-landing.css') }}">
    <style>
        .fd-page { min-height: 100vh; display: flex; flex-direction: column; background: #f8fafc; }
        .fd-navbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; }
        .fd-navbar-logo img { height: 36px; object-fit: contain; }
        .fd-navbar-logo-text { font-size: 1.3rem; font-weight: 700; color: #1e3a8a; text-decoration: none; }
        .fd-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1rem; }
        .fd-card { background: #fff; border-radius: 20px; box-shadow: 0 4px 40px rgba(30,58,138,.10); max-width: 560px; width: 100%; padding: 2.5rem 2.5rem 2rem; }
        .fd-card-header { text-align: center; margin-bottom: 2rem; }
        .fd-tag { display: inline-block; background: #eff6ff; color: #2563eb; font-size: .75rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; border-radius: 99px; padding: .25rem .9rem; margin-bottom: .75rem; }
        .fd-card-header h1 { font-size: 1.7rem; font-weight: 800; color: #0f172a; margin: 0 0 .5rem; line-height: 1.2; }
        .fd-card-header p { color: #64748b; font-size: .95rem; margin: 0; }
        .fd-form-group { margin-bottom: 1.25rem; }
        .fd-label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .fd-input, .fd-select { width: 100%; padding: .65rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: .95rem; font-family: inherit; color: #0f172a; background: #fff; outline: none; transition: border-color .2s; box-sizing: border-box; }
        .fd-input:focus, .fd-select:focus { border-color: #3b82f6; }
        .fd-input.is-error, .fd-select.is-error { border-color: #ef4444; }
        .fd-error-msg { font-size: .78rem; color: #ef4444; margin-top: .25rem; display: none; }
        .fd-radio-group { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
        .fd-radio-label { display: flex; align-items: center; gap: .5rem; padding: .55rem .9rem; border: 1.5px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: .88rem; color: #374151; transition: border-color .2s, background .2s; }
        .fd-radio-label:hover { border-color: #3b82f6; background: #eff6ff; }
        .fd-radio-label input[type=radio] { accent-color: #3b82f6; }
        .fd-radio-label.is-checked { border-color: #3b82f6; background: #eff6ff; color: #1e40af; font-weight: 600; }
        .fd-submit { width: 100%; background: linear-gradient(135deg, #2563eb, #1e40af); color: #fff; border: none; border-radius: 12px; padding: .85rem; font-size: 1rem; font-weight: 700; cursor: pointer; transition: opacity .2s, transform .1s; margin-top: .5rem; font-family: inherit; letter-spacing: .01em; }
        .fd-submit:hover { opacity: .92; }
        .fd-submit:active { transform: scale(.99); }
        .fd-success { display: none; text-align: center; padding: 1.5rem 0; }
        .fd-success i { font-size: 3rem; color: #10b981; }
        .fd-success h2 { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: .75rem 0 .5rem; }
        .fd-success p { color: #64748b; font-size: .95rem; }
        .fd-success a { display: inline-block; margin-top: 1.25rem; background: #2563eb; color: #fff; padding: .7rem 2rem; border-radius: 10px; font-weight: 600; text-decoration: none; }
        .fd-footer { text-align: center; padding: 1.25rem; color: #94a3b8; font-size: .8rem; }
        @media(max-width:500px){
            .fd-card { padding: 1.75rem 1.25rem; }
            .fd-radio-group { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body style="font-family:'Inter',sans-serif;">
<div class="fd-page">

    <nav class="fd-navbar">
        <a href="{{ url('/') }}" class="fd-navbar-logo">
            <img src="{{ $logo_main_url }}" alt="{{ $appName }}"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
            <span class="fd-navbar-logo-text" style="display:none;">{{ $appName }}</span>
        </a>
        <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost lp-btn-sm">{{ __('Sign In') }}</a>
    </nav>

    <main class="fd-main">
        <div class="fd-card">
            <div class="fd-card-header">
                <span class="fd-tag"><i class="ti ti-sparkles"></i> Free Demo</span>
                <h1>{{ __('Try') }} {{ $appName }} {{ __('Free') }}</h1>
                <p>{{ __('Fill in your details and we\'ll set up your demo account instantly.') }}</p>
            </div>

            <div id="fd-success" class="fd-success">
                <i class="ti ti-circle-check-filled"></i>
                <h2>{{ __('You\'re all set!') }}</h2>
                <p>{{ __('Your demo request has been received. We\'ll send your login credentials to your email within a few minutes.') }}</p>
                <a href="{{ route('login') }}"><i class="ti ti-login"></i> {{ __('Go to Login') }}</a>
            </div>

            <form id="fd-form" novalidate>
                @csrf
                <div class="fd-form-group">
                    <label class="fd-label" for="fd_name">{{ __('Your Name') }} <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="fd_name" name="name" class="fd-input" placeholder="{{ __('e.g. Rahul Sharma') }}" autocomplete="name">
                    <div class="fd-error-msg" id="err_name">{{ __('Please enter your name.') }}</div>
                </div>
                <div class="fd-form-group">
                    <label class="fd-label" for="fd_company">{{ __('Company Name') }} <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="fd_company" name="company" class="fd-input" placeholder="{{ __('e.g. Miraix Technologies') }}" autocomplete="organization">
                    <div class="fd-error-msg" id="err_company">{{ __('Please enter your company name.') }}</div>
                </div>
                <div class="fd-form-group">
                    <label class="fd-label" for="fd_email">{{ __('Work Email') }} <span style="color:#ef4444;">*</span></label>
                    <input type="email" id="fd_email" name="email" class="fd-input" placeholder="{{ __('you@company.com') }}" autocomplete="email">
                    <div class="fd-error-msg" id="err_email">{{ __('Please enter a valid email address.') }}</div>
                </div>
                <div class="fd-form-group">
                    <label class="fd-label" for="fd_phone">{{ __('Phone Number') }}</label>
                    <input type="tel" id="fd_phone" name="phone" class="fd-input" placeholder="{{ __('e.g. +91 98765 43210') }}" autocomplete="tel">
                </div>
                <div class="fd-form-group">
                    <label class="fd-label">{{ __('Employee Strength') }} <span style="color:#ef4444;">*</span></label>
                    <div class="fd-radio-group" id="strength-group">
                        @foreach(['0–10','11–30','31–50','50 & above'] as $opt)
                        <label class="fd-radio-label">
                            <input type="radio" name="strength" value="{{ $opt }}">
                            {{ $opt }}
                        </label>
                        @endforeach
                    </div>
                    <div class="fd-error-msg" id="err_strength">{{ __('Please select your employee strength.') }}</div>
                </div>
                <div class="fd-form-group">
                    <label class="fd-label" for="fd_industry">{{ __('Industry') }} <span style="color:#ef4444;">*</span></label>
                    <select id="fd_industry" name="industry" class="fd-select">
                        <option value="">— {{ __('Select Industry') }} —</option>
                        @foreach($industries as $ind)
                        <option value="{{ $ind }}">{{ $ind }}</option>
                        @endforeach
                    </select>
                    <div class="fd-error-msg" id="err_industry">{{ __('Please select your industry.') }}</div>
                </div>

                <button type="submit" class="fd-submit" id="fd-submit-btn">
                    <i class="ti ti-rocket"></i> {{ __('Request My Free Demo') }}
                </button>
            </form>
        </div>
    </main>

    <footer class="fd-footer">
        &copy; {{ date('Y') }} {{ $appName }}. {{ __('All rights reserved.') }}
    </footer>
</div>

<script>
(function(){
    // Radio labels highlight
    document.querySelectorAll('.fd-radio-label input[type=radio]').forEach(function(r){
        r.addEventListener('change', function(){
            document.querySelectorAll('.fd-radio-label').forEach(function(l){ l.classList.remove('is-checked'); });
            r.closest('.fd-radio-label').classList.add('is-checked');
            document.getElementById('err_strength').style.display = 'none';
            document.getElementById('strength-group').querySelectorAll('.fd-radio-label').forEach(function(l){ l.style.borderColor=''; });
        });
    });

    function showErr(id, inputId){ document.getElementById(id).style.display='block'; if(inputId){ var el=document.getElementById(inputId); if(el) el.classList.add('is-error'); } }
    function clearErr(id, inputId){ document.getElementById(id).style.display='none'; if(inputId){ var el=document.getElementById(inputId); if(el) el.classList.remove('is-error'); } }

    ['fd_name','fd_company','fd_email','fd_industry'].forEach(function(id){
        var el = document.getElementById(id);
        if(el) el.addEventListener('input', function(){ el.classList.remove('is-error'); });
    });

    document.getElementById('fd-form').addEventListener('submit', function(e){
        e.preventDefault();
        var ok = true;
        var name = document.getElementById('fd_name').value.trim();
        var company = document.getElementById('fd_company').value.trim();
        var email = document.getElementById('fd_email').value.trim();
        var industry = document.getElementById('fd_industry').value;
        var strength = document.querySelector('input[name=strength]:checked');

        if(!name){ showErr('err_name','fd_name'); ok=false; } else { clearErr('err_name','fd_name'); }
        if(!company){ showErr('err_company','fd_company'); ok=false; } else { clearErr('err_company','fd_company'); }
        if(!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ showErr('err_email','fd_email'); ok=false; } else { clearErr('err_email','fd_email'); }
        if(!industry){ showErr('err_industry','fd_industry'); ok=false; } else { clearErr('err_industry','fd_industry'); }
        if(!strength){ document.getElementById('err_strength').style.display='block'; ok=false; } else { document.getElementById('err_strength').style.display='none'; }

        if(!ok) return;

        var btn = document.getElementById('fd-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> {{ __("Submitting...") }}';

        var fd = new FormData(document.getElementById('fd-form'));
        fetch('{{ route("free-demo.submit") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': fd.get('_token'), 'Accept': 'application/json' },
            body: fd
        }).then(function(r){ return r.json(); }).then(function(data){
            if(data.success){
                document.getElementById('fd-form').style.display = 'none';
                document.getElementById('fd-success').style.display = 'block';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-rocket"></i> {{ __("Request My Free Demo") }}';
                alert(data.message || '{{ __("Something went wrong. Please try again.") }}');
            }
        }).catch(function(){
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-rocket"></i> {{ __("Request My Free Demo") }}';
            alert('{{ __("Network error. Please try again.") }}');
        });
    });
})();
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>
