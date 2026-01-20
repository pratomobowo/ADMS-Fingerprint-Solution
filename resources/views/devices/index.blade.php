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
                    <th>IP Address</th>
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
                                <div>
                                    <div class="font-semibold">{{ $d->nama ?? 'Unnamed Device' }}</div>
                                    <div class="text-secondary small">{{ $d->no_sn }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($d->ip_address)
                                <code class="small">{{ $d->ip_address }}</code>
                            @else
                                <span class="text-secondary small">-</span>
                            @endif
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
                            <button type="button" class="btn btn-sm btn-outline-primary me-1 ping-btn" data-device-id="{{ $d->id }}" title="Test Connection">
                                <i class="bi bi-wifi"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#editDeviceModal{{ $d->id }}" title="Edit Alias">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="{{ url('/devices-log?sn='.$d->no_sn) }}" class="btn btn-sm btn-light border" title="View Device Logs">
                                <i class="bi bi-eye text-secondary"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- Edit Device Modal -->
                    <div class="modal fade" id="editDeviceModal{{ $d->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content text-start">
                                <form action="{{ route('devices.update', $d->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Device Alias</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Serial Number</label>
                                            <input type="text" class="form-control bg-light" value="{{ $d->no_sn }}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Device Alias (Name)</label>
                                            <input type="text" name="nama" class="form-control" value="{{ $d->nama }}" placeholder="e.g. Finger R. Rapat" required>
                                            <div class="form-text">Gunakan nama yang mudah dikenali (Akses Pintu Depan, dsb).</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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
            "order": [[2, "desc"]], // Sort by status column
            "language": {
                "emptyTable": "No devices available"
            }
        });

        $('#deviceSearch').on('keyup', function() {
            table.search($(this).val()).draw();
        });

        // Ping button handler
        $(document).on('click', '.ping-btn', function() {
            var btn = $(this);
            var deviceId = btn.data('device-id');
            var originalHtml = btn.html();
            
            // Show loading state
            btn.prop('disabled', true);
            btn.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
            
            $.ajax({
                url: '/devices/' + deviceId + '/ping',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    var alertClass = response.success ? 'alert-success' : 'alert-warning';
                    var icon = response.success ? 'bi-check-circle' : 'bi-exclamation-triangle';
                    
                    var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                        '<i class="bi ' + icon + ' me-2"></i>' +
                        '<strong>' + (response.device ? response.device.serial : 'Device') + '</strong>: ' + response.ping_result;
                    
                    if (response.device && response.device.ip) {
                        html += ' <span class="badge bg-secondary">' + response.device.ip + '</span>';
                    }
                    
                    if (response.device && response.device.last_seen) {
                        html += '<br><small class="text-muted">Last seen: ' + response.device.last_seen + '</small>';
                    }
                    
                    html += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    
                    // Show result at top of card
                    $('.card').first().prepend(html);
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to ping device';
                    alert('Error: ' + msg);
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            });
        });
    });
</script>
@endpush

