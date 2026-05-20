<div class="card mb-3">
    <div class="card-body py-2">
        <h5 class="mb-2">{{ __('Statutory Components') }}</h5>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <a href="{{ route('statutory.component.settings', ['code' => 'EPF']) }}" class="small {{ request()->is('statutory/EPF') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('EPF') }}</a>
            <a href="{{ route('statutory.component.settings', ['code' => 'ESIC']) }}" class="small {{ request()->is('statutory/ESIC') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('ESI') }}</a>
            <a href="{{ route('statutory.component.settings', ['code' => 'PT']) }}" class="small {{ request()->is('statutory/PT') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('Professional Tax') }}</a>
            <a href="{{ route('statutory.component.settings', ['code' => 'LWF']) }}" class="small {{ request()->is('statutory/LWF') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('Labour Welfare Fund') }}</a>
            <span class="mx-1 text-muted">|</span>
            <a href="{{ route('statutory.states') }}" class="small {{ request()->routeIs('statutory.states') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('State Configuration') }}</a>
            <a href="{{ route('statutory.employee.config') }}" class="small {{ request()->routeIs('statutory.employee.config') ? 'text-primary fw-bold' : 'text-muted' }}">{{ __('Employee Config') }}</a>
        </div>
    </div>
</div>

