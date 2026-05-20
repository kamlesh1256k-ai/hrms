{{--
    Reusable assign modal.
    Expects:
      $modalId       – unique DOM id for this modal instance
      $gen           – GrKpiGeneration model
      $employees     – collection of Employee rows
      $assignedIds   – array of employee ids already assigned to this gen (optional)
--}}
@php
    $assignedIds = $assignedIds ?? [];
@endphp
<div class="modal fade assign-modal-root" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px;">
        <form method="POST" action="{{ route('growth-review.kpi-generator.assign', $gen->id) }}">
            @csrf
            <div class="modal-content assign-modal">
                <div class="assign-header">
                    <div class="assign-header-icon"><i class="ti ti-user-plus"></i></div>
                    <div class="assign-header-text">
                        <h5>{{ __('Assign KRA / KPI') }}</h5>
                        <div class="assign-subtitle">
                            <strong>{{ $gen->job_role }}</strong>
                            @if($gen->industry) · {{ $gen->industry }} @endif
                            @if($gen->seniority_level) · {{ $gen->seniority_level }} @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="assign-toolbar">
                        <div class="assign-search-wrap">
                            <i class="ti ti-search assign-search-icon"></i>
                            <input type="text" class="form-control assign-search" placeholder="{{ __('Search employees by name or ID…') }}">
                        </div>
                        <div class="assign-actions">
                            <label class="assign-select-all">
                                <input type="checkbox" class="assign-select-all-cb">
                                <span>{{ __('Select all visible') }}</span>
                            </label>
                            <span class="assign-count-badge">0 {{ __('selected') }}</span>
                        </div>
                    </div>

                    <div class="assign-list">
                        @forelse($employees as $emp)
                            @php $isAssigned = in_array($emp->id, $assignedIds); @endphp
                            <label class="assign-item {{ $isAssigned ? 'assign-item-disabled' : '' }}"
                                   data-name="{{ strtolower($emp->name) }}"
                                   data-code="{{ strtolower($emp->employee_id ?? '') }}">
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                       class="assign-checkbox" {{ $isAssigned ? 'disabled' : '' }}>
                                <div class="assign-avatar">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                                <div class="assign-info">
                                    <div class="assign-name">{{ $emp->name }}</div>
                                    <div class="assign-meta">
                                        <span class="assign-code">#{{ $emp->employee_id ?? '—' }}</span>
                                        @if($isAssigned)
                                            <span class="assign-status-pill"><i class="ti ti-check"></i> {{ __('Already assigned') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="assign-check-indicator"><i class="ti ti-check"></i></div>
                            </label>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="ti ti-users-off" style="font-size:2.5rem;opacity:.3;"></i>
                                <p class="mt-2 mb-0">{{ __('No employees found.') }}</p>
                            </div>
                        @endforelse
                        <div class="assign-empty-search d-none">
                            <i class="ti ti-search-off"></i>
                            <p>{{ __('No employees match your search.') }}</p>
                        </div>
                    </div>

                    <div class="assign-remarks">
                        <label class="assign-remarks-label">
                            <i class="ti ti-message-circle me-1"></i>{{ __('Remarks') }}
                            <span class="text-muted">({{ __('optional') }})</span>
                        </label>
                        <textarea name="remarks" rows="2" class="form-control assign-remarks-input" maxlength="500"
                                  placeholder="{{ __('Any note for the assigned employees…') }}"></textarea>
                    </div>
                </div>

                <div class="assign-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-assign-primary" disabled>
                        <i class="ti ti-send me-1"></i>{{ __('Assign to') }} <span class="assign-submit-count">0</span> {{ __('employee(s)') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
