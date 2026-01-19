@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>{{ $lable }}</h2>
            <p class="text-secondary mb-0">System logs and communication history</p>
        </div>
        <div>
            <span class="badge bg-light text-secondary border">
                <i class="bi bi-info-circle me-1"></i> {{ count($log) }} entries
            </span>
        </div>
    </div>
</div>

<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="card-title mb-0">History Log</h5>
        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-funnel text-secondary"></i></span>
            <input type="text" id="logSearch" class="form-control border-start-0" placeholder="Filter log data...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" id="logTable">
            <thead>
                <tr>
                    <th style="width: 80px">ID</th>
                    <th>Request Details</th>
                    <th>Raw Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $d)
                    <tr>
                        <td class="font-medium text-secondary">#{{ $d->id }}</td>
                        <td>
                            <div class="bg-light p-2 rounded border-start border-4 border-primary mb-1">
                                <code class="text-xs text-primary">{{ $d->url }}</code>
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate text-secondary small" style="max-width: 400px;" title="{{ $d->data }}">
                                {{ $d->data }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-secondary">
                            <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                            No logs found
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
        var table = $('#logTable').DataTable({
            "dom": 'rtip',
            "pageLength": 25,
            "language": {
                "emptyTable": "No log records found"
            }
        });

        $('#logSearch').on('keyup', function() {
            table.search($(this).val()).draw();
        });
    });
</script>
@endpush
