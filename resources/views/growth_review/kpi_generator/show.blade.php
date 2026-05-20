@extends('layouts.admin')
@section('page-title') {{ __('Generated KRA / KPI') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.kpi-generator.index') }}">{{ __('KRA / KPI Generator') }}</a></li>
    <li class="breadcrumb-item">#{{ $gen->id }}</li>
@endsection
@section('action-button')
    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="ti ti-user-plus me-1"></i>{{ __('Assign') }}</button>
    <a href="{{ route('growth-review.kpi-generator.pdf', $gen->id) }}" class="btn btn-sm btn-success me-1"><i class="ti ti-file-type-pdf me-1"></i>{{ __('Download PDF') }}</a>
    <a href="{{ route('growth-review.kpi-generator.index') }}" class="btn btn-sm btn-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
@endsection

@push('css-page')
<style>
    .kg-meta{display:flex;flex-wrap:wrap;gap:18px;padding:18px 22px;background:#f8fafc;border-radius:14px;margin-bottom:22px;}
    .kg-meta .m-item{font-size:.82rem;}
    .kg-meta .m-item label{display:block;color:#64748b;font-weight:600;margin-bottom:2px;font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;}
    .kg-meta .m-item span{color:#1f2a44;font-weight:600;}
    .kra-card{border:1px solid #e2e5ec;border-radius:14px;margin-bottom:18px;overflow:hidden;}
    .kra-head{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);color:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;}
    .kra-head h6{margin:0;font-weight:700;font-size:1rem;}
    .kra-weight{background:rgba(255,255,255,.22);padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700;}
    .kra-body{padding:18px 22px;}
    .kra-desc{color:#64748b;font-size:.88rem;margin-bottom:14px;}
    .kpi-table{width:100%;font-size:.85rem;}
    .kpi-table th{background:#f1f5f9;color:#475569;font-weight:600;padding:8px 12px;text-align:left;border:1px solid #e2e5ec;}
    .kpi-table td{padding:10px 12px;border:1px solid #e2e5ec;color:#1f2a44;}
    .kpi-table td strong{color:#6d28d9;}

    /* ── Universal inline editor (target / metric / frequency / KRA title / desc / weightage) ── */
    .edit-field{display:inline-flex;align-items:center;gap:6px;min-height:26px;position:relative;}
    .edit-field .edit-value{flex:1;word-break:break-word;}
    .edit-btn-inline{
        background:transparent;border:1px solid transparent;border-radius:5px;
        color:#94a3b8;padding:2px 5px;font-size:.78rem;cursor:pointer;
        transition:all .12s;opacity:0;line-height:1;
    }
    .edit-field:hover .edit-btn-inline{opacity:1;}
    .edit-btn-inline:hover{background:#eef2ff;color:#6366f1;border-color:#e0e7ff;}
    .edit-field.is-editing .edit-value{display:none;}
    .edit-field.is-editing .edit-btn-inline{display:none;}
    .edit-field .edit-input{
        flex:1;min-width:120px;border:1.5px solid #8b5cf6;border-radius:6px;padding:4px 8px;
        font-size:inherit;font-weight:inherit;color:inherit;outline:none;
        box-shadow:0 0 0 3px rgba(139,92,246,.12);background:#fff;
    }
    .edit-field .edit-save,.edit-field .edit-cancel{
        background:transparent;border:none;padding:3px 6px;cursor:pointer;border-radius:4px;font-size:.85rem;line-height:1;
    }
    .edit-field .edit-save{color:#16a34a;}
    .edit-field .edit-save:hover{background:#dcfce7;}
    .edit-field .edit-cancel{color:#dc2626;}
    .edit-field .edit-cancel:hover{background:#fee2e2;}
    .edit-field.is-saving{opacity:.55;pointer-events:none;}
    .edit-field.flash-success .edit-value{
        background:#dcfce7;padding:1px 6px;border-radius:4px;transition:background .6s;
    }

    /* KRA header edit styling — inherit white colour inside red gradient bar */
    .kra-head .edit-field{color:#fff;}
    .kra-head .edit-value{color:#fff;}
    .kra-head .edit-btn-inline{color:rgba(255,255,255,.75);}
    .kra-head .edit-btn-inline:hover{background:rgba(255,255,255,.18);color:#fff;border-color:transparent;}
    .kra-head .edit-input{color:#1f2a44;font-weight:700;}
    .kra-weight{display:inline-flex;align-items:center;gap:4px;}

    /* Description styling (appears above KPI table) */
    .kra-desc{margin-bottom:14px;}
    .kra-desc .edit-value{color:#64748b;font-size:.88rem;}

    /* Target highlight */
    .target-highlight .edit-value strong{color:#6d28d9;}

    /* Row action buttons */
    .btn-kpi-delete,.btn-kra-delete{
        background:transparent;border:1px solid transparent;border-radius:6px;
        color:#cbd5e1;padding:4px 7px;cursor:pointer;transition:all .12s;font-size:.85rem;line-height:1;
    }
    .btn-kpi-delete:hover{background:#fee2e2;color:#dc2626;border-color:#fecaca;}
    .btn-kra-delete{color:rgba(255,255,255,.8);}
    .btn-kra-delete:hover{background:rgba(255,255,255,.22);color:#fff;}

    /* Add buttons */
    .btn-add-kpi{font-size:.8rem;padding:5px 14px;border-radius:8px;}
    .btn-add-kra{padding:10px 26px;border-radius:10px;font-weight:600;
        background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;box-shadow:0 4px 14px rgba(139,92,246,.25);}
    .btn-add-kra:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);transform:translateY(-1px);box-shadow:0 6px 18px rgba(139,92,246,.35);}

    /* New KRA fade-in */
    @keyframes kraFadeIn{from{opacity:0;transform:translateY(-6px);}to{opacity:1;transform:none;}}
    .kra-card.kra-new{animation:kraFadeIn .35s ease;}

    /* ── Numeric rating pills (1-10) ──────────────────────────── */
    .rate-nums{display:inline-flex;gap:4px;align-items:center;flex-wrap:wrap;}
    .rate-nums .num{
        cursor:pointer;min-width:28px;height:28px;padding:0 6px;
        border:1.5px solid #e2e5ec;background:#fff;color:#64748b;
        border-radius:7px;font-size:.82rem;font-weight:600;line-height:1;
        display:inline-flex;align-items:center;justify-content:center;
        transition:all .12s;user-select:none;
    }
    .rate-nums .num:hover{border-color:#8b5cf6;color:#6d28d9;background:#faf5ff;}
    .rate-nums .num.on{
        background:linear-gradient(135deg,#6366f1,#8b5cf6);
        border-color:transparent;color:#fff;
        box-shadow:0 2px 6px rgba(139,92,246,.3);
    }
    .rate-score{font-size:.78rem;color:#64748b;margin-left:8px;font-weight:600;}
    .rate-score.has-val{color:#6d28d9;}

    /* KRA-level big rating row */
    .kra-rating-row{
        display:flex;align-items:center;gap:14px;flex-wrap:wrap;
        padding:10px 14px;background:#faf5ff;border:1px dashed #d8b4fe;
        border-radius:10px;margin-bottom:14px;
    }
    .kra-rating-row .label{font-size:.82rem;font-weight:600;color:#6d28d9;}
    .kra-rating-row .rate-nums .num{min-width:34px;height:32px;font-size:.88rem;}
    .kra-rating-row .remarks-input{
        flex:1;min-width:200px;border:1px solid #e9d5ff;border-radius:8px;
        padding:6px 10px;font-size:.82rem;background:#fff;
    }
    .kra-rating-row .remarks-input:focus{border-color:#8b5cf6;outline:none;box-shadow:0 0 0 3px rgba(139,92,246,.12);}

    /* KPI rating column */
    .kpi-table th.rate-col,.kpi-table td.rate-col{text-align:center;white-space:nowrap;}
    .kpi-table td.rate-col .rate-nums{justify-content:center;}

    /* Manual rating input (replaces pills in KPI table) */
    .rate-input-wrap{
        display:inline-flex;align-items:center;gap:4px;
    }
    .rate-input{
        width:54px;height:34px;text-align:center;font-size:.95rem;font-weight:700;
        border:1.5px solid #e2e5ec;border-radius:8px;background:#fff;color:#1f2a44;
        outline:none;transition:all .12s;padding:0 4px;
    }
    .rate-input:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .rate-input.saved{border-color:#10b981;background:#ecfdf5;}
    .rate-input.invalid{border-color:#ef4444;background:#fef2f2;}
    .rate-input.mgr-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.15);}
    .rate-input.has-val{border-color:#8b5cf6;background:linear-gradient(135deg,#ede9fe,#faf5ff);color:#6d28d9;}
    .rate-input.mgr-input.has-val{border-color:#f59e0b;background:linear-gradient(135deg,#fef3c7,#fffbeb);color:#92400e;}
    .rate-suffix{font-size:.78rem;color:#94a3b8;font-weight:500;}
    /* Hide browser spinner clutter */
    .rate-input::-webkit-outer-spin-button,
    .rate-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0;}
    .rate-input[type=number]{-moz-appearance:textfield;}

    /* KPI Evidence (Remarks + Document) column */
    .kpi-table td.evidence-col{padding:8px 10px;}
    .evidence-wrap{display:flex;flex-direction:column;gap:6px;}
    .evidence-remarks{
        width:100%;border:1px solid #e2e5ec;border-radius:7px;padding:6px 10px;
        font-size:.78rem;background:#fff;color:#1f2a44;
    }
    .evidence-remarks:focus{border-color:#8b5cf6;outline:none;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .evidence-remarks.saved{border-color:#10b981;}
    .evidence-actions{display:flex;align-items:center;gap:6px;}
    .ev-upload-btn{
        display:inline-flex;align-items:center;justify-content:center;
        width:30px;height:30px;border-radius:7px;cursor:pointer;
        background:#faf5ff;color:#6d28d9;border:1px solid #e9d5ff;
        transition:all .12s;margin:0;
    }
    .ev-upload-btn:hover{background:#ede9fe;border-color:#8b5cf6;transform:translateY(-1px);}
    .ev-doc-chip{
        display:inline-flex;align-items:center;gap:4px;padding:4px 9px;
        background:linear-gradient(135deg,#ede9fe,#faf5ff);color:#6d28d9;
        border:1px solid #d8b4fe;border-radius:7px;font-size:.74rem;font-weight:600;
        text-decoration:none;max-width:130px;
    }
    .ev-doc-chip:hover{background:#ddd6fe;color:#5b21b6;}
    .ev-doc-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .ev-doc-remove{
        background:transparent;border:1px solid transparent;padding:3px 5px;
        color:#94a3b8;cursor:pointer;border-radius:6px;line-height:1;
    }
    .ev-doc-remove:hover{background:#fee2e2;color:#dc2626;border-color:#fecaca;}
    .evidence-col .btn-kpi-delete{margin-left:auto;}
    .ev-uploading{opacity:.6;pointer-events:none;}

    /* KRA average badge (auto-computed, read-only) */
    .kra-avg-badge{
        display:inline-flex;align-items:baseline;gap:6px;padding:4px 14px;
        background:#fff;border:1.5px solid #e9d5ff;border-radius:8px;
        color:#94a3b8;font-weight:700;font-size:1.05rem;justify-content:center;
    }
    .kra-avg-badge small{font-size:.72rem;color:#94a3b8;font-weight:500;}
    .kra-avg-badge .avg-formula{font-size:.9rem;font-weight:600;color:#64748b;}
    .kra-avg-badge .avg-eq{color:#cbd5e1;font-weight:400;margin:0 2px;}
    .kra-avg-badge.has-val{border-color:#8b5cf6;background:linear-gradient(135deg,#ede9fe,#faf5ff);color:#6d28d9;}
    .kra-avg-badge.has-val small{color:#8b5cf6;}
    .kra-avg-badge.has-val .avg-formula{color:#8b5cf6;}
    .kra-weight-chip{color:#64748b;font-size:.8rem;font-weight:500;}
    .kra-weighted-score{color:#94a3b8;font-size:.88rem;font-weight:500;}
    .kra-weighted-score.has-val{color:#6d28d9;}
    .kra-weighted-score strong{font-size:1.05rem;font-weight:700;}

    /* Overall total card */
    .overall-total-card{
        margin-top:22px;padding:22px 28px;border-radius:16px;
        background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);
        color:#fff;text-align:center;box-shadow:0 8px 24px rgba(139,92,246,.28);
    }
    .overall-total-card .ot-label{font-size:.88rem;font-weight:600;opacity:.9;letter-spacing:.4px;text-transform:uppercase;}
    .overall-total-card .ot-value{font-size:2.6rem;font-weight:800;margin:6px 0 2px;letter-spacing:-1px;}
    .overall-total-card .ot-value small{font-size:1.1rem;opacity:.75;font-weight:500;}
    .overall-total-card .ot-hint{font-size:.76rem;opacity:.8;}
    .totals-wrap{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:22px;}
    .totals-wrap > .overall-total-card{margin-top:0;}
    @media(max-width:800px){.totals-wrap{grid-template-columns:1fr;}}
    .mgr-total-card{background:linear-gradient(135deg,#f59e0b 0%,#f97316 50%,#ef4444 100%);box-shadow:0 8px 24px rgba(249,115,22,.28);}

    /* Save / Submit action bar */
    .kg-save-bar{
        display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;
        margin-top:24px;padding:18px 22px;background:#f8fafc;border-radius:14px;border:1px solid #e2e5ec;
    }
    .kg-status-pill{
        display:inline-flex;align-items:center;gap:6px;padding:6px 14px;
        border-radius:20px;font-size:.82rem;font-weight:600;
    }
    .kg-status-draft{background:#fef3c7;color:#92400e;}
    .kg-status-submitted{background:#d1fae5;color:#065f46;}
    .kg-save-btn{
        background:#fff;color:#6d28d9;border:1.5px solid #d8b4fe;
        padding:9px 24px;border-radius:10px;font-weight:600;font-size:.88rem;
    }
    .kg-save-btn:hover{background:#faf5ff;color:#6d28d9;border-color:#8b5cf6;}
    .kg-submit-btn{
        background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;
        padding:9px 26px;border-radius:10px;font-weight:600;font-size:.88rem;
        box-shadow:0 4px 14px rgba(139,92,246,.28);
    }
    .kg-submit-btn:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;transform:translateY(-1px);}
    .kg-reopen-btn{padding:9px 22px;border-radius:10px;font-weight:600;font-size:.88rem;}

    /* Fully locked — HOD finalised (end of workflow) */
    .kg-locked .edit-btn-inline,
    .kg-locked .rate-input,
    .kg-locked .evidence-remarks,
    .kg-locked .ev-upload-btn,
    .kg-locked .ev-doc-remove,
    .kg-locked .kra-remarks-input,
    .kg-locked .kra-mgr-remarks-input,
    .kg-locked .mgr-remarks,
    .kg-locked .kra-hod-remarks-input,
    .kg-locked .hod-remarks,
    .kg-locked .btn-kra-delete,
    .kg-locked .btn-kpi-delete{
        pointer-events:none !important;opacity:.55 !important;cursor:not-allowed !important;
    }
    .kg-locked .edit-btn-inline{display:none !important;}

    /* Employee phase done, manager phase open — lock employee fields but
       leave manager_rating / manager_remarks / manager_overall_remarks open */
    .kg-employee-locked .edit-btn-inline,
    .kg-employee-locked .self-rate .rate-input,
    .kg-employee-locked .evidence-remarks:not(.mgr-remarks),
    .kg-employee-locked .ev-upload-btn,
    .kg-employee-locked .ev-doc-remove,
    .kg-employee-locked .kra-remarks-input:not(.kra-mgr-remarks-input),
    .kg-employee-locked .btn-kra-delete,
    .kg-employee-locked .btn-kpi-delete{
        pointer-events:none !important;opacity:.6 !important;cursor:not-allowed !important;
    }
    .kg-employee-locked .edit-btn-inline{display:none !important;}

    /* Manager column styling */
    .kpi-table th.mgr-col,.kpi-table td.mgr-col{
        background:#fef3c7;border-left:2px solid #f59e0b;
    }
    .kpi-table th.mgr-col{color:#92400e;}
    .rate-nums.mgr-rate .num.on{
        background:linear-gradient(135deg,#f59e0b,#f97316);box-shadow:0 2px 6px rgba(249,115,22,.3);
    }
    .rate-nums.mgr-rate .num:hover{border-color:#f97316;color:#c2410c;background:#fff7ed;}
    .kra-mgr-row{background:#fffbeb !important;border:1px dashed #fbbf24 !important;}
    .kra-mgr-row .label{color:#92400e !important;}
    .kra-mgr-badge.has-val{
        border-color:#f59e0b !important;background:linear-gradient(135deg,#fef3c7,#fffbeb) !important;color:#92400e !important;
    }
    .kra-mgr-score.has-val{color:#92400e !important;}
    .mgr-remarks{background:#fffbeb !important;border-color:#fcd34d !important;}
    .mgr-remarks:focus{border-color:#f59e0b !important;box-shadow:0 0 0 3px rgba(245,158,11,.15) !important;}
    .kg-status-finalised{background:#ddd6fe;color:#5b21b6;}
    .kg-status-mgr-done{background:#cffafe;color:#0e7490;}
    .kg-status-hod-done{background:#dcfce7;color:#166534;}

    /* Inline score chips inside the status pill */
    .kg-status-pill{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap;}
    .kg-score-chip{
        display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;
        font-weight:600;font-size:.78rem;background:#fff;border:1px solid rgba(0,0,0,.08);
    }
    .kg-score-chip strong{font-size:.9rem;}
    .kg-score-chip.chip-self{color:#5b21b6;border-color:#c4b5fd;background:#f5f3ff;}
    .kg-score-chip.chip-mgr{color:#92400e;border-color:#fcd34d;background:#fffbeb;}
    .kg-score-chip.chip-hod{color:#0e7490;border-color:#67e8f9;background:#ecfeff;}

    /* Manager phase done, HOD phase open — lock employee + manager fields,
       leave head_rating / head_remarks / head_overall_remarks open */
    .kg-manager-locked .edit-btn-inline,
    .kg-manager-locked .self-rate .rate-input,
    .kg-manager-locked .mgr-rate .rate-input,
    .kg-manager-locked .evidence-remarks:not(.hod-remarks),
    .kg-manager-locked .ev-upload-btn,
    .kg-manager-locked .ev-doc-remove,
    .kg-manager-locked .kra-remarks-input:not(.kra-hod-remarks-input),
    .kg-manager-locked .btn-kra-delete,
    .kg-manager-locked .btn-kpi-delete{
        pointer-events:none !important;opacity:.6 !important;cursor:not-allowed !important;
    }
    .kg-manager-locked .edit-btn-inline{display:none !important;}

    /* HOD column styling — teal/green theme to distinguish from manager amber */
    .kpi-table th.hod-col,.kpi-table td.hod-col{
        background:#ecfeff;border-left:2px solid #06b6d4;
    }
    .kpi-table th.hod-col{color:#0e7490;}
    .rate-nums.hod-rate .num.on{
        background:linear-gradient(135deg,#06b6d4,#0891b2);box-shadow:0 2px 6px rgba(6,182,212,.3);
    }
    .rate-nums.hod-rate .num:hover{border-color:#0891b2;color:#0e7490;background:#ecfeff;}
    .kra-hod-row{background:#ecfeff !important;border:1px dashed #67e8f9 !important;}
    .kra-hod-row .label{color:#0e7490 !important;}
    .kra-hod-badge.has-val{
        border-color:#06b6d4 !important;background:linear-gradient(135deg,#cffafe,#ecfeff) !important;color:#0e7490 !important;
    }
    .kra-hod-score.has-val{color:#0e7490 !important;}
    .hod-remarks{background:#ecfeff !important;border-color:#67e8f9 !important;}
    .hod-remarks:focus{border-color:#06b6d4 !important;box-shadow:0 0 0 3px rgba(6,182,212,.15) !important;}

    /* Copy Manager Rating button */
    .btn-copy-mgr-rating{
        background:#fff;color:#0e7490;border:1.5px solid #67e8f9;border-radius:8px;
        padding:3px 12px;font-size:.74rem;font-weight:600;cursor:pointer;
        transition:all .15s;white-space:nowrap;
    }
    .btn-copy-mgr-rating:hover{background:#ecfeff;border-color:#06b6d4;transform:translateY(-1px);box-shadow:0 2px 8px rgba(6,182,212,.2);}
    .btn-copy-mgr-rating.copied{background:#d1fae5;color:#065f46;border-color:#6ee7b7;}
    .btn-copy-all-mgr{
        background:#ecfeff;color:#0e7490;border:1.5px solid #67e8f9;
        padding:8px 20px;border-radius:10px;font-weight:600;font-size:.85rem;
    }
    .btn-copy-all-mgr:hover{background:#cffafe;border-color:#06b6d4;color:#0e7490;transform:translateY(-1px);}

    /* Role-based locks — each role can only edit their own section */

    /* Employee role: lock manager + HOD fields, allow employee fields */
    .kg-role-employee .mgr-rate .rate-input,
    .kg-role-employee .hod-rate .rate-input,
    .kg-role-employee .mgr-remarks,
    .kg-role-employee .hod-remarks,
    .kg-role-employee .kra-mgr-remarks-input,
    .kg-role-employee .kra-hod-remarks-input{
        pointer-events:none !important;opacity:.55 !important;cursor:not-allowed !important;
    }
    /* Employee after submit: everything locked (their fields too) */
    .kg-employee-locked.kg-role-employee .self-rate .rate-input,
    .kg-employee-locked.kg-role-employee .evidence-remarks,
    .kg-employee-locked.kg-role-employee .kra-remarks-input,
    .kg-employee-locked.kg-role-employee .edit-btn-inline,
    .kg-employee-locked.kg-role-employee .ev-upload-btn,
    .kg-employee-locked.kg-role-employee .ev-doc-remove,
    .kg-employee-locked.kg-role-employee .btn-kra-delete,
    .kg-employee-locked.kg-role-employee .btn-kpi-delete{
        pointer-events:none !important;opacity:.55 !important;cursor:not-allowed !important;
    }
    .kg-employee-locked.kg-role-employee .edit-btn-inline{display:none !important;}

    /* Manager role: lock employee + HOD fields, allow manager fields */
    .kg-role-manager .self-rate .rate-input,
    .kg-role-manager .hod-rate .rate-input,
    .kg-role-manager .evidence-remarks:not(.mgr-remarks),
    .kg-role-manager .hod-remarks,
    .kg-role-manager .kra-remarks-input:not(.kra-mgr-remarks-input),
    .kg-role-manager .kra-hod-remarks-input,
    .kg-role-manager .edit-btn-inline{
        pointer-events:none !important;opacity:.55 !important;cursor:not-allowed !important;
    }
    .kg-role-manager .edit-btn-inline{display:none !important;}

    /* HOD role: lock employee + manager fields, allow HOD fields */
    .kg-role-hod .self-rate .rate-input,
    .kg-role-hod .mgr-rate .rate-input,
    .kg-role-hod .evidence-remarks:not(.hod-remarks),
    .kg-role-hod .mgr-remarks,
    .kg-role-hod .kra-remarks-input:not(.kra-hod-remarks-input),
    .kg-role-hod .kra-mgr-remarks-input,
    .kg-role-hod .edit-btn-inline{
        pointer-events:none !important;opacity:.55 !important;cursor:not-allowed !important;
    }
    .kg-role-hod .edit-btn-inline{display:none !important;}

    .overall-total-card.hod-total-card{
        background:linear-gradient(135deg,#06b6d4,#0891b2);
    }
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Assigned employees card --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-users me-1"></i>{{ __('Assigned Employees') }}</h6>
            <span class="badge bg-primary">{{ count($assignments) }}</span>
        </div>
        <div class="card-body">
            @if(count($assignments) === 0)
                <div class="text-muted text-center py-3" style="font-size:.85rem;">
                    {{ __('No employees assigned yet.') }}
                    <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="ti ti-user-plus me-1"></i>{{ __('Assign Now') }}
                    </button>
                </div>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($assignments as $a)
                        <div class="d-flex align-items-center gap-2 border rounded px-3 py-1" style="background:#f8fafc;">
                            <i class="ti ti-user text-primary"></i>
                            <div>
                                <strong style="font-size:.85rem;">{{ $a->employee->name ?? 'Unknown' }}</strong>
                                <small class="text-muted ms-1">{{ $a->assigned_at?->diffForHumans() }}</small>
                            </div>
                            <form method="POST" action="{{ route('growth-review.kpi-generator.unassign', [$gen->id, $a->id]) }}" class="d-inline" onsubmit="return confirm('{{ __('Remove this assignment?') }}');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link p-0 text-danger" title="Unassign"><i class="ti ti-x"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
                @if($assignments->first()?->remarks)
                    <div class="mt-2 text-muted" style="font-size:.78rem;">
                        <i class="ti ti-message me-1"></i>{{ $assignments->first()->remarks }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @php
        $status             = $gen->status ?? 'draft';
        $isDraft            = $status === 'draft';
        $isSubmitted        = $status === 'submitted';
        $isManagerFinalised = $status === 'manager_reviewed';
        $isHodFinalised     = $status === 'hod_reviewed';
        // Legacy alias used throughout the view — kept true for any fully-locked state.
        $isFinalised        = $isHodFinalised;
        // Viewer role: 'employee', 'manager', 'hod', 'admin'
        $role = $viewerRole ?? 'admin';

        // For assigned employees: only show manager/HOD ratings AFTER they are submitted (not while being filled)
        $showManagerCol = ($role === 'employee')
            ? in_array($status, ['manager_reviewed', 'hod_reviewed'], true)
            : in_array($status, ['submitted', 'manager_reviewed', 'hod_reviewed'], true);
        $showHodCol = ($role === 'manager')
            ? in_array($status, ['hod_reviewed'], true)
            : in_array($status, ['manager_reviewed', 'hod_reviewed'], true);
        $employeeLocked     = !$isDraft;
        $managerLocked      = in_array($status, ['manager_reviewed', 'hod_reviewed'], true);
        $hodLocked          = $isHodFinalised;

        // Phase-based lock class
        $cardLockClass = $isHodFinalised
            ? 'kg-locked'
            : ($isManagerFinalised ? 'kg-manager-locked'
                : ($isSubmitted ? 'kg-employee-locked' : ''));

        // Role-based lock class — each role can only edit their own section
        $cardLockClass .= ' kg-role-' . $role;
    @endphp
    <div class="card {{ $cardLockClass }}">
        <div class="card-body">

            <div id="krasContainer">
            @forelse($kras as $idx => $kra)
            <div class="kra-card" data-kra-index="{{ $idx }}">
                <div class="kra-head">
                    <h6>
                        <span class="kra-label">KRA <span class="kra-num">{{ $idx + 1 }}</span>:</span>
                        <span class="edit-field" data-scope="kra" data-field="kra" data-kra="{{ $idx }}">
                            <span class="edit-value">{{ $kra['kra'] }}</span>
                            <button type="button" class="edit-btn-inline" title="{{ __('Edit KRA title') }}"><i class="ti ti-pencil"></i></button>
                        </span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="kra-weight edit-field" data-scope="kra" data-field="weightage" data-kra="{{ $idx }}">
                            <span class="edit-value">{{ $kra['weightage'] }}</span>%
                            <button type="button" class="edit-btn-inline" title="{{ __('Edit weightage (0-100)') }}"><i class="ti ti-pencil"></i></button>
                        </span>
                    </div>
                </div>
                <div class="kra-body">
                    <div class="kra-desc edit-field" data-scope="kra" data-field="description" data-kra="{{ $idx }}">
                        <span class="edit-value">{{ $kra['description'] ?: __('(No description — click to add)') }}</span>
                        <button type="button" class="edit-btn-inline" title="{{ __('Edit description') }}"><i class="ti ti-pencil"></i></button>
                    </div>

                    @php
                        $kraRemarks = $kra['rating_remarks'] ?? '';
                        $allKpis = $kra['kpis'] ?? [];
                        $sumRatings = array_sum(array_map(fn($k) => (int)($k['rating'] ?? 0), $allKpis));
                        $avgRating = count($allKpis) > 0 ? round($sumRatings / count($allKpis), 2) : 0;
                        $kraWeight = (int) ($kra['weightage'] ?? 0);
                        $weightedScore = round($avgRating * ($kraWeight / 100), 2);
                    @endphp
                    <div class="kra-rating-row" data-kra-summary="{{ $idx }}" data-weight="{{ $kraWeight }}">
                        <span class="label"><i class="ti ti-user me-1"></i>{{ __('Self Rating') }}:</span>
                        <span class="kra-avg-badge {{ $avgRating > 0 ? 'has-val' : '' }}">
                            <span class="avg-formula"><span class="avg-sum">{{ $sumRatings }}</span>/<span class="avg-count">{{ count($allKpis) }}</span></span>
                        </span>
                        <span class="kra-weight-chip">× <span class="weight-chip-val">{{ $kraWeight }}</span>% {{ __('weight') }}</span>
                        <span class="kra-weighted-score {{ $weightedScore > 0 ? 'has-val' : '' }}">
                            = <strong class="weighted-val">{{ $weightedScore }}</strong> {{ __('pts') }}
                        </span>
                        <input type="text" class="remarks-input kra-remarks-input" placeholder="{{ __('Add remarks…') }}" value="{{ $kraRemarks }}" data-kra="{{ $idx }}" maxlength="500">
                    </div>

                    @if($showManagerCol)
                        @php
                            $mgrSum   = array_sum(array_map(fn($k) => (int)($k['manager_rating'] ?? 0), $allKpis));
                            $mgrAvg   = count($allKpis) > 0 ? round($mgrSum / count($allKpis), 2) : 0;
                            $mgrScore = round($mgrAvg * ($kraWeight / 100), 2);
                            $mgrKraRemarks = $kra['manager_overall_remarks'] ?? '';
                        @endphp
                        <div class="kra-rating-row kra-mgr-row" data-kra-mgr-summary="{{ $idx }}" data-weight="{{ $kraWeight }}">
                            <span class="label"><i class="ti ti-user-check me-1"></i>{{ __('Manager Rating') }}:</span>
                            <span class="kra-avg-badge kra-mgr-badge {{ $mgrAvg > 0 ? 'has-val' : '' }}">
                                <span class="avg-formula"><span class="mgr-sum">{{ $mgrSum }}</span>/<span class="mgr-count">{{ count($allKpis) }}</span></span>
                            </span>
                            <span class="kra-weight-chip">× {{ $kraWeight }}% {{ __('weight') }}</span>
                            <span class="kra-weighted-score kra-mgr-score {{ $mgrScore > 0 ? 'has-val' : '' }}">
                                = <strong class="mgr-weighted-val">{{ $mgrScore }}</strong> {{ __('pts') }}
                            </span>
                            <input type="text" class="remarks-input kra-mgr-remarks-input" placeholder="{{ __('Manager remarks…') }}" value="{{ $mgrKraRemarks }}" data-kra="{{ $idx }}" maxlength="500">
                        </div>
                    @endif

                    @if($showHodCol)
                        @php
                            $hodSum   = array_sum(array_map(fn($k) => (int)($k['head_rating'] ?? 0), $allKpis));
                            $hodAvg   = count($allKpis) > 0 ? round($hodSum / count($allKpis), 2) : 0;
                            $hodScore = round($hodAvg * ($kraWeight / 100), 2);
                            $hodKraRemarks = $kra['head_overall_remarks'] ?? '';
                        @endphp
                        <div class="kra-rating-row kra-hod-row" data-kra-hod-summary="{{ $idx }}" data-weight="{{ $kraWeight }}">
                            <span class="label"><i class="ti ti-shield-check me-1"></i>{{ __('HOD Rating') }}:</span>
                            @if(!$isHodFinalised && ($role === 'hod' || $role === 'admin'))
                                <button type="button" class="btn-copy-mgr-rating" data-kra="{{ $idx }}" title="{{ __('Copy Manager Rating') }}">
                                    <i class="ti ti-copy me-1"></i>{{ __('Copy Manager') }}
                                </button>
                            @endif
                            <span class="kra-avg-badge kra-hod-badge {{ $hodAvg > 0 ? 'has-val' : '' }}">
                                <span class="avg-formula"><span class="hod-sum">{{ $hodSum }}</span>/<span class="hod-count">{{ count($allKpis) }}</span></span>
                            </span>
                            <span class="kra-weight-chip">× {{ $kraWeight }}% {{ __('weight') }}</span>
                            <span class="kra-weighted-score kra-hod-score {{ $hodScore > 0 ? 'has-val' : '' }}">
                                = <strong class="hod-weighted-val">{{ $hodScore }}</strong> {{ __('pts') }}
                            </span>
                            <input type="text" class="remarks-input kra-hod-remarks-input" placeholder="{{ __('HOD remarks…') }}" value="{{ $hodKraRemarks }}" data-kra="{{ $idx }}" maxlength="500">
                        </div>
                    @endif

                    @php
                        if ($showHodCol) {
                            $wMetric = 18; $wTarget = 12; $wFreq = 8; $wSelf = 11; $wMgr = 12; $wHod = 12; $wRem = 27;
                        } elseif ($showManagerCol) {
                            $wMetric = 22; $wTarget = 14; $wFreq = 10; $wSelf = 14; $wMgr = 16; $wHod = 0; $wRem = 24;
                        } else {
                            $wMetric = 22; $wTarget = 14; $wFreq = 10; $wSelf = 14; $wMgr = 0; $wHod = 0; $wRem = 30;
                        }
                    @endphp
                    <table class="kpi-table">
                        <thead><tr>
                            <th width="{{ $wMetric }}%">{{ __('KPI / Metric') }}</th>
                            <th width="{{ $wTarget }}%">{{ __('Target') }}</th>
                            <th width="{{ $wFreq }}%">{{ __('Frequency') }}</th>
                            <th width="{{ $wSelf }}%" class="rate-col">{{ __('Self Rating') }}</th>
                            @if($showManagerCol)
                                <th width="{{ $wMgr }}%" class="rate-col mgr-col">{{ __('Manager Rating') }}</th>
                            @endif
                            @if($showHodCol)
                                <th width="{{ $wHod }}%" class="rate-col hod-col">{{ __('HOD Rating') }}</th>
                            @endif
                            <th width="{{ $wRem }}%" class="text-center">{{ __('Remarks & Evidence') }}</th>
                        </tr></thead>
                        <tbody class="kpi-rows">
                        @foreach($kra['kpis'] as $kpiIdx => $kpi)
                        @php $kpiRating = (int) ($kpi['rating'] ?? 0); @endphp
                        <tr data-kpi-index="{{ $kpiIdx }}">
                            <td>
                                <div class="edit-field" data-scope="kpi" data-field="metric" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}">
                                    <span class="edit-value">{{ $kpi['metric'] }}</span>
                                    <button type="button" class="edit-btn-inline" title="{{ __('Edit metric') }}"><i class="ti ti-pencil"></i></button>
                                </div>
                            </td>
                            <td>
                                <div class="edit-field target-highlight" data-scope="kpi" data-field="target" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}">
                                    <span class="edit-value"><strong>{{ $kpi['target'] }}</strong></span>
                                    <button type="button" class="edit-btn-inline" title="{{ __('Edit target') }}"><i class="ti ti-pencil"></i></button>
                                </div>
                            </td>
                            <td>
                                <div class="edit-field" data-scope="kpi" data-field="frequency" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}">
                                    <span class="edit-value">{{ $kpi['frequency'] }}</span>
                                    <button type="button" class="edit-btn-inline" title="{{ __('Edit frequency') }}"><i class="ti ti-pencil"></i></button>
                                </div>
                            </td>
                            <td class="rate-col">
                                <span class="rate-input-wrap self-rate" data-scope="kpi" data-field="rating" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" data-value="{{ $kpiRating }}">
                                    <input type="number" class="rate-input {{ $kpiRating > 0 ? 'has-val' : '' }}" min="0" max="5" step="1" value="{{ $kpiRating ?: '' }}" placeholder="0-5">
                                    <span class="rate-suffix">/5</span>
                                </span>
                            </td>
                            @if($showManagerCol)
                                @php $mgrRating = (int) ($kpi['manager_rating'] ?? 0); @endphp
                                <td class="rate-col mgr-col">
                                    <span class="rate-input-wrap mgr-rate" data-scope="kpi" data-field="manager_rating" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" data-value="{{ $mgrRating }}">
                                        <input type="number" class="rate-input mgr-input {{ $mgrRating > 0 ? 'has-val' : '' }}" min="0" max="5" step="1" value="{{ $mgrRating ?: '' }}" placeholder="0-5">
                                        <span class="rate-suffix">/5</span>
                                    </span>
                                </td>
                            @endif
                            @if($showHodCol)
                                @php $hodRating = (int) ($kpi['head_rating'] ?? 0); @endphp
                                <td class="rate-col hod-col">
                                    <span class="rate-input-wrap hod-rate" data-scope="kpi" data-field="head_rating" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" data-value="{{ $hodRating }}">
                                        <input type="number" class="rate-input hod-input {{ $hodRating > 0 ? 'has-val' : '' }}" min="0" max="5" step="1" value="{{ $hodRating ?: '' }}" placeholder="0-5">
                                        <span class="rate-suffix">/5</span>
                                    </span>
                                </td>
                            @endif
                            @php
                                $kpiRemarks = $kpi['remarks'] ?? '';
                                $kpiDoc = $kpi['document'] ?? null;
                                $kpiDocName = $kpi['document_name'] ?? ($kpiDoc ? basename($kpiDoc) : '');
                            @endphp
                            @php
                                $mgrKpiRemarks = $kpi['manager_remarks'] ?? '';
                                $hodKpiRemarks = $kpi['head_remarks'] ?? '';
                            @endphp
                            <td class="evidence-col">
                                <div class="evidence-wrap">
                                    <input type="text" class="evidence-remarks" placeholder="{{ __('Employee remarks…') }}" value="{{ $kpiRemarks }}" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" maxlength="500">
                                    @if($showManagerCol)
                                        <input type="text" class="evidence-remarks mgr-remarks" placeholder="{{ __('Manager remarks…') }}" value="{{ $mgrKpiRemarks }}" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" maxlength="500">
                                    @endif
                                    @if($showHodCol)
                                        <input type="text" class="evidence-remarks hod-remarks" placeholder="{{ __('HOD remarks…') }}" value="{{ $hodKpiRemarks }}" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" maxlength="500">
                                    @endif
                                    <div class="evidence-actions">
                                        @if($kpiDoc)
                                            <a href="{{ asset('storage/'.$kpiDoc) }}" target="_blank" class="ev-doc-chip" title="{{ $kpiDocName }}">
                                                <i class="ti ti-paperclip"></i><span class="ev-doc-name">{{ \Illuminate\Support\Str::limit($kpiDocName, 14) }}</span>
                                            </a>
                                            <button type="button" class="ev-doc-remove" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" title="{{ __('Remove file') }}"><i class="ti ti-x"></i></button>
                                        @else
                                            <label class="ev-upload-btn" title="{{ __('Upload evidence') }}">
                                                <i class="ti ti-upload"></i>
                                                <input type="file" class="ev-file-input" data-kra="{{ $idx }}" data-kpi="{{ $kpiIdx }}" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" hidden>
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4" id="kraEmptyState">{{ __('No KRA/KPI generated.') }}</div>
            @endforelse
            </div>

            @php
                $totalScore = 0;
                foreach ($kras as $k) {
                    $all = $k['kpis'] ?? [];
                    if (count($all) > 0) {
                        $avg = array_sum(array_map(fn($x) => (int)($x['rating'] ?? 0), $all)) / count($all);
                        $totalScore += $avg * ((int)($k['weightage'] ?? 0) / 100);
                    }
                }
                $totalScore = round($totalScore, 2);
            @endphp
            @php
                $mgrTotalScore = 0;
                if ($showManagerCol) {
                    foreach ($kras as $k) {
                        $all = $k['kpis'] ?? [];
                        if (count($all) > 0) {
                            $avg = array_sum(array_map(fn($x) => (int)($x['manager_rating'] ?? 0), $all)) / count($all);
                            $mgrTotalScore += $avg * ((int)($k['weightage'] ?? 0) / 100);
                        }
                    }
                    $mgrTotalScore = round($mgrTotalScore, 2);
                }
            @endphp
            @php
                $hodTotalScore = 0;
                if ($showHodCol) {
                    foreach ($kras as $k) {
                        $all = $k['kpis'] ?? [];
                        if (count($all) > 0) {
                            $avg = array_sum(array_map(fn($x) => (int)($x['head_rating'] ?? 0), $all)) / count($all);
                            $hodTotalScore += $avg * ((int)($k['weightage'] ?? 0) / 100);
                        }
                    }
                    $hodTotalScore = round($hodTotalScore, 2);
                }
            @endphp
            <div class="totals-wrap">
                <div class="overall-total-card" id="overallTotalCard">
                    <div class="ot-label"><i class="ti ti-user me-1"></i>{{ __('Self — Final Weighted Score') }}</div>
                    <div class="ot-value"><span id="totalScoreVal">{{ $totalScore }}</span><small> / 5</small></div>
                    <div class="ot-hint">{{ __('Sum of (KRA avg × weightage) across all KRAs') }}</div>
                </div>
                @if($showManagerCol)
                <div class="overall-total-card mgr-total-card">
                    <div class="ot-label"><i class="ti ti-user-check me-1"></i>{{ __('Manager — Final Weighted Score') }}</div>
                    <div class="ot-value"><span id="mgrTotalScoreVal">{{ $mgrTotalScore }}</span><small> / 5</small></div>
                    <div class="ot-hint">{{ __('Manager-rated equivalent of the same formula') }}</div>
                </div>
                @endif
                @if($showHodCol)
                <div class="overall-total-card hod-total-card">
                    <div class="ot-label"><i class="ti ti-shield-check me-1"></i>{{ __('HOD — Final Weighted Score') }}</div>
                    <div class="ot-value"><span id="hodTotalScoreVal">{{ $hodTotalScore }}</span><small> / 5</small></div>
                    <div class="ot-hint">{{ __('HOD-rated equivalent of the same formula') }}</div>
                </div>
                @endif
            </div>

            <div class="kg-save-bar">
                @if($isHodFinalised)
                    <div class="kg-status-pill kg-status-hod-done">
                        <i class="ti ti-lock"></i>
                        {{ __('Finalised by HOD on') }} {{ $gen->hod_reviewed_at?->format('d M Y, h:i A') }}
                        <span class="kg-score-chip chip-self"><i class="ti ti-user"></i>{{ __('Self') }} <strong>{{ $totalScore }}</strong>/5</span>
                        <span class="kg-score-chip chip-mgr"><i class="ti ti-user-check"></i>{{ __('Manager') }} <strong>{{ $mgrTotalScore }}</strong>/5</span>
                        <span class="kg-score-chip chip-hod"><i class="ti ti-shield-check"></i>{{ __('HOD') }} <strong>{{ $hodTotalScore }}</strong>/5</span>
                    </div>
                @elseif($isManagerFinalised)
                    <div class="kg-status-pill kg-status-mgr-done">
                        <i class="ti ti-circle-check"></i>
                        {{ __('Manager submitted to HOD on') }} {{ $gen->manager_reviewed_at?->format('d M Y, h:i A') }} — {{ __('awaiting HOD rating') }}
                        <span class="kg-score-chip chip-self"><i class="ti ti-user"></i>{{ __('Self') }} <strong>{{ $totalScore }}</strong>/5</span>
                        <span class="kg-score-chip chip-mgr"><i class="ti ti-user-check"></i>{{ __('Manager') }} <strong>{{ $mgrTotalScore }}</strong>/5</span>
                    </div>
                    @if($role === 'hod' || $role === 'admin')
                        <button type="button" class="btn btn-copy-all-mgr" id="btnCopyAllMgr">
                            <i class="ti ti-copy me-1"></i>{{ __('Copy All Manager Ratings') }}
                        </button>
                        <a href="{{ route('growth-review.kpi-generator.index') }}" class="btn kg-save-btn">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save & Exit') }}
                        </a>
                        <form method="POST" action="{{ route('growth-review.kpi-generator.hod-finalize', $gen->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Finalise this review? HOD ratings will be locked and the record fully closed.') }}');">
                            @csrf
                            <button type="submit" class="btn kg-submit-btn">
                                <i class="ti ti-lock me-1"></i>{{ __('Finalise HOD Review') }}
                            </button>
                        </form>
                    @endif
                @elseif($isSubmitted)
                    <div class="kg-status-pill kg-status-submitted">
                        <i class="ti ti-circle-check"></i>
                        {{ __('Employee submitted on') }} {{ $gen->submitted_at?->format('d M Y, h:i A') }} — {{ __('awaiting manager rating') }}
                        <span class="kg-score-chip chip-self"><i class="ti ti-user"></i>{{ __('Self') }} <strong>{{ $totalScore }}</strong>/5</span>
                        @if($role !== 'employee')
                            <span class="kg-score-chip chip-mgr" id="statusMgrChip"><i class="ti ti-user-check"></i>{{ __('Manager') }} <strong id="statusMgrScoreVal">{{ $mgrTotalScore }}</strong>/5</span>
                        @endif
                    </div>
                    @if($role === 'manager' || $role === 'admin')
                        <a href="{{ route('growth-review.kpi-generator.index') }}" class="btn kg-save-btn">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save & Exit') }}
                        </a>
                        <form method="POST" action="{{ route('growth-review.kpi-generator.manager-finalize', $gen->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Submit to HOD? Manager ratings will be locked and the review moves to the HOD for final rating.') }}');">
                            @csrf
                            <button type="submit" class="btn kg-submit-btn">
                                <i class="ti ti-send me-1"></i>{{ __('Save & Submit to HOD') }}
                            </button>
                        </form>
                    @endif
                @else
                    <div class="kg-status-pill kg-status-draft">
                        <i class="ti ti-pencil"></i>
                        {{ __('Draft — changes auto-save as you edit') }}
                    </div>
                    <a href="{{ route('growth-review.kpi-generator.index') }}" class="btn kg-save-btn">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('Save & Exit') }}
                    </a>
                    <form method="POST" action="{{ route('growth-review.kpi-generator.submit', $gen->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Submit this review? After submit only manager can rate.') }}');">
                        @csrf
                        <button type="submit" class="btn kg-submit-btn">
                            <i class="ti ti-send me-1"></i>{{ __('Submit Review') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @include('growth_review.kpi_generator._assign_modal', [
        'modalId'     => 'assignModal',
        'gen'         => $gen,
        'employees'   => $employees,
        'assignedIds' => $assignedIds,
    ])
    @include('growth_review.kpi_generator._assign_modal_styles')

    @push('script-page')
    @if(request('download'))
    <script>
        // Auto-trigger PDF download when redirected here from generate() with ?download=1.
        window.addEventListener('load', function () {
            window.location.href = "{{ route('growth-review.kpi-generator.pdf', $gen->id) }}";
        });
    </script>
    @endif
    <script>
    (function(){
        var URL_UPDATE   = "{{ route('growth-review.kpi-generator.update-target', $gen->id) }}";
        var URL_ADD_KPI  = "{{ route('growth-review.kpi-generator.add-kpi', $gen->id) }}";
        var URL_DEL_KPI  = "{{ route('growth-review.kpi-generator.delete-kpi', $gen->id) }}";
        var URL_ADD_KRA  = "{{ route('growth-review.kpi-generator.add-kra', $gen->id) }}";
        var URL_DEL_KRA  = "{{ route('growth-review.kpi-generator.delete-kra', $gen->id) }}";
        var URL_DOC_UP   = "{{ route('growth-review.kpi-generator.kpi-document.upload', $gen->id) }}";
        var URL_DOC_DEL  = "{{ route('growth-review.kpi-generator.kpi-document.delete', $gen->id) }}";
        var CSRF = "{{ csrf_token() }}";

        function postJson(url, body, method){
            return fetch(url, {
                method: method || 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            }).then(function(r){ return r.json(); });
        }

        // ── Inline field editor ─────────────────────────────────────
        function enterEdit(field){
            if (field.classList.contains('is-editing')) return;
            var valEl = field.querySelector('.edit-value');
            if (!valEl) return;
            // For target we show <strong>…</strong>; strip it for editing
            var strong = valEl.querySelector('strong');
            var current = (strong ? strong.textContent : valEl.textContent).trim();
            // Remove placeholder "(No description…)" so input is empty not literal text
            if (field.dataset.field === 'description' && current.indexOf('(No description') !== -1) current = '';

            field.classList.add('is-editing');

            var isMultiline = (field.dataset.field === 'description');
            var input = document.createElement(isMultiline ? 'textarea' : 'input');
            input.className = 'edit-input';
            input.value = current;
            if (!isMultiline) {
                input.type = (field.dataset.field === 'weightage') ? 'number' : 'text';
                input.maxLength = 255;
                if (field.dataset.field === 'weightage') { input.min = 0; input.max = 100; }
            } else {
                input.rows = 2;
                input.maxLength = 500;
            }

            var save = document.createElement('button');
            save.type = 'button'; save.className = 'edit-save'; save.title = 'Save';
            save.innerHTML = '<i class="ti ti-check"></i>';
            var cancel = document.createElement('button');
            cancel.type = 'button'; cancel.className = 'edit-cancel'; cancel.title = 'Cancel';
            cancel.innerHTML = '<i class="ti ti-x"></i>';

            field.appendChild(input);
            field.appendChild(save);
            field.appendChild(cancel);
            input.focus();
            if (input.select) input.select();

            function cleanup(){
                field.classList.remove('is-editing');
                input.remove(); save.remove(); cancel.remove();
            }

            function commit(){
                var newVal = String(input.value).trim();
                if (newVal === '' || newVal === current) { cleanup(); return; }
                field.classList.add('is-saving');
                postJson(URL_UPDATE, {
                    scope:     field.dataset.scope,
                    kra_index: parseInt(field.dataset.kra, 10),
                    kpi_index: field.dataset.kpi !== undefined ? parseInt(field.dataset.kpi, 10) : null,
                    field:     field.dataset.field,
                    value:     newVal,
                }).then(function(j){
                    field.classList.remove('is-saving');
                    if (j && j.ok) {
                        if (strong) strong.textContent = j.value;
                        else valEl.textContent = j.value || ((field.dataset.field === 'description') ? '(No description — click to add)' : j.value);
                        field.classList.add('flash-success');
                        setTimeout(function(){ field.classList.remove('flash-success'); }, 900);
                        // Weightage change → refresh weighted scores + totals (self + manager + HOD)
                        if (field.dataset.scope === 'kra' && field.dataset.field === 'weightage') {
                            var kraIdx = parseInt(field.dataset.kra, 10);
                            ['data-kra-summary', 'data-kra-mgr-summary', 'data-kra-hod-summary'].forEach(function(attr){
                                var row = document.querySelector('.kra-rating-row[' + attr + '="' + kraIdx + '"]');
                                if (row) row.dataset.weight = String(j.value);
                            });
                            // Update the inline weight chip on each summary row.
                            var card = document.querySelector('.kra-card[data-kra-index="' + kraIdx + '"]');
                            if (card) {
                                card.querySelectorAll('.kra-rating-row .weight-chip-val').forEach(function(el){
                                    el.textContent = j.value;
                                });
                                // Manager/HOD chips render the value inline (no .weight-chip-val span) — patch their text.
                                card.querySelectorAll('.kra-mgr-row .kra-weight-chip, .kra-hod-row .kra-weight-chip').forEach(function(el){
                                    el.innerHTML = '× ' + j.value + '% {{ __('weight') }}';
                                });
                            }
                            recomputeKraScore(kraIdx);
                            recomputeFinalTotal();
                            if (typeof recomputeMgrKraScore === 'function') { recomputeMgrKraScore(kraIdx); recomputeMgrFinalTotal(); }
                            if (typeof recomputeHodKraScore === 'function') { recomputeHodKraScore(kraIdx); recomputeHodFinalTotal(); }
                        }
                    } else {
                        alert((j && j.error) || 'Failed to save.');
                    }
                    cleanup();
                }).catch(function(){
                    field.classList.remove('is-saving');
                    alert('Network error while saving.');
                    cleanup();
                });
            }

            save.addEventListener('click', commit);
            cancel.addEventListener('click', cleanup);
            input.addEventListener('keydown', function(e){
                if (e.key === 'Enter' && !isMultiline) { e.preventDefault(); commit(); }
                else if (e.key === 'Escape') { cleanup(); }
            });
        }

        // ── Numeric rating pills (1-10) ────────────────────────────
        function paintNums(container, value){
            var nums = container.querySelectorAll('.num');
            nums.forEach(function(n){
                var v = parseInt(n.dataset.num, 10);
                n.classList.toggle('on', v === value);
            });
            var score = container.querySelector('.rate-score');
            if (score) {
                score.textContent = value ? (value + '/5') : 'Not rated';
                score.classList.toggle('has-val', value > 0);
            }
        }
        function saveRating(container, value){
            var field = container.dataset.field || 'rating';
            var body = {
                scope:     container.dataset.scope,
                kra_index: parseInt(container.dataset.kra, 10),
                kpi_index: container.dataset.kpi !== undefined ? parseInt(container.dataset.kpi, 10) : null,
                field:     field,
                value:     String(value),
            };
            postJson(URL_UPDATE, body).then(function(j){
                if (j && j.ok) {
                    container.dataset.value = String(value);
                    paintNums(container, value);
                    // Recalc KRA average + weighted score + final total
                    var kraIdx = parseInt(container.dataset.kra, 10);
                    if (field === 'manager_rating') {
                        recomputeMgrKraScore(kraIdx);
                        recomputeMgrFinalTotal();
                    } else if (field === 'head_rating') {
                        recomputeHodKraScore(kraIdx);
                        recomputeHodFinalTotal();
                    } else {
                        recomputeKraScore(kraIdx);
                        recomputeFinalTotal();
                    }
                } else {
                    alert((j && j.error) || 'Failed to save rating.');
                }
            });
        }

        // ── Live recompute of KRA average, weighted score & final total ──
        function recomputeKraScore(kraIdx){
            var card = document.querySelector('.kra-card[data-kra-index="' + kraIdx + '"]');
            if (!card) return;
            var summary = card.querySelector('.kra-rating-row[data-kra-summary]');
            if (!summary) return;

            var pills = card.querySelectorAll('.kpi-rows .rate-input-wrap.self-rate');
            var sum = 0, total = pills.length;
            pills.forEach(function(p){
                sum += parseInt(p.dataset.value, 10) || 0;
            });
            var avg = total > 0 ? Math.round((sum / total) * 100) / 100 : 0;
            var weight = parseInt(summary.dataset.weight, 10) || 0;
            var weighted = Math.round(avg * (weight / 100) * 100) / 100;

            var avgBadge = summary.querySelector('.kra-avg-badge');
            var avgVal   = summary.querySelector('.avg-val');
            var avgSum   = summary.querySelector('.avg-sum');
            var avgCount = summary.querySelector('.avg-count');
            var wrap     = summary.querySelector('.kra-weighted-score');
            var wVal     = summary.querySelector('.weighted-val');
            if (avgSum) avgSum.textContent = sum;
            if (avgCount) avgCount.textContent = total;
            if (avgVal) avgVal.textContent = avg > 0 ? avg.toFixed(1) : '—';
            if (avgBadge) avgBadge.classList.toggle('has-val', avg > 0);
            if (wVal) wVal.textContent = weighted;
            if (wrap) wrap.classList.toggle('has-val', weighted > 0);
        }
        function recomputeFinalTotal(){
            var rows = document.querySelectorAll('.kra-rating-row[data-kra-summary]');
            var total = 0;
            rows.forEach(function(r){
                var w = parseFloat(r.querySelector('.weighted-val')?.textContent || 0);
                if (!isNaN(w)) total += w;
            });
            total = Math.round(total * 100) / 100;
            var el = document.getElementById('totalScoreVal');
            if (el) el.textContent = total;
        }
        function recomputeMgrKraScore(kraIdx){
            var card = document.querySelector('.kra-card[data-kra-index="' + kraIdx + '"]');
            if (!card) return;
            var summary = card.querySelector('.kra-rating-row[data-kra-mgr-summary]');
            if (!summary) return;
            var pills = card.querySelectorAll('.kpi-rows .rate-input-wrap.mgr-rate');
            var sum = 0, total = pills.length;
            pills.forEach(function(p){ sum += parseInt(p.dataset.value, 10) || 0; });
            var avg = total > 0 ? Math.round((sum / total) * 100) / 100 : 0;
            var weight = parseInt(summary.dataset.weight, 10) || 0;
            var weighted = Math.round(avg * (weight / 100) * 100) / 100;
            var sumEl = summary.querySelector('.mgr-sum');
            var countEl = summary.querySelector('.mgr-count');
            var badge = summary.querySelector('.kra-mgr-badge');
            var scoreWrap = summary.querySelector('.kra-mgr-score');
            var wVal = summary.querySelector('.mgr-weighted-val');
            if (sumEl) sumEl.textContent = sum;
            if (countEl) countEl.textContent = total;
            if (badge) badge.classList.toggle('has-val', avg > 0);
            if (wVal) wVal.textContent = weighted;
            if (scoreWrap) scoreWrap.classList.toggle('has-val', weighted > 0);
        }
        function recomputeMgrFinalTotal(){
            var rows = document.querySelectorAll('.kra-rating-row[data-kra-mgr-summary]');
            var total = 0;
            rows.forEach(function(r){
                var w = parseFloat(r.querySelector('.mgr-weighted-val')?.textContent || 0);
                if (!isNaN(w)) total += w;
            });
            total = Math.round(total * 100) / 100;
            var el = document.getElementById('mgrTotalScoreVal');
            if (el) el.textContent = total;
            var chipEl = document.getElementById('statusMgrScoreVal');
            if (chipEl) chipEl.textContent = total;
        }
        function recomputeHodKraScore(kraIdx){
            var card = document.querySelector('.kra-card[data-kra-index="' + kraIdx + '"]');
            if (!card) return;
            var summary = card.querySelector('.kra-rating-row[data-kra-hod-summary]');
            if (!summary) return;
            var pills = card.querySelectorAll('.kpi-rows .rate-input-wrap.hod-rate');
            var sum = 0, total = pills.length;
            pills.forEach(function(p){ sum += parseInt(p.dataset.value, 10) || 0; });
            var avg = total > 0 ? Math.round((sum / total) * 100) / 100 : 0;
            var weight = parseInt(summary.dataset.weight, 10) || 0;
            var weighted = Math.round(avg * (weight / 100) * 100) / 100;
            var sumEl = summary.querySelector('.hod-sum');
            var countEl = summary.querySelector('.hod-count');
            var badge = summary.querySelector('.kra-hod-badge');
            var scoreWrap = summary.querySelector('.kra-hod-score');
            var wVal = summary.querySelector('.hod-weighted-val');
            if (sumEl) sumEl.textContent = sum;
            if (countEl) countEl.textContent = total;
            if (badge) badge.classList.toggle('has-val', avg > 0);
            if (wVal) wVal.textContent = weighted;
            if (scoreWrap) scoreWrap.classList.toggle('has-val', weighted > 0);
        }
        function recomputeHodFinalTotal(){
            var rows = document.querySelectorAll('.kra-rating-row[data-kra-hod-summary]');
            var total = 0;
            rows.forEach(function(r){
                var w = parseFloat(r.querySelector('.hod-weighted-val')?.textContent || 0);
                if (!isNaN(w)) total += w;
            });
            total = Math.round(total * 100) / 100;
            var el = document.getElementById('hodTotalScoreVal');
            if (el) el.textContent = total;
        }

        // ── KRA remarks input (debounced save) ─────────────────────
        var remarksTimers = {};
        document.addEventListener('input', function(e){
            // Rating number input (self or manager)
            var rInp = e.target.closest('.rate-input');
            if (rInp) {
                var wrap = rInp.closest('.rate-input-wrap');
                if (!wrap) return;
                var raw = rInp.value.trim();
                var n = raw === '' ? 0 : parseInt(raw, 10);
                if (isNaN(n) || n < 0 || n > 5) {
                    rInp.classList.add('invalid');
                    return;
                }
                rInp.classList.remove('invalid');
                rInp.classList.toggle('has-val', n > 0);
                var rKey = 'rate-'+wrap.dataset.kra+'-'+wrap.dataset.kpi+'-'+wrap.dataset.field;
                clearTimeout(remarksTimers[rKey]);
                remarksTimers[rKey] = setTimeout(function(){
                    var body = {
                        scope: wrap.dataset.scope,
                        kra_index: parseInt(wrap.dataset.kra, 10),
                        kpi_index: wrap.dataset.kpi !== undefined ? parseInt(wrap.dataset.kpi, 10) : null,
                        field: wrap.dataset.field,
                        value: String(n),
                    };
                    postJson(URL_UPDATE, body).then(function(j){
                        if (j && j.ok) {
                            wrap.dataset.value = String(n);
                            rInp.classList.add('saved');
                            setTimeout(function(){ rInp.classList.remove('saved'); }, 700);
                            var kraIdx = parseInt(wrap.dataset.kra, 10);
                            if (wrap.dataset.field === 'manager_rating') {
                                recomputeMgrKraScore(kraIdx);
                                recomputeMgrFinalTotal();
                            } else if (wrap.dataset.field === 'head_rating') {
                                recomputeHodKraScore(kraIdx);
                                recomputeHodFinalTotal();
                            } else {
                                recomputeKraScore(kraIdx);
                                recomputeFinalTotal();
                            }
                        } else {
                            alert((j && j.error) || 'Failed to save rating.');
                        }
                    });
                }, 500);
                return;
            }
            var inp = e.target.closest('.kra-remarks-input');
            if (inp) {
                var kraIdx = inp.dataset.kra;
                clearTimeout(remarksTimers['kra-'+kraIdx]);
                remarksTimers['kra-'+kraIdx] = setTimeout(function(){
                    postJson(URL_UPDATE, {
                        scope: 'kra', kra_index: parseInt(kraIdx, 10),
                        kpi_index: null, field: 'rating_remarks', value: inp.value,
                    }).then(function(j){
                        if (j && j.ok) {
                            inp.style.borderColor = '#10b981';
                            setTimeout(function(){ inp.style.borderColor = ''; }, 700);
                        }
                    });
                }, 600);
                return;
            }
            // Manager KRA remarks
            var mgrKraInp = e.target.closest('.kra-mgr-remarks-input');
            if (mgrKraInp) {
                var mk = mgrKraInp.dataset.kra;
                clearTimeout(remarksTimers['mgr-kra-'+mk]);
                remarksTimers['mgr-kra-'+mk] = setTimeout(function(){
                    postJson(URL_UPDATE, {
                        scope: 'kra', kra_index: parseInt(mk, 10),
                        kpi_index: null, field: 'manager_overall_remarks', value: mgrKraInp.value,
                    }).then(function(j){
                        if (j && j.ok) {
                            mgrKraInp.style.borderColor = '#10b981';
                            setTimeout(function(){ mgrKraInp.style.borderColor = ''; }, 700);
                        }
                    });
                }, 600);
                return;
            }
            // HOD KRA remarks
            var hodKraInp = e.target.closest('.kra-hod-remarks-input');
            if (hodKraInp) {
                var hk = hodKraInp.dataset.kra;
                clearTimeout(remarksTimers['hod-kra-'+hk]);
                remarksTimers['hod-kra-'+hk] = setTimeout(function(){
                    postJson(URL_UPDATE, {
                        scope: 'kra', kra_index: parseInt(hk, 10),
                        kpi_index: null, field: 'head_overall_remarks', value: hodKraInp.value,
                    }).then(function(j){
                        if (j && j.ok) {
                            hodKraInp.style.borderColor = '#10b981';
                            setTimeout(function(){ hodKraInp.style.borderColor = ''; }, 700);
                        }
                    });
                }, 600);
                return;
            }
            // KPI-level remarks (employee / manager / HOD depending on class)
            var kpiInp = e.target.closest('.evidence-remarks');
            if (kpiInp) {
                var k = kpiInp.dataset.kra, p = kpiInp.dataset.kpi;
                var field = kpiInp.classList.contains('hod-remarks')
                    ? 'head_remarks'
                    : (kpiInp.classList.contains('mgr-remarks') ? 'manager_remarks' : 'remarks');
                var key = field+'-'+k+'-'+p;
                clearTimeout(remarksTimers[key]);
                remarksTimers[key] = setTimeout(function(){
                    postJson(URL_UPDATE, {
                        scope: 'kpi', kra_index: parseInt(k, 10),
                        kpi_index: parseInt(p, 10), field: field, value: kpiInp.value,
                    }).then(function(j){
                        if (j && j.ok) {
                            kpiInp.classList.add('saved');
                            setTimeout(function(){ kpiInp.classList.remove('saved'); }, 700);
                        }
                    });
                }, 600);
                return;
            }
        });

        // ── KPI document upload / delete ───────────────────────────
        document.addEventListener('change', function(e){
            var f = e.target.closest('.ev-file-input');
            if (!f || !f.files || !f.files[0]) return;
            var file = f.files[0];
            var wrap = f.closest('.evidence-wrap');
            var kra = f.dataset.kra, kpi = f.dataset.kpi;

            var fd = new FormData();
            fd.append('kra_index', kra);
            fd.append('kpi_index', kpi);
            fd.append('document', file);

            wrap.classList.add('ev-uploading');
            fetch(URL_DOC_UP, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            }).then(function(r){ return r.json(); }).then(function(j){
                wrap.classList.remove('ev-uploading');
                if (j && j.ok) { window.location.reload(); }
                else { alert((j && j.error) || 'Upload failed.'); }
            }).catch(function(){
                wrap.classList.remove('ev-uploading');
                alert('Network error during upload.');
            });
        });

        // Delegated click for edit buttons / double-click on values
        document.addEventListener('click', function(e){
            // Number pill click
            var num = e.target.closest('.rate-nums .num');
            if (num) {
                var container = num.closest('.rate-nums');
                var newVal = parseInt(num.dataset.num, 10);
                var curVal = parseInt(container.dataset.value, 10) || 0;
                // Click same value again = clear to 0
                if (curVal === newVal) newVal = 0;
                saveRating(container, newVal);
                return;
            }
            var btn = e.target.closest('.edit-btn-inline');
            if (btn) {
                var field = btn.closest('.edit-field');
                if (field) enterEdit(field);
                return;
            }
            // Add KPI
            var addKpi = e.target.closest('.btn-add-kpi');
            if (addKpi) { openAddKpiModal(parseInt(addKpi.dataset.kra, 10)); return; }
            // Remove KPI document
            var docRm = e.target.closest('.ev-doc-remove');
            if (docRm) {
                if (!confirm('{{ __('Remove the attached file?') }}')) return;
                postJson(URL_DOC_DEL, {
                    kra_index: parseInt(docRm.dataset.kra, 10),
                    kpi_index: parseInt(docRm.dataset.kpi, 10),
                }, 'DELETE').then(function(j){
                    if (j && j.ok) { window.location.reload(); }
                    else { alert((j && j.error) || 'Failed to remove file.'); }
                });
                return;
            }
            // Delete KPI
            var delKpi = e.target.closest('.btn-kpi-delete');
            if (delKpi) {
                if (!confirm('{{ __('Delete this KPI?') }}')) return;
                postJson(URL_DEL_KPI, {
                    kra_index: parseInt(delKpi.dataset.kra, 10),
                    kpi_index: parseInt(delKpi.dataset.kpi, 10),
                }, 'DELETE').then(function(j){
                    if (j && j.ok) { window.location.reload(); }
                    else { alert((j && j.error) || 'Failed to delete KPI.'); }
                });
                return;
            }
            // Delete KRA
            var delKra = e.target.closest('.btn-kra-delete');
            if (delKra) {
                if (!confirm('{{ __('Delete this entire KRA section? All its KPIs will be removed.') }}')) return;
                postJson(URL_DEL_KRA, {
                    kra_index: parseInt(delKra.dataset.kra, 10),
                }, 'DELETE').then(function(j){
                    if (j && j.ok) { window.location.reload(); }
                    else { alert((j && j.error) || 'Failed to delete KRA.'); }
                });
                return;
            }
            // Add new KRA
            var addKra = e.target.closest('#btnAddKra');
            if (addKra) { openAddKraModal(); return; }
        });
        document.addEventListener('dblclick', function(e){
            var val = e.target.closest('.edit-value');
            if (val) {
                var field = val.closest('.edit-field');
                if (field) enterEdit(field);
            }
        });

        // ── Add KPI modal (inline prompt) ─────────────────────────
        function openAddKpiModal(kraIdx){
            var modal = getOrCreateModal('addKpiModal', function(m){
                m.innerHTML =
                    '<div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:14px;border:none;">' +
                    '<div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px 14px 0 0;">' +
                    '<h5 class="modal-title"><i class="ti ti-plus me-1"></i>Add KPI</h5>' +
                    '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>' +
                    '<div class="modal-body">' +
                    '<div class="mb-3"><label class="form-label">Metric / KPI Name *</label>' +
                    '<input type="text" id="addKpiMetric" class="form-control" maxlength="255" placeholder="e.g. Customer retention rate"></div>' +
                    '<div class="row g-2">' +
                    '<div class="col-7"><label class="form-label">Target *</label>' +
                    '<input type="text" id="addKpiTarget" class="form-control" maxlength="255" placeholder="e.g. > 90%"></div>' +
                    '<div class="col-5"><label class="form-label">Frequency</label>' +
                    '<input type="text" id="addKpiFreq" class="form-control" maxlength="50" value="{{ $gen->target_timeframe ?: 'Quarterly' }}"></div>' +
                    '</div></div>' +
                    '<div class="modal-footer" style="border:none;">' +
                    '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
                    '<button type="button" class="btn btn-primary" id="addKpiSubmit"><i class="ti ti-check me-1"></i>Add KPI</button>' +
                    '</div></div></div>';
            });
            var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            modal.querySelector('#addKpiMetric').value = '';
            modal.querySelector('#addKpiTarget').value = '';
            modal.querySelector('#addKpiSubmit').onclick = function(){
                var metric = modal.querySelector('#addKpiMetric').value.trim();
                var target = modal.querySelector('#addKpiTarget').value.trim();
                var freq   = modal.querySelector('#addKpiFreq').value.trim();
                if (!metric || !target) { alert('Metric and Target are required.'); return; }
                postJson(URL_ADD_KPI, { kra_index: kraIdx, metric: metric, target: target, frequency: freq })
                    .then(function(j){
                        if (j && j.ok) { bsModal.hide(); window.location.reload(); }
                        else { alert((j && j.error) || 'Failed to add KPI.'); }
                    });
            };
            bsModal.show();
            setTimeout(function(){ modal.querySelector('#addKpiMetric').focus(); }, 200);
        }

        // ── Add KRA modal ─────────────────────────────────────────
        function openAddKraModal(){
            var modal = getOrCreateModal('addKraModal', function(m){
                m.innerHTML =
                    '<div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:14px;border:none;">' +
                    '<div class="modal-header" style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);color:#fff;border:none;border-radius:14px 14px 0 0;">' +
                    '<h5 class="modal-title"><i class="ti ti-plus me-1"></i>Add New KRA</h5>' +
                    '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>' +
                    '<div class="modal-body">' +
                    '<div class="mb-3"><label class="form-label">KRA Title *</label>' +
                    '<input type="text" id="addKraTitle" class="form-control" maxlength="255" placeholder="e.g. Customer Experience"></div>' +
                    '<div class="mb-3"><label class="form-label">Description</label>' +
                    '<textarea id="addKraDesc" class="form-control" rows="2" maxlength="500" placeholder="Briefly describe this KRA…"></textarea></div>' +
                    '<div class="mb-3" style="max-width:180px;"><label class="form-label">Weightage %</label>' +
                    '<input type="number" id="addKraWeight" class="form-control" min="0" max="100" value="0"></div>' +
                    '</div>' +
                    '<div class="modal-footer" style="border:none;">' +
                    '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
                    '<button type="button" class="btn" id="addKraSubmit" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;padding:8px 22px;border-radius:8px;font-weight:600;"><i class="ti ti-check me-1"></i>Add KRA</button>' +
                    '</div></div></div>';
            });
            var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            modal.querySelector('#addKraTitle').value = '';
            modal.querySelector('#addKraDesc').value = '';
            modal.querySelector('#addKraWeight').value = 0;
            modal.querySelector('#addKraSubmit').onclick = function(){
                var title = modal.querySelector('#addKraTitle').value.trim();
                var desc  = modal.querySelector('#addKraDesc').value.trim();
                var wt    = parseInt(modal.querySelector('#addKraWeight').value, 10) || 0;
                if (!title) { alert('KRA title is required.'); return; }
                postJson(URL_ADD_KRA, { kra: title, description: desc, weightage: wt })
                    .then(function(j){
                        if (j && j.ok) { bsModal.hide(); window.location.reload(); }
                        else { alert((j && j.error) || 'Failed to add KRA.'); }
                    });
            };
            bsModal.show();
            setTimeout(function(){ modal.querySelector('#addKraTitle').focus(); }, 200);
        }

        function getOrCreateModal(id, builder){
            var m = document.getElementById(id);
            if (!m) {
                m = document.createElement('div');
                m.id = id;
                m.className = 'modal fade';
                m.tabIndex = -1;
                builder(m);
                document.body.appendChild(m);
            }
            return m;
        }

        // ── Copy Manager Rating → HOD Rating ──────────────────────
        function copyMgrToHodForKra(kraIdx, btn){
            var card = document.querySelector('.kra-card[data-kra-index="' + kraIdx + '"]');
            if (!card) return Promise.resolve();

            var mgrWraps = card.querySelectorAll('.kpi-rows .rate-input-wrap.mgr-rate');
            var hodWraps = card.querySelectorAll('.kpi-rows .rate-input-wrap.hod-rate');
            var promises = [];

            mgrWraps.forEach(function(mgrWrap, i){
                var hodWrap = hodWraps[i];
                if (!hodWrap) return;
                var mgrVal = parseInt(mgrWrap.dataset.value, 10) || 0;
                var hodInput = hodWrap.querySelector('.rate-input');
                if (!hodInput || mgrVal === 0) return;

                hodInput.value = mgrVal;
                hodInput.classList.add('has-val');
                hodWrap.dataset.value = String(mgrVal);

                promises.push(postJson(URL_UPDATE, {
                    scope: 'kpi',
                    kra_index: parseInt(hodWrap.dataset.kra, 10),
                    kpi_index: parseInt(hodWrap.dataset.kpi, 10),
                    field: 'head_rating',
                    value: String(mgrVal),
                }));
            });

            // Also copy manager KRA remarks to HOD KRA remarks
            var mgrRemarksInp = card.querySelector('.kra-mgr-remarks-input[data-kra="' + kraIdx + '"]');
            var hodRemarksInp = card.querySelector('.kra-hod-remarks-input[data-kra="' + kraIdx + '"]');
            if (mgrRemarksInp && hodRemarksInp && mgrRemarksInp.value.trim()) {
                hodRemarksInp.value = mgrRemarksInp.value;
                promises.push(postJson(URL_UPDATE, {
                    scope: 'kra', kra_index: parseInt(kraIdx, 10),
                    kpi_index: null, field: 'head_overall_remarks', value: mgrRemarksInp.value,
                }));
            }

            // Also copy KPI-level manager remarks to HOD remarks
            var mgrKpiRemarks = card.querySelectorAll('.evidence-remarks.mgr-remarks');
            var hodKpiRemarks = card.querySelectorAll('.evidence-remarks.hod-remarks');
            mgrKpiRemarks.forEach(function(mgrR, i){
                var hodR = hodKpiRemarks[i];
                if (!hodR || !mgrR.value.trim()) return;
                hodR.value = mgrR.value;
                promises.push(postJson(URL_UPDATE, {
                    scope: 'kpi', kra_index: parseInt(mgrR.dataset.kra, 10),
                    kpi_index: parseInt(mgrR.dataset.kpi, 10),
                    field: 'head_remarks', value: mgrR.value,
                }));
            });

            return Promise.all(promises).then(function(){
                recomputeHodKraScore(kraIdx);
                recomputeHodFinalTotal();
                if (btn) {
                    btn.classList.add('copied');
                    btn.innerHTML = '<i class="ti ti-check me-1"></i>{{ __("Copied!") }}';
                    setTimeout(function(){
                        btn.classList.remove('copied');
                        btn.innerHTML = '<i class="ti ti-copy me-1"></i>{{ __("Copy Manager") }}';
                    }, 1500);
                }
            });
        }

        // Per-KRA copy button
        document.addEventListener('click', function(e){
            var btn = e.target.closest('.btn-copy-mgr-rating');
            if (btn) {
                var kraIdx = parseInt(btn.dataset.kra, 10);
                copyMgrToHodForKra(kraIdx, btn);
                return;
            }
        });

        // Copy All button
        var copyAllBtn = document.getElementById('btnCopyAllMgr');
        if (copyAllBtn) {
            copyAllBtn.addEventListener('click', function(){
                var cards = document.querySelectorAll('.kra-card');
                var allDone = [];
                cards.forEach(function(card){
                    var kraIdx = parseInt(card.dataset.kraIndex, 10);
                    allDone.push(copyMgrToHodForKra(kraIdx, null));
                });
                Promise.all(allDone).then(function(){
                    copyAllBtn.innerHTML = '<i class="ti ti-check me-1"></i>{{ __("All Copied!") }}';
                    copyAllBtn.style.background = '#d1fae5';
                    copyAllBtn.style.borderColor = '#6ee7b7';
                    copyAllBtn.style.color = '#065f46';
                    setTimeout(function(){
                        copyAllBtn.innerHTML = '<i class="ti ti-copy me-1"></i>{{ __("Copy All Manager Ratings") }}';
                        copyAllBtn.style.background = '';
                        copyAllBtn.style.borderColor = '';
                        copyAllBtn.style.color = '';
                    }, 2000);
                });
            });
        }
    })();
    </script>
    @endpush
@endsection
