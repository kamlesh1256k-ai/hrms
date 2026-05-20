@extends('layouts.admin')
@section('page-title') {{ __('Agent Tokens') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('activity-tracker.index') }}">{{ __('Activity Tracker') }}</a></li>
    <li class="breadcrumb-item">{{ __('Tokens') }}</li>
@endsection

@push('css-page')
<style>
    /* Huge, impossible-to-miss token reveal modal-style banner */
    .plain-token-overlay {
        position: fixed; inset: 0;
        background: rgba(15, 23, 42, .82);
        backdrop-filter: blur(6px);
        z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
    }
    .plain-token-modal {
        background: #fff; border-radius: 16px;
        max-width: 720px; width: 100%;
        box-shadow: 0 30px 80px -20px rgba(0,0,0,.5);
        overflow: hidden;
    }
    .plain-token-modal .ptm-head {
        background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
        color: #fff; padding: 18px 24px;
        display: flex; align-items: center; gap: 12px;
    }
    .plain-token-modal .ptm-head i { font-size: 1.8rem; }
    .plain-token-modal .ptm-head h4 { margin: 0; font-weight: 700; }
    .plain-token-modal .ptm-body { padding: 22px 24px; }
    .plain-token-modal .ptm-warn {
        background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444;
        padding: 10px 14px; border-radius: 8px; margin-bottom: 14px;
        font-size: .86rem; color: #991b1b;
    }
    .plain-token-modal code {
        display: block;
        background: #0f172a; color: #4ade80;
        padding: 18px 20px; border-radius: 10px;
        font-size: 1.05rem; font-family: 'SF Mono', Consolas, Menlo, monospace;
        word-break: break-all; line-height: 1.5;
        border: 2px dashed #4ade80;
        user-select: all;
        cursor: text;
    }
    .plain-token-modal .ptm-actions {
        display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px;
    }
    .plain-token-modal .btn-copy-big {
        background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: 0;
        padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: .92rem;
        cursor: pointer;
    }
    .plain-token-modal .btn-confirm {
        background: #10b981; color: #fff; border: 0;
        padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: .92rem;
        cursor: pointer; margin-left: auto;
    }
    .plain-token-modal .btn-confirm:disabled { opacity: .5; cursor: not-allowed; }
    .copy-status { font-size: .82rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; }
    .copy-status.ok { background: #dcfce7; color: #166534; }
</style>
@endpush

@section('content')
    @if(session('success') && !session('plain_token'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- ── Token reveal modal — blocking overlay so user CANNOT miss it ── --}}
    @if(session('plain_token'))
        <div class="plain-token-overlay" id="tokenOverlay">
            <div class="plain-token-modal">
                <div class="ptm-head">
                    <i class="ti ti-key"></i>
                    <h4>{{ __('Your New Agent Token') }}</h4>
                </div>
                <div class="ptm-body">
                    <div class="ptm-warn">
                        <strong><i class="ti ti-alert-triangle"></i> {{ __('IMPORTANT — Copy this token now!') }}</strong><br>
                        {{ __('This is the ONLY time you will see the full token. After you close this dialog, it cannot be retrieved. If you lose it, you must generate a new one.') }}
                    </div>

                    <label style="font-size:.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">{{ __('Token (click to select all)') }}:</label>
                    <code id="plainTokenVal" onclick="this.select && this.select(); window.getSelection().selectAllChildren(this);">{{ session('plain_token') }}</code>

                    <div class="ptm-actions">
                        <button type="button" class="btn-copy-big" onclick="copyPlainToken()">
                            <i class="ti ti-copy"></i> {{ __('Copy to Clipboard') }}
                        </button>
                        <span class="copy-status ok" id="copyStatus" style="display:none;">
                            <i class="ti ti-check"></i> {{ __('Copied!') }}
                        </span>
                        <button type="button" class="btn-confirm" id="btnConfirm" disabled onclick="closeTokenModal()">
                            <i class="ti ti-check"></i> {{ __('I have saved the token — close') }}
                        </button>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="ti ti-info-circle"></i>
                        {{ __('You must click "Copy to Clipboard" before you can close this dialog.') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Create Agent Token') }}</h6></div>
                <div class="card-body">
                    <p class="small text-muted">{{ __('Generate a personal access token for the desktop agent. Paste it into the Electron agent\'s settings to authorise this device.') }}</p>
                    <form method="POST" action="{{ route('activity-tracker.token.create') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label small">{{ __('Token Name') }}</label>
                            <input type="text" name="name" class="form-control" required maxlength="80" placeholder="{{ __('e.g. Office Laptop') }}">
                        </div>
                        <button class="btn btn-primary btn-sm w-100"><i class="ti ti-key me-1"></i>{{ __('Generate Token') }}</button>
                    </form>

                    <div class="alert alert-info mt-3 small mb-0" style="font-size:.8rem;">
                        <i class="ti ti-info-circle"></i>
                        <strong>{{ __('Tip:') }}</strong>
                        {{ __('Keep a Notepad open before clicking Generate. Paste the token there so you have a backup while you set up the agent.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-list me-1"></i>{{ __('Existing Tokens') }}</h6></div>
                <div class="card-body p-0">
                    @if($tokens->isEmpty())
                        <div class="text-muted small p-3">{{ __('No tokens yet.') }}</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($tokens as $t)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="small">{{ $t->name }}</strong>
                                        <div class="text-muted" style="font-size:.7rem;">
                                            {{ __('Created') }} {{ $t->created_at->diffForHumans() }}
                                            @if($t->last_used_at) · {{ __('Last used') }} {{ $t->last_used_at->diffForHumans() }} @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('activity-tracker.token.revoke', $t->id) }}" onsubmit="return confirm('{{ __('Revoke this token? The agent using it will be signed out.') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
function copyPlainToken() {
    const val = document.getElementById('plainTokenVal').textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(val).then(() => {
            document.getElementById('copyStatus').style.display = 'inline-block';
            document.getElementById('btnConfirm').disabled = false;
        });
    } else {
        // Fallback: select-and-execCommand
        const range = document.createRange();
        range.selectNodeContents(document.getElementById('plainTokenVal'));
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        try { document.execCommand('copy'); } catch (e) {}
        document.getElementById('copyStatus').style.display = 'inline-block';
        document.getElementById('btnConfirm').disabled = false;
    }
}
function closeTokenModal() {
    document.getElementById('tokenOverlay').style.display = 'none';
}
// Block accidental refresh-leaving while modal is open
window.addEventListener('beforeunload', function (e) {
    const overlay = document.getElementById('tokenOverlay');
    if (overlay && overlay.style.display !== 'none') {
        e.preventDefault();
        e.returnValue = 'Your token will be lost if you leave! Have you copied it?';
        return e.returnValue;
    }
});
</script>
@endpush
