@extends('layouts.admin')

@section('page-title')
    {{ __('Grievance Details') }}
@endsection

@push('css-page')
    <style>
        .grievance-header {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .response-thread {
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .response-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #6366f1;
            background: white;
        }
        .response-item.employee-reply {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .response-item.internal-note {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .response-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        .response-message {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .response-form {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .anonymous-info {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .action-buttons {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6366f1;
            position: absolute;
            left: -6px;
            top: 20px;
        }
        .timeline-line {
            position: absolute;
            left: -1px;
            top: 32px;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <!-- Grievance Header -->
            <div class="grievance-header">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h3 class="mb-2">{{ $grievance->title }}</h3>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-secondary">{{ $grievance->category }}</span>
                            @if ($grievance->is_anonymous)
                                <span class="badge bg-warning">Anonymous</span>
                            @endif
                            <span class="status-badge bg-{{ $grievance->status_with_color['color'] }}">
                                {{ $grievance->status_with_color['label'] }}
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('Created') }}</div>
                        <div class="fw-bold">{{ $grievance->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>

                @if ($grievance->is_anonymous)
                    <div class="anonymous-info">
                        <i class="ti ti-shield-lock me-2"></i>
                        <strong>{{ __('Anonymous Grievance') }}</strong>
                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small">{{ __('Tracking Token:') }}</span>
                            <code id="anonTokenVal" style="font-size:.95rem;background:#fef3c7;padding:4px 10px;border-radius:6px;">{{ $grievance->anonymous_token }}</code>
                            <button type="button" class="btn btn-sm btn-light border" onclick="(function(){const t=document.getElementById('anonTokenVal').textContent.trim();navigator.clipboard?navigator.clipboard.writeText(t).then(()=>alert('{{ __('Token copied!') }}')):alert('{{ __('Copy this token: ') }}'+t);})()">
                                <i class="ti ti-copy"></i> {{ __('Copy') }}
                            </button>
                            <a href="{{ route('grievances.track') }}" target="_blank" class="btn btn-sm btn-light border">
                                <i class="ti ti-external-link"></i> {{ __('Track from anywhere') }}
                            </a>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="ti ti-alert-triangle"></i>
                            {{ __('Save this token securely — without it, you cannot follow up on this anonymous grievance.') }}
                        </div>
                    </div>
                @endif

                <div class="mt-3">
                    <h6>{{ __('Description') }}</h6>
                    <div class="mt-2 p-3 bg-white rounded border">
                        {{ nl2br(e($grievance->description)) }}
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <small class="text-muted">{{ __('Complainant') }}</small>
                        <div class="fw-bold">{{ $grievance->complainant_display_name }}</div>
                    </div>
                    @if ($grievance->assigned_to)
                        <div class="col-md-6">
                            <small class="text-muted">{{ __('Assigned to') }}</small>
                            <div class="fw-bold">{{ $grievance->assignedTo->name }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Response Thread -->
            <div class="response-thread">
                <h5 class="mb-4">
                    <i class="ti ti-messages me-2"></i>
                    {{ __('Response History') }}
                </h5>
                
                @if ($responses->count() > 0)
                    @foreach ($responses as $index => $response)
                        <div class="response-item {{ $response->response_type === 'employee_reply' ? 'employee-reply' : ($response->is_internal_note ? 'internal-note' : '') }}">
                            <div class="timeline-dot"></div>
                            @if ($index < $responses->count() - 1)
                                <div class="timeline-line"></div>
                            @endif
                            
                            <div class="response-meta">
                                <strong>{{ $response->responder_name }}</strong>
                                <span class="ms-2 badge bg-{{ $response->response_type_with_color['color'] }}">
                                    {{ $response->response_type_with_color['label'] }}
                                </span>
                                @if ($response->is_internal_note)
                                    <span class="ms-2 badge bg-warning">Internal Note</span>
                                @endif
                                <div class="mt-1">
                                    <i class="ti ti-clock me-1"></i>
                                    {{ $response->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                            
                            <div class="response-message">
                                {{ nl2br(e($response->message)) }}
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-message-circle-off" style="font-size: 3rem;"></i>
                        <div class="mt-2">{{ __('No responses yet') }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Action Buttons for HR/Admin -->
            @if (in_array(Auth::user()->type, ['super admin', 'company', 'hr']))
                <div class="action-buttons">
                    <h6 class="mb-3">{{ __('Quick Actions') }}</h6>
                    
                    <!-- Status Update -->
                    <form method="POST" action="{{ route('grievances.update.status', $grievance->id) }}" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-bold">{{ __('Update Status') }}</label>
                            <select name="status" class="form-select form-select-sm">
                                @foreach (\App\Models\Grievance::getStatuses() as $status => $label)
                                    <option value="{{ $status }}" {{ $grievance->status === $status ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        @if (!empty($hrStaff))
                            <div class="mb-2">
                                <label class="form-label small fw-bold">{{ __('Assign to') }}</label>
                                <select name="assigned_to" class="form-select form-select-sm">
                                    <option value="">{{ __('Unassigned') }}</option>
                                    @foreach ($hrStaff as $hr)
                                        <option value="{{ $hr->id }}" {{ $grievance->assigned_to === $hr->id ? 'selected' : '' }}>
                                            {{ $hr->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ti ti-refresh me-1"></i>
                            {{ __('Update') }}
                        </button>
                    </form>
                    
                    <div class="d-grid gap-2">
                        @if ($grievance->isResolved())
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="reopenGrievance()">
                                <i class="ti ti-arrow-back-up me-1"></i>
                                {{ __('Reopen') }}
                            </button>
                        @endif
                        
                        @if (Auth::user()->type === 'super admin')
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGrievance()">
                                <i class="ti ti-trash me-1"></i>
                                {{ __('Delete') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Add Response Form -->
            @if (!$grievance->isResolved() || in_array(Auth::user()->type, ['super admin', 'company', 'hr']))
                <div class="response-form">
                    <h6 class="mb-3">{{ __('Add Response') }}</h6>
                    
                    <form method="POST" action="{{ route('grievances.add.response', $grievance->id) }}">
                        @csrf
                        
                        @if (in_array(Auth::user()->type, ['super admin', 'company', 'hr']))
                            <div class="mb-3">
                                <label class="form-label small fw-bold">{{ __('Response Type') }}</label>
                                <select name="response_type" class="form-select form-select-sm">
                                    <option value="hr_response">{{ __('HR Response') }}</option>
                                    <option value="internal_note">{{ __('Internal Note') }}</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="response_type" value="employee_reply">
                        @endif
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('Message') }}</label>
                            <textarea name="message" class="form-control" rows="4" required
                                      placeholder="{{ __('Type your response here...') }}"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-send me-1"></i>
                            {{ __('Send Response') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Auto-scroll to latest response
        document.addEventListener('DOMContentLoaded', function() {
            const responseThread = document.querySelector('.response-thread');
            if (responseThread) {
                responseThread.scrollTop = responseThread.scrollHeight;
            }
        });

        // Reopen grievance
        function reopenGrievance() {
            if (confirm('{{ __("Are you sure you want to reopen this grievance?") }}')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("grievances.update.status", $grievance->id) }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = 'open';
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete grievance
        function deleteGrievance() {
            if (confirm('{{ __("Are you sure you want to delete this grievance? This action cannot be undone.") }}')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("grievances.destroy", $grievance->id) }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Character counter for response
        const messageTextarea = document.querySelector('textarea[name="message"]');
        if (messageTextarea) {
            messageTextarea.addEventListener('input', function() {
                const length = this.value.length;
                if (length < 10) {
                    this.setCustomValidity('{{ __("Message must be at least 10 characters long") }}');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    </script>
@endpush
