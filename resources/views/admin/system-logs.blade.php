@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-journal-text me-2"></i>System Key Logs</h2>
            <p class="text-secondary mb-0">Monitor communication and raw data from devices</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white border-bottom-0 pb-0">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link {{ $type === 'finger' ? 'active fw-bold' : 'text-secondary' }}" 
                   href="{{ route('admin.logs', ['type' => 'finger']) }}">
                   <i class="bi bi-fingerprint me-1"></i> Finger Raw Data
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $type === 'device' ? 'active fw-bold' : 'text-secondary' }}" 
                   href="{{ route('admin.logs', ['type' => 'device']) }}">
                   <i class="bi bi-router me-1"></i> Device Handshakes
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.logs') }}" class="row g-2 mb-4 align-items-end">
            <input type="hidden" name="type" value="{{ $type }}">
            
            <div class="col-md-3">
                <label class="form-label small text-secondary">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Device</label>
                <select name="sn" class="form-select form-select-sm">
                    <option value="">All Devices</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->no_sn }}" {{ request('sn') == $device->no_sn ? 'selected' : '' }}>
                            {{ $device->nama ?? 'Unnamed' }} ({{ $device->no_sn }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">Search Content</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Keyword..." value="{{ request('search') }}">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-sm btn-primary mt-4"><i class="bi bi-search"></i></button>
            </div>
            @if(request()->hasAny(['start_date', 'end_date', 'sn', 'search']))
            <div class="col-12 mt-2">
                 <a href="{{ route('admin.logs', ['type' => $type]) }}" class="btn btn-sm btn-outline-danger border-0">
                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                 </a>
            </div>
            @endif
        </form>

        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th style="width: 150px">Time</th>
                        @if($type === 'device')
                            <th>Serial Number</th>
                            <th>IP Address</th>
                            <th>Request URL</th>
                        @else
                            <th>Request Info</th>
                            <th>Raw Data Content</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-secondary small">#{{ $log->id }}</td>
                            <td class="text-nowrap small text-secondary">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                            </td>
                            
                            @if($type === 'device')
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $log->sn }}</span>
                                </td>
                                <td>
                                    <!-- Parsing Option param if available -->
                                    <span class="small font-monospace">{{ $log->option ?? '-' }}</span>
                                </td>
                                <td>
                                    <code class="text-primary small d-block text-truncate" style="max-width: 400px;" title="{{ $log->url }}">
                                        {{ $log->url }}
                                    </code>
                                    <div class="small text-muted mt-1">Data: {{ Str::limit($log->data, 50) }}</div>
                                </td>
                            @else
                                <td>
                                    <div class="small mb-1">
                                        <code class="text-bg-light px-2 py-1 rounded">{{ $log->url }}</code>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-monospace small text-secondary text-break bg-light p-2 rounded" style="max-height: 100px; overflow-y: auto;">
                                        {{ $log->data }}
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No logs found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-secondary small">
                Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
