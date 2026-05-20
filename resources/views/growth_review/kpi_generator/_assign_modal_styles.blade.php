{{-- Shared styles + JS for the Assign Modal (used by index + show pages) --}}
@push('css-page')
<style>
    .assign-modal{border:none;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.18);}

    .assign-header{
        background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);
        color:#fff;padding:22px 24px;display:flex;align-items:flex-start;gap:14px;position:relative;
    }
    .assign-header-icon{
        width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.2);
        display:flex;align-items:center;justify-content:center;flex-shrink:0;
        backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);
    }
    .assign-header-icon i{font-size:1.35rem;}
    .assign-header-text{flex:1;min-width:0;}
    .assign-header-text h5{margin:0 0 4px;font-weight:700;font-size:1.05rem;letter-spacing:-.2px;color:#fff;}
    .assign-subtitle{font-size:.78rem;opacity:.92;}
    .assign-subtitle strong{font-weight:600;}
    .assign-header .btn-close{margin-top:2px;opacity:.85;}
    .assign-header .btn-close:hover{opacity:1;}

    .assign-toolbar{
        padding:16px 20px 12px;background:#f8fafc;border-bottom:1px solid #e5e7eb;
        display:flex;flex-direction:column;gap:10px;
    }
    .assign-search-wrap{position:relative;}
    .assign-search-icon{
        position:absolute;left:14px;top:50%;transform:translateY(-50%);
        color:#94a3b8;font-size:1rem;pointer-events:none;
    }
    .assign-search{
        padding-left:40px;height:42px;border-radius:10px;border:1.5px solid #e2e8f0;
        background:#fff;font-size:.88rem;transition:all .15s;
    }
    .assign-search:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .assign-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .assign-select-all{
        display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;
        font-size:.78rem;color:#475569;font-weight:600;
    }
    .assign-select-all input{width:16px;height:16px;cursor:pointer;accent-color:#8b5cf6;}
    .assign-count-badge{
        background:#ede9fe;color:#6d28d9;padding:5px 12px;border-radius:20px;
        font-size:.72rem;font-weight:700;border:1px solid #ddd6fe;
    }

    .assign-list{
        max-height:340px;overflow-y:auto;padding:8px 0;
        scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;
    }
    .assign-list::-webkit-scrollbar{width:6px;}
    .assign-list::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px;}

    .assign-item{
        display:flex;align-items:center;gap:12px;padding:10px 20px;margin:0;
        cursor:pointer;transition:background .12s;border-left:3px solid transparent;
    }
    .assign-item:hover{background:#f8fafc;}
    .assign-item input[type=checkbox]{display:none;}
    .assign-item.has-check{background:#faf5ff;border-left-color:#8b5cf6;}
    .assign-item.has-check .assign-avatar{background:linear-gradient(135deg,#8b5cf6,#a855f7);color:#fff;}
    .assign-item.has-check .assign-check-indicator{
        background:#8b5cf6;color:#fff;transform:scale(1);opacity:1;
    }
    .assign-item-disabled{opacity:.55;cursor:not-allowed;background:#f1f5f9;}
    .assign-item-disabled:hover{background:#f1f5f9;}

    .assign-avatar{
        width:38px;height:38px;border-radius:50%;background:#e2e8f0;color:#64748b;
        display:flex;align-items:center;justify-content:center;
        font-weight:700;font-size:.92rem;flex-shrink:0;transition:all .15s;
    }
    .assign-info{flex:1;min-width:0;}
    .assign-name{font-weight:600;color:#1f2a44;font-size:.88rem;line-height:1.2;}
    .assign-meta{font-size:.72rem;color:#94a3b8;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
    .assign-code{font-family:ui-monospace,monospace;}
    .assign-status-pill{
        background:#dcfce7;color:#166534;padding:1px 8px;border-radius:10px;
        font-size:.68rem;font-weight:600;display:inline-flex;align-items:center;gap:3px;
    }
    .assign-check-indicator{
        width:22px;height:22px;border-radius:50%;background:#e2e8f0;color:transparent;
        display:flex;align-items:center;justify-content:center;flex-shrink:0;
        font-size:.78rem;transform:scale(.85);opacity:.5;transition:all .15s;
    }

    .assign-empty-search{text-align:center;padding:40px 20px;color:#94a3b8;}
    .assign-empty-search i{font-size:2.2rem;opacity:.4;}
    .assign-empty-search p{margin:10px 0 0;font-size:.85rem;}

    .assign-remarks{padding:16px 20px;border-top:1px solid #e5e7eb;background:#fafbfc;}
    .assign-remarks-label{
        font-size:.78rem;font-weight:600;color:#475569;margin-bottom:6px;display:block;
    }
    .assign-remarks-label .text-muted{font-weight:400;font-size:.72rem;}
    .assign-remarks-input{border-radius:10px;border:1.5px solid #e2e8f0;resize:none;font-size:.85rem;}
    .assign-remarks-input:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12);}

    .assign-footer{
        padding:16px 20px;border-top:1px solid #e5e7eb;background:#fff;
        display:flex;justify-content:flex-end;gap:10px;
    }
    .btn-assign-primary{
        background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;
        padding:9px 22px;border-radius:10px;font-weight:600;font-size:.86rem;
        display:inline-flex;align-items:center;transition:all .15s;
    }
    .btn-assign-primary:hover:not(:disabled){
        background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;
        transform:translateY(-1px);box-shadow:0 6px 16px rgba(99,102,241,.35);
    }
    .btn-assign-primary:disabled{background:#cbd5e1;color:#94a3b8;cursor:not-allowed;}
    .assign-footer .btn-light{border-radius:10px;padding:9px 18px;font-size:.86rem;font-weight:600;}
</style>
@endpush

@push('script-page')
<script>
/**
 * Wire up any .assign-modal element: search, select-all, live count, submit
 * button enable/disable. Called for every assign modal on the page.
 */
(function(){
    function wire(modal){
        if (!modal || modal.dataset.wired === '1') return;
        modal.dataset.wired = '1';

        var searchInput = modal.querySelector('.assign-search');
        var selectAllCb = modal.querySelector('.assign-select-all-cb');
        var countBadge  = modal.querySelector('.assign-count-badge');
        var submitBtn   = modal.querySelector('.btn-assign-primary');
        var submitCount = modal.querySelector('.assign-submit-count');
        var emptyMsg    = modal.querySelector('.assign-empty-search');
        var items       = modal.querySelectorAll('.assign-item');

        function updateCount(){
            var checked = modal.querySelectorAll('.assign-checkbox:checked').length;
            if (countBadge)  countBadge.textContent = checked + ' selected';
            if (submitCount) submitCount.textContent = checked;
            if (submitBtn)   submitBtn.disabled = (checked === 0);
            items.forEach(function(it){
                var cb = it.querySelector('.assign-checkbox');
                if (cb && cb.checked) it.classList.add('has-check');
                else it.classList.remove('has-check');
            });
        }

        items.forEach(function(it){
            it.addEventListener('click', function(e){
                if (it.classList.contains('assign-item-disabled')) { e.preventDefault(); return; }
                setTimeout(updateCount, 0);
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function(){
                var q = this.value.trim().toLowerCase();
                var visible = 0;
                items.forEach(function(it){
                    var name = it.dataset.name || '';
                    var code = it.dataset.code || '';
                    var match = (q === '' || name.indexOf(q) !== -1 || code.indexOf(q) !== -1);
                    it.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (emptyMsg) emptyMsg.classList.toggle('d-none', visible > 0);
            });
        }

        if (selectAllCb) {
            selectAllCb.addEventListener('change', function(){
                items.forEach(function(it){
                    if (it.style.display === 'none') return;
                    if (it.classList.contains('assign-item-disabled')) return;
                    var cb = it.querySelector('.assign-checkbox');
                    if (cb) cb.checked = selectAllCb.checked;
                });
                updateCount();
            });
        }

        modal.addEventListener('show.bs.modal', function(){
            if (searchInput) searchInput.value = '';
            if (selectAllCb) selectAllCb.checked = false;
            modal.querySelectorAll('.assign-checkbox').forEach(function(cb){
                if (!cb.disabled) cb.checked = false;
            });
            items.forEach(function(it){ it.style.display = ''; });
            if (emptyMsg) emptyMsg.classList.add('d-none');
            updateCount();
        });

        updateCount();
    }

    document.querySelectorAll('.assign-modal-root').forEach(wire);
})();
</script>
@endpush
