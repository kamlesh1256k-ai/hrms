@php
  $setting = \App\Models\Utility::colorset();
@endphp

<title>{{ config('chatify.name') }}</title>

{{-- Meta tags --}}
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="id" content="{{ $id }}">
<meta name="type" content="{{ $route }}">
<meta name="messenger-color" content="{{ $messengerColor }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="url" content="{{ url('') . '/' . config('chatify.routes.prefix') }}" data-user="{{ Auth::user()->id }}">

{{-- scripts --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/chatify/font.awesome.min.js') }}"></script>
<script src="{{ asset('js/chatify/autosize.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src='https://unpkg.com/nprogress@0.2.0/nprogress.js'></script>

{{-- styles --}}
<link rel='stylesheet' href='https://unpkg.com/nprogress@0.2.0/nprogress.css' />
<link href="{{ asset('css/chatify/style.css') }}?v={{ @filemtime(public_path('css/chatify/style.css')) }}" rel="stylesheet" />
<link href="{{ asset('css/chatify/' . $dark_mode . '.mode.css') }}?v={{ @filemtime(public_path('css/chatify/' . $dark_mode . '.mode.css')) }}" rel="stylesheet" />

{{-- dark mode issue --}}
{{-- <link href="{{ asset('css/app.css') }}" rel="stylesheet" /> --}}

@if ($setting['cust_darklayout'] == 'on')
  <link rel="stylesheet" href="{{ asset('css/chatify/dark.mode.css') }}?v={{ @filemtime(public_path('css/chatify/dark.mode.css')) }}">
@else
  <link rel="stylesheet" href="{{ asset('css/chatify/light.mode.css') }}?v={{ @filemtime(public_path('css/chatify/light.mode.css')) }}" id="main-style-link">
@endif

{{-- Messenger Color Style --}}
@include('Chatify::layouts.messengerColor')

{{-- ─── Custom HRMS Chat Polish ─────────────────────────────────────────── --}}
<style>
  /* Page background */
  body, .app, .messenger { background: #f1f5f9 !important; }

  /* Ensure messenger occupies full viewport without breaking columns */
  html, body { height: 100% !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important; }
  .messenger {
    width: 100% !important;
    height: 100vh !important;
    display: flex !important;
    flex-direction: row !important;
    position: relative !important;
  }

  /* Sidebar — fixed width, not stretched */
  .messenger-listView {
    background: #ffffff !important;
    border-right: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, .04) !important;
    width: 320px !important;
    flex: 0 0 320px !important;
    height: 100vh !important;
    overflow-y: auto !important;
  }

  /* Main messaging area — fill remaining space */
  .messenger-messagingView {
    flex: 1 1 auto !important;
    height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
    min-width: 0 !important;
  }

  /* Info panel — fixed width if shown */
  .messenger-infoView {
    width: 300px !important;
    flex: 0 0 300px !important;
    height: 100vh !important;
    overflow-y: auto !important;
  }
  .messenger-listView .m-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
    color: #ffffff !important;
    padding: 16px !important;
    border-bottom: none !important;
  }
  .messenger-listView .m-header h1,
  .messenger-listView .m-header * { color: #ffffff !important; }
  .messenger-listView .m-header input.messenger-search {
    background: rgba(255,255,255,.18) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255,255,255,.25) !important;
    border-radius: 10px !important;
    padding: 8px 12px !important;
  }
  .messenger-listView .m-header input.messenger-search::placeholder { color: rgba(255,255,255,.7) !important; }

  /* Conversation row hover/active only — keep Chatify's own layout */
  .messenger-list-item {
    transition: background .15s ease !important;
    cursor: pointer !important;
  }
  .messenger-list-item:hover { background: #f8fafc !important; }
  .messenger-list-item.contact-item-active { background: #eef2ff !important; border-left: 3px solid #6366f1 !important; }
  .messenger-list-item .avatar {
    background: linear-gradient(135deg, #cbd5e1, #94a3b8) !important;
    border: 2px solid #ffffff !important;
    box-shadow: 0 2px 4px rgba(15,23,42,.08) !important;
    border-radius: 50% !important;
  }

  /* Main messaging area */
  .messenger-messagingView { background: #ffffff !important; }
  .messenger-messagingView .m-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
    color: #fff !important;
    padding: 14px 18px !important;
    border-bottom: none !important;
    box-shadow: 0 2px 4px rgba(15,23,42,.06) !important;
  }
  .messenger-messagingView .m-header * { color: #fff !important; }
  .messenger-messagingView .m-header .messenger-headTitle { font-size: 1rem !important; font-weight: 700 !important; }
  .messenger-messagingView .m-header .messenger-headSubtitle { font-size: .72rem !important; opacity: .85 !important; }

  /* Chat bubbles */
  .messenger-messagingView .m-body { background: #f8fafc !important; padding: 20px !important; }
  .message-card {
    background: #ffffff !important;
    color: #1e293b !important;
    border-radius: 14px !important;
    padding: 10px 14px !important;
    box-shadow: 0 1px 3px rgba(15,23,42,.06) !important;
    border: 1px solid #e2e8f0 !important;
    max-width: 65% !important;
    min-width: 60px !important;
    width: auto !important;
    display: inline-block !important;
    font-size: .9rem !important;
    line-height: 1.45 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
  }
  .message-card.mc-sender {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
    color: #ffffff !important;
    border: none !important;
  }
  /* Force sender messages to right, receiver to left */
  .message-card.mc-sender,
  li.message-card.mc-sender {
    float: right !important;
    clear: both !important;
    margin-left: auto !important;
    margin-right: 0 !important;
  }
  .message-card.mc-receiver,
  li.message-card.mc-receiver {
    float: left !important;
    clear: both !important;
    margin-right: auto !important;
    margin-left: 0 !important;
  }
  /* Clear floats so consecutive bubbles stack vertically */
  .messenger-messagingView .m-body ul,
  .messenger-messagingView .m-body { overflow: hidden; }
  .message-card .message-time { font-size: .65rem !important; opacity: .7 !important; margin-top: 4px !important; display: block; text-align: right; }
  .message-card.mc-sender .message-time { color: rgba(255,255,255,.85) !important; }
  .message-card.mc-receiver .message-time { color: #94a3b8 !important; }

  /* "Please select a chat" placeholder */
  .messenger-messagingView .m-body p,
  .messenger-messagingView .m-body div:has(> p) {
    color: #64748b !important;
  }

  /* Send message area */
  .m-message-wrappter, .messenger-sendCard {
    background: #ffffff !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 10px 14px !important;
  }
  .messenger-sendCard textarea, .m-send {
    border-radius: 22px !important;
    border: 1px solid #e2e8f0 !important;
    padding: 10px 16px !important;
    font-size: .9rem !important;
    background: #f8fafc !important;
    resize: none !important;
  }
  .messenger-sendCard textarea:focus, .m-send:focus {
    outline: none !important;
    border-color: #6366f1 !important;
    background: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(13,148,136,.12) !important;
  }

  /* Send button */
  .messenger-sendCard .m-send-card-btn,
  .messenger-sendCard button[type=submit],
  .send-btn {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
    border-radius: 50% !important;
    width: 40px !important;
    height: 40px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 2px 6px rgba(13,148,136,.3) !important;
  }
  .messenger-sendCard .m-send-card-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(13,148,136,.4) !important; }
  .messenger-sendCard .m-send-card-btn i { color: #fff !important; }

  /* Info panel (right side) */
  .messenger-infoView {
    background: #ffffff !important;
    border-left: 1px solid #e2e8f0 !important;
  }
  .messenger-infoView .avatar {
    border: 4px solid #eef2ff !important;
    box-shadow: 0 4px 12px rgba(15,23,42,.08) !important;
  }

  /* Online dot */
  .avatar.av-s.av-active::after,
  .avatar.av-m.av-active::after,
  .avatar.av-l.av-active::after {
    background: #22c55e !important;
    border: 2px solid #ffffff !important;
    width: 12px !important;
    height: 12px !important;
  }

  /* ── Emoji picker button & panel ──────────────────────────────────── */
  #hrmsEmojiToggle {
    position: absolute;
    right: 60px;
    bottom: 18px;
    background: transparent;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: background .15s;
    z-index: 5;
  }
  #hrmsEmojiToggle:hover { background: #f1f5f9; }

  #hrmsEmojiPanel {
    position: absolute;
    right: 14px;
    bottom: 62px;
    width: 280px;
    max-height: 240px;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 6px 24px rgba(15, 23, 42, .12);
    padding: 10px;
    z-index: 9999;
    display: none;
  }
  #hrmsEmojiPanel.show { display: block; }
  #hrmsEmojiPanel .ep-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 2px;
  }
  #hrmsEmojiPanel .ep-emo {
    cursor: pointer;
    text-align: center;
    padding: 4px;
    font-size: 1.3rem;
    border-radius: 6px;
    background: transparent;
    border: none;
    transition: background .12s;
  }
  #hrmsEmojiPanel .ep-emo:hover { background: #eef2ff; }
  #hrmsEmojiPanel .ep-section {
    font-size: .7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 8px 4px 4px;
  }
  #hrmsEmojiPanel .ep-section:first-child { padding-top: 0; }

  /* Scrollbar polish */
  .messenger-listView::-webkit-scrollbar,
  .messenger-messagingView .m-body::-webkit-scrollbar,
  #hrmsEmojiPanel::-webkit-scrollbar { width: 8px; }
  .messenger-listView::-webkit-scrollbar-thumb,
  .messenger-messagingView .m-body::-webkit-scrollbar-thumb,
  #hrmsEmojiPanel::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 100px;
  }
  .messenger-listView::-webkit-scrollbar-thumb:hover,
  .messenger-messagingView .m-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

  /* Empty placeholder polish */
  .messenger-messagingView .m-body > div[style*="text-align"] {
    color: #94a3b8 !important;
  }
</style>

{{-- Inject emoji picker & wire up to message input --}}
<script>
(function () {
    var emojiSet = {
        'Smileys': ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬'],
        'Gestures': ['👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👋','🤚','🖐️','✋','🖖','👏','🙌','👐','🤲','🤝','🙏','✍️','💪','🦾','🙇','💁','🙋','🙆','🙅'],
        'Hearts': ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟'],
        'Objects': ['🔥','⭐','🎉','🎊','🎁','🏆','🎯','📌','📍','✅','❌','⚠️','📢','💡','💯','✨','⚡','💥','💫','🌟','☑️','📝','📅','📆','🕐','💼','💻','📱','☕','🍕']
    };

    function buildPanel() {
        if (document.getElementById('hrmsEmojiPanel')) return;
        var panel = document.createElement('div');
        panel.id = 'hrmsEmojiPanel';
        var html = '';
        for (var cat in emojiSet) {
            html += '<div class="ep-section">' + cat + '</div><div class="ep-grid">';
            emojiSet[cat].forEach(function (e) {
                html += '<button type="button" class="ep-emo" data-emo="' + e + '">' + e + '</button>';
            });
            html += '</div>';
        }
        panel.innerHTML = html;
        document.body.appendChild(panel);

        panel.addEventListener('click', function (ev) {
            var t = ev.target.closest('.ep-emo');
            if (!t) return;
            var emo = t.getAttribute('data-emo');
            var input = document.querySelector('.m-send, .messenger-sendCard textarea, textarea[name="message"]');
            if (input) {
                var start = input.selectionStart || input.value.length;
                var end = input.selectionEnd || input.value.length;
                input.value = input.value.substring(0, start) + emo + input.value.substring(end);
                input.focus();
                var pos = start + emo.length;
                try { input.setSelectionRange(pos, pos); } catch (e) {}
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    function ensureToggle() {
        var sendCard = document.querySelector('.messenger-sendCard, .m-message-wrappter');
        if (!sendCard) return false;
        if (document.getElementById('hrmsEmojiToggle')) return true;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'hrmsEmojiToggle';
        btn.title = 'Emoji';
        btn.innerHTML = '😊';
        sendCard.style.position = sendCard.style.position || 'relative';
        sendCard.appendChild(btn);

        btn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            buildPanel();
            var p = document.getElementById('hrmsEmojiPanel');
            // position panel relative to the send card
            var rect = sendCard.getBoundingClientRect();
            p.style.right = (window.innerWidth - rect.right + 14) + 'px';
            p.style.bottom = (window.innerHeight - rect.top + 6) + 'px';
            p.classList.toggle('show');
        });

        document.addEventListener('click', function (ev) {
            var p = document.getElementById('hrmsEmojiPanel');
            if (!p || !p.classList.contains('show')) return;
            if (p.contains(ev.target) || ev.target.id === 'hrmsEmojiToggle') return;
            p.classList.remove('show');
        });
        return true;
    }

    function init() {
        if (!ensureToggle()) {
            // Send card may render slightly later — retry briefly
            var tries = 0;
            var iv = setInterval(function () {
                if (ensureToggle() || ++tries > 20) clearInterval(iv);
            }, 250);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
