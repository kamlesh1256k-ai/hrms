@extends('layouts.admin')
@section('page-title') {{ __('Crew — Organization Chart') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('People Hub') }}</li>
    <li class="breadcrumb-item">{{ __('Crew') }}</li>
@endsection

@push('css-page')
<style>
    .org-chart-wrap{overflow-x:auto;padding:20px 0;}
    .org-chart,.org-chart ul{list-style:none;padding:0;margin:0;position:relative;}
    .org-chart{display:flex;justify-content:center;}
    .org-chart ul{display:flex;justify-content:center;padding-top:20px;position:relative;}
    .org-chart ul::before{content:'';position:absolute;top:0;left:50%;border-left:2px solid #d1d5db;height:20px;}
    .org-chart li{display:flex;flex-direction:column;align-items:center;position:relative;padding:20px 8px 0;}
    /* Horizontal connectors */
    .org-chart li::before,.org-chart li::after{content:'';position:absolute;top:0;width:50%;height:20px;border-top:2px solid #d1d5db;}
    .org-chart li::before{right:50%;}
    .org-chart li::after{left:50%;}
    .org-chart li:first-child::before,.org-chart li:last-child::after{border:none;}
    .org-chart li:only-child::before,.org-chart li:only-child::after{border:none;}
    /* Vertical connector from parent */
    .org-chart li::before{border-left:2px solid #d1d5db;}
    .org-chart > li::before,.org-chart > li::after{border:none;}
    .org-chart > li{padding-top:0;}

    .org-card{
        display:inline-flex;flex-direction:column;align-items:center;padding:12px 18px;
        border:1.5px solid #e5e7eb;border-radius:12px;background:#fff;cursor:pointer;
        transition:all .15s;min-width:130px;position:relative;
    }
    .org-card:hover{border-color:#6366f1;box-shadow:0 4px 14px rgba(99,102,241,.18);transform:translateY(-2px);}
    .org-avatar{
        width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;
        font-weight:700;font-size:1.1rem;color:#fff;margin-bottom:6px;
    }
    .org-name{font-weight:700;font-size:.82rem;color:#1f2a44;text-align:center;line-height:1.2;}
    .org-role{font-size:.68rem;color:#94a3b8;text-align:center;}
    .org-dept{font-size:.62rem;color:#6366f1;background:#ede9fe;padding:1px 8px;border-radius:8px;margin-top:4px;}
    .org-count{position:absolute;top:-6px;right:-6px;background:#6366f1;color:#fff;font-size:.6rem;font-weight:700;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;}
</style>
@endpush

@section('content')
    @include('people_hub._nav')

    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label mb-1">{{ __('Department') }}</label>
                    <select name="department_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                        <option value="">{{ __('All Departments') }}</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" {{ $deptId==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <span class="text-muted" style="font-size:.82rem;"><i class="ti ti-users me-1"></i>{{ $employees->count() }} {{ __('people') }}</span>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="org-chart-wrap">
                @php
                    $colors = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#06b6d4'];

                    function renderOrgTree($nodes, $empById, $colors) {
                        echo '<ul class="org-chart">';
                        foreach ($nodes as $node) {
                            $children = $empById->filter(fn($e) => (int)$e->reporting_manager_id === $node->id);
                            $color = $colors[$node->id % count($colors)];
                            echo '<li>';
                            echo '<div class="org-card" data-url="' . route('people-hub.detail', $node->id) . '" data-ajax-popup="true" data-size="lg" data-title="' . e($node->name) . '">';
                            if ($children->isNotEmpty()) echo '<span class="org-count">' . $children->count() . '</span>';
                            echo '<div class="org-avatar" style="background:' . $color . ';">' . strtoupper(substr($node->name, 0, 1)) . '</div>';
                            echo '<div class="org-name">' . e($node->name) . '</div>';
                            echo '<div class="org-role">' . e($node->designation->name ?? '') . '</div>';
                            echo '<div class="org-dept">' . e($node->department->name ?? '') . '</div>';
                            echo '</div>';
                            if ($children->isNotEmpty()) {
                                renderOrgTree($children, $empById, $colors);
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                    }

                    renderOrgTree($tree, $empById, $colors);
                @endphp
            </div>
        </div>
    </div>
@endsection
