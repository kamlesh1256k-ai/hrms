<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('payroll.schedule') }}" class="btn btn-sm {{ request()->routeIs('payroll.schedule') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Pay Schedule') }}</a>
            <a href="{{ route('payroll.components') }}" class="btn btn-sm {{ request()->routeIs('payroll.components') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Salary Components') }}</a>
            <a href="{{ route('payroll.employee.salary') }}" class="btn btn-sm {{ request()->routeIs('payroll.employee.salary') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Employee Salary') }}</a>
            <a href="{{ route('payroll.salary.increment') }}" class="btn btn-sm {{ request()->routeIs('payroll.salary.increment') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Salary Increment') }}</a>
            <a href="{{ route('payroll.reimbursements') }}" class="btn btn-sm {{ request()->routeIs('payroll.reimbursements') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Reimbursements') }}</a>
            <a href="{{ route('payroll.supplementary') }}" class="btn btn-sm {{ request()->routeIs('payroll.supplementary') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Supplementary') }}</a>
            <a href="{{ route('payroll.process') }}" class="btn btn-sm {{ request()->routeIs('payroll.process') ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Process Payroll') }}</a>
        </div>
    </div>
</div>

