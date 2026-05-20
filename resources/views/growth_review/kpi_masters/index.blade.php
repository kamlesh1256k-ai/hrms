@extends('layouts.admin')
@section('page-title') {{ __('KPI Masters') }} — {{ $label }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('KPI Masters') }}</li>
    <li class="breadcrumb-item">{{ $label }}</li>
@endsection
@section('action-button')
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMasterModal"><i class="ti ti-plus me-1"></i>{{ __('Add') }} {{ $label }}</button>
@endsection

@push('css-page')
<style>
    .master-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;}
    .master-tabs a{padding:8px 16px;border-radius:10px;background:#f1f5f9;color:#475569;font-size:.82rem;font-weight:600;text-decoration:none;}
    .master-tabs a.active{background:#ef4444;color:#fff;}
    .master-tabs a:hover{background:#e2e8f0;}
    .master-tabs a.active:hover{background:#dc2626;color:#fff;}
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="master-tabs">
        @foreach($masters as $slug => $info)
            <a href="{{ route('growth-review.masters.index', $slug) }}" class="{{ $slug == $master ? 'active' : '' }}">{{ $info[1] }}</a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="60">#</th>
                            <th>{{ __('Name') }}</th>
                            <th width="120">{{ __('Sort Order') }}</th>
                            <th width="100">{{ __('Status') }}</th>
                            <th width="140">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td>{{ $row->sort_order }}</td>
                            <td>
                                @if($row->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info edit-btn"
                                    data-id="{{ $row->id }}"
                                    data-name="{{ $row->name }}"
                                    data-sort="{{ $row->sort_order }}"
                                    data-active="{{ $row->is_active }}"
                                    data-bs-toggle="modal" data-bs-target="#editMasterModal"><i class="ti ti-edit"></i></button>
                                <form action="{{ route('growth-review.masters.destroy', [$master, $row->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this item?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No items yet. Click "Add" to create one.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal fade" id="addMasterModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('growth-review.masters.store', $master) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">{{ __('Add') }} {{ $label }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">{{ __('Name') }} *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">{{ __('Sort Order') }}</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                        <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="addActive" checked><label class="form-check-label" for="addActive">{{ __('Active') }}</label></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Save') }}</button></div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editMasterModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="editMasterForm">
                @csrf @method('PUT')
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">{{ __('Edit') }} {{ $label }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">{{ __('Name') }} *</label><input type="text" name="name" id="editName" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">{{ __('Sort Order') }}</label><input type="number" name="sort_order" id="editSort" class="form-control"></div>
                        <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive"><label class="form-check-label" for="editActive">{{ __('Active') }}</label></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Update') }}</button></div>
                </div>
            </form>
        </div>
    </div>

    @push('script-page')
    <script>
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const name = this.dataset.name;
            const sort = this.dataset.sort;
            const active = this.dataset.active;
            document.getElementById('editMasterForm').action = "{{ url('growth-review/masters/'.$master) }}/" + id;
            document.getElementById('editName').value = name;
            document.getElementById('editSort').value = sort;
            document.getElementById('editActive').checked = (active === '1' || active === 1);
        });
    });
    </script>
    @endpush
@endsection
