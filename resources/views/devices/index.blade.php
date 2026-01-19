@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Devices</h2>
            <p class="text-secondary mb-0">Manage and monitor your biometric devices</p>
        </div>
        <div>
            <span class="badge badge-online">
                <i class="bi bi-broadcast me-1"></i> System Online
            </span>
        </div>
    </div>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-indicator" style="color: var(--accent)">
            <i class="bi bi-hdd-network fs-4"></i>
        </div>
        <div class="stat-label mt-2">Total Devices</div>
        <div class="stat-value">{{ $log->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-indicator" style="color: var(--success)">
            <i class="bi bi-check-circle fs-4"></i>
        </div>
        <div class="stat-label mt-2">Active Status</div>
        <div class="stat-value">{{ $log->where('online', '>=', now()->subMinutes(60))->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-indicator" style="color: var(--warning)">
            <i class="bi bi-clock-history fs-4"></i>
        </div>
        <div class="stat-label mt-2">Last Sync</div>
        <div class="stat-value text-sm">{{ $log->max('online') ? \Carbon\Carbon::parse($log->max('online'))->diffForHumans() : '-' }}</div>
    </div>
</div>

<!-- Devices Table -->
<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="card-title mb-0">Device List</h5>
        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
            <input type="text" id="deviceSearch" class="form-control border-start-0" placeholder="Search serial...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table" id="devicesTable">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>Status</th>
                    <th>Last Online</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $d)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-2 rounded-3 me-3">
                                    <i class="bi bi-device-hdd text-secondary"></i>
                                </div>
                                <span class="font-semibold">{{ $d->no_sn }}</span>
                            </div>
                        </td>
                        <td>
                            @if($d->online && \Carbon\Carbon::parse($d->online)->gt(now()->subMinutes(30)))
                                <span class="badge badge-online">Online</span>
                            @else
                                <span class="badge badge-offline">Offline</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-secondary small">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $d->online ? \Carbon\Carbon::parse($d->online)->format('M d, H:i') : 'Never' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ url('/devices-log?sn='.$d->no_sn) }}" class="btn btn-sm btn-light border" title="View Device Logs">
                                <i class="bi bi-eye text-secondary"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            No devices found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#devicesTable').DataTable({
            "dom": '<"top"i>rt<"bottom"lp><"clear">',
            "pageLength": 10,
            "order": [[1, "desc"]], // Sort by status or serial
            "language": {
                "emptyTable": "No devices available"
            }
        });

        $('#deviceSearch').on('keyup', function() {
            table.search($(this).val()).draw();
        });
    });
</script>
@endpush
