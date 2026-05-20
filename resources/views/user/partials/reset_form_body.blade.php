@php
    $isStandalone = $standalone ?? false;
    $displayName  = $user->name  ?? '';
    $displayEmail = $user->email ?? '';
@endphp

@if ($isStandalone)
    <div class="rp-hero">
        <div class="rp-hero-icon"><i class="ti ti-key"></i></div>
        <h4>{{ __('Reset Password') }}</h4>
        <p>{{ __('Set a new password for this user account.') }}</p>
        @if ($displayName)
            <span class="rp-user-chip">
                <i class="ti ti-user-circle"></i>
                <strong>{{ $displayName }}</strong>
                @if ($displayEmail) · <span style="opacity:.85;">{{ $displayEmail }}</span> @endif
            </span>
        @endif
    </div>
    <div class="rp-body">
@endif

{{ Form::model($user, ['route' => ['user.password.update', $user->id], 'method' => 'post', 'class' => 'needs-validation rp-form', 'id' => 'rpForm', 'novalidate']) }}

@if (!$isStandalone)
    <div class="modal-body">
@endif

    <div class="rp-field">
        <label for="rpPassword" class="rp-label">{{ __('New Password') }} <span class="text-danger">*</span></label>
        <div class="rp-input-wrap">
            <input id="rpPassword" type="password" name="password" required autocomplete="new-password"
                   class="form-control rp-input @error('password') is-invalid @enderror"
                   placeholder="{{ __('Enter new password') }}" minlength="8">
            <button type="button" class="rp-toggle" onclick="rpToggle('rpPassword', this)" tabindex="-1">
                <i class="ti ti-eye"></i>
            </button>
        </div>
        @error('password')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror

        <div class="rp-strength" id="rpStrengthBars">
            <div class="rp-strength-bar"></div>
            <div class="rp-strength-bar"></div>
            <div class="rp-strength-bar"></div>
            <div class="rp-strength-bar"></div>
        </div>
        <div class="rp-strength-label" id="rpStrengthLabel">{{ __('Password strength') }}</div>
    </div>

    @if ($isStandalone)
        <div class="rp-rules" id="rpRules">
            <div class="rp-rules-title">{{ __('Password Requirements') }}</div>
            <div class="rp-rule" data-rule="length"><i class="ti ti-circle"></i> {{ __('At least 8 characters') }}</div>
            <div class="rp-rule" data-rule="upper"><i class="ti ti-circle"></i> {{ __('One uppercase letter (A-Z)') }}</div>
            <div class="rp-rule" data-rule="number"><i class="ti ti-circle"></i> {{ __('One number (0-9)') }}</div>
            <div class="rp-rule" data-rule="special"><i class="ti ti-circle"></i> {{ __('One special character (!@#$ etc.)') }}</div>
        </div>
    @endif

    <div class="rp-field">
        <label for="rpPasswordConfirm" class="rp-label">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
        <div class="rp-input-wrap">
            <input id="rpPasswordConfirm" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="form-control rp-input" placeholder="{{ __('Re-enter new password') }}">
            <button type="button" class="rp-toggle" onclick="rpToggle('rpPasswordConfirm', this)" tabindex="-1">
                <i class="ti ti-eye"></i>
            </button>
        </div>
        <div class="rp-match" id="rpMatch" style="display:none;"></div>
    </div>

@if (!$isStandalone)
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update Password') }}" class="btn btn-primary" id="rpSubmit">
    </div>
@else
    <div class="rp-actions">
        <a href="{{ url()->previous() }}" class="rp-btn rp-btn-secondary">
            <i class="ti ti-arrow-left"></i> {{ __('Cancel') }}
        </a>
        <button type="submit" class="rp-btn rp-btn-primary" id="rpSubmit">
            <i class="ti ti-check"></i> {{ __('Update Password') }}
        </button>
    </div>
@endif

{{ Form::close() }}

@if ($isStandalone)
    </div>
@endif

<script>
(function () {
    const pwd  = document.getElementById('rpPassword');
    const conf = document.getElementById('rpPasswordConfirm');
    const bars = document.querySelectorAll('#rpStrengthBars .rp-strength-bar');
    const lbl  = document.getElementById('rpStrengthLabel');
    const match = document.getElementById('rpMatch');
    const rules = document.querySelectorAll('#rpRules .rp-rule');
    if (!pwd) return;

    function scorePassword(p) {
        let s = 0;
        if (!p) return 0;
        if (p.length >= 8)  s++;
        if (/[A-Z]/.test(p)) s++;
        if (/[0-9]/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return s;
    }

    function updateRules(p) {
        const checks = {
            length:  p.length >= 8,
            upper:   /[A-Z]/.test(p),
            number:  /[0-9]/.test(p),
            special: /[^A-Za-z0-9]/.test(p),
        };
        rules.forEach(r => {
            const key = r.getAttribute('data-rule');
            const icon = r.querySelector('i');
            if (checks[key]) {
                r.classList.add('met');
                icon.className = 'ti ti-circle-check';
            } else {
                r.classList.remove('met');
                icon.className = 'ti ti-circle';
            }
        });
    }

    function refresh() {
        const p = pwd.value;
        const s = scorePassword(p);
        const colors = ['', 'active-weak', 'active-weak', 'active-medium', 'active-strong'];
        const labels = ['Password strength', 'Weak password', 'Weak password', 'Medium strength', 'Strong password'];
        bars.forEach((b, i) => {
            b.className = 'rp-strength-bar';
            if (i < s) b.classList.add(colors[s]);
        });
        if (lbl) lbl.textContent = labels[s];
        updateRules(p);
        checkMatch();
    }

    function checkMatch() {
        if (!match) return;
        if (!conf.value) { match.style.display = 'none'; return; }
        match.style.display = 'flex';
        if (pwd.value === conf.value) {
            match.className = 'rp-match match';
            match.innerHTML = '<i class="ti ti-check"></i> Passwords match';
        } else {
            match.className = 'rp-match mismatch';
            match.innerHTML = '<i class="ti ti-x"></i> Passwords do not match';
        }
    }

    pwd.addEventListener('input', refresh);
    conf.addEventListener('input', checkMatch);
})();

function rpToggle(id, btn) {
    const inp = document.getElementById(id);
    const ico = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'ti ti-eye-off';
    } else {
        inp.type = 'password';
        ico.className = 'ti ti-eye';
    }
}
</script>
