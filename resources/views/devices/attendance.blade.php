@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Attendance</h2>
            <p class="text-secondary mb-0">Monitor real-time employee attendance logs</p>
        </div>
        <div class="d-flex gap-2" id="exportButtonContainer">
            <!-- DataTables buttons will be moved here -->
        </div>
    </div>
</div>

<div class="card">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h5 class="card-title mb-0">Logs Records</h5>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm w-auto">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                <input type="text" id="attendanceSearch" class="form-control border-start-0" placeholder="Search logs...">
            </div>
            <select class="form-select form-select-sm w-auto" id="statusFilter">
                <option value="">All Status</option>
                <option value="1">Checked In</option>
                <option value="0">Checked Out</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" id="attendanceTable">
            <thead>
                <tr>
                    <th>Record</th>
                    <th>Device SN</th>
                    <th>Employee</th>
                    <th>Timestamp</th>
                    <th class="text-center">S1</th>
                    <th class="text-center">S2</th>
                    <th class="text-center">S3</th>
                    <th class="text-center">S4</th>
                    <th class="text-center">S5</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td class="font-medium text-secondary">#{{ $attendance->id }}</td>
                        <td>
                            @if($attendance->device_name)
                                <div class="font-medium">{{ $attendance->device_name }}</div>
                                <div class="text-secondary text-xs">{{ $attendance->sn }}</div>
                            @else
                                <span class="text-xs border px-2 py-1 rounded bg-light font-mono">
                                    {{ $attendance->sn }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    {{ substr($attendance->employee_id, -1) }}
                                </div>
                                <span class="font-semibold">{{ $attendance->employee_id }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="font-medium">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('H:i:s') }}</span>
                                <span class="text-secondary text-xs">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('M d, Y') }}</span>
                            </div>
                        </td>
                        @foreach(['status1', 'status2', 'status3', 'status4', 'status5'] as $status)
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $attendance->$status ?? '-' }}</span>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">
                            <i class="bi bi-database-exclamation fs-1 d-block mb-3"></i>
                            No attendance records found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attendances->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable (client-side for the current page)
        var table = $('#attendanceTable').DataTable({
            "dom": 'Brtip',
            "buttons": [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-sm btn-light border me-2'
                },
                {
                    extend: 'csv',
                    text: '<i class="bi bi-file-earmark-text me-1"></i> CSV',
                    className: 'btn btn-sm btn-light border'
                }
            ],
            "pageLength": 15,
            "paging": false, // Disable DataTables pagination since we use Laravel's
            "info": false,
            "ordering": false // Preserve server-side ordering (Laravel DESC)
        });

        // Hide DataTables default buttons and move them to our custom container
        table.buttons().container().appendTo('#exportButtonContainer');

        $('#attendanceSearch').on('keyup', function() {
            table.search($(this).val()).draw();
        });

        $('#statusFilter').on('change', function() {
            var val = $(this).val();
            // This is a bit tricky for server-side paginated data, 
            // but we'll filter what's currently on the screen.
            table.column(4).search(val ? 'Active' : '').draw(); // Example mapping to S1
        });
    });
</script>
@endpush