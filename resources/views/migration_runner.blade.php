@extends('layouts.admin')

@section('page-title')
    {{ __('Run Grievance Migrations') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h4 class="mb-0">
                        <i class="ti ti-database me-2"></i>
                        {{ __('Grievance Module Setup') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5>{{ __('Database Tables Required') }}</h5>
                        <p>{{ __('The grievance module requires database tables to be created before use. Click the button below to run the migrations.') }}</p>
                    </div>

                    <div class="text-center">
                        <button onclick="runMigrations()" class="btn btn-primary btn-lg">
                            <i class="ti ti-database me-2"></i>
                            {{ __('Create Grievance Tables') }}
                        </button>
                    </div>

                    <div id="migration-output" class="mt-4" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h6>{{ __('Migration Output') }}</h6>
                            </div>
                            <div class="card-body">
                                <pre id="output-text" style="height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;"></pre>
                            </div>
                        </div>
                    </div>

                    <div id="success-message" class="mt-4" style="display: none;">
                        <div class="alert alert-success">
                            <h5>{{ __('✅ Setup Complete!') }}</h5>
                            <p>{{ __('Grievance module is now ready. You can access it using the links below:') }}</p>
                            <div class="mt-3">
                                <a href="{{ route('grievances.index') }}" class="btn btn-primary me-2">
                                    <i class="ti ti-list me-1"></i>
                                    {{ __('View Grievances') }}
                                </a>
                                <a href="{{ route('grievances.create') }}" class="btn btn-success me-2">
                                    <i class="ti ti-plus me-1"></i>
                                    {{ __('Raise Grievance') }}
                                </a>
                                <a href="{{ route('dashboard') }}" class="btn btn-info">
                                    <i class="ti ti-home me-1"></i>
                                    {{ __('Dashboard') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function runMigrations() {
            const outputDiv = document.getElementById('migration-output');
            const outputText = document.getElementById('output-text');
            const successDiv = document.getElementById('success-message');
            
            outputDiv.style.display = 'block';
            outputText.textContent = 'Starting migrations...\n';
            
            fetch('{{ route("run.migrations") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                outputText.textContent = data.output;
                
                if (data.success) {
                    successDiv.style.display = 'block';
                }
            })
            .catch(error => {
                outputText.textContent += '\n\nError: ' + error.message;
            });
        }
    </script>
@endpush
