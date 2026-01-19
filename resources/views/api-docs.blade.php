@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="print-title">API Documentation</h2>
            <p class="text-secondary mb-0">Complete guide for HR system integration</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary border">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
            <span class="badge bg-success d-flex align-items-center">
                <i class="bi bi-check-circle me-1"></i> API Ready
            </span>
        </div>
    </div>
</div>

<!-- Quick Start -->
<div class="card mb-4">
    <h5 class="card-title"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Start</h5>
    <p class="text-secondary">Gunakan API ini untuk mengintegrasikan data absensi dengan aplikasi SDM/HR Anda.</p>
    
    <div class="bg-light p-3 rounded-3 border">
        <code class="text-primary d-block mb-2">Base URL: <strong>{{ url('/api/v1') }}</strong></code>
        <code class="text-primary">Authentication: <strong>Bearer Token</strong></code>
    </div>
</div>

<!-- Authentication -->
<div class="card mb-4">
    <h5 class="card-title"><i class="bi bi-shield-lock text-primary me-2"></i>Authentication</h5>
    <p class="text-secondary mb-3">Semua request API membutuhkan header Authorization dengan format Bearer Token.</p>
    
    <div class="bg-dark text-light p-3 rounded-3 mb-3">
        <pre class="mb-0"><code>curl -X GET "{{ url('/api/v1/hr/attendances') }}" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code></pre>
    </div>
    
    <div class="no-print">
        <h6 class="mt-4 mb-3">Active API Tokens</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Expires</th>
                        <th>Last Used</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tokens as $token)
                    <tr>
                        <td><code>{{ $token->name }}</code></td>
                        <td>
                            @if($token->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-secondary small">{{ $token->expires_at ? \Carbon\Carbon::parse($token->expires_at)->format('M d, Y') : 'Never' }}</td>
                        <td class="text-secondary small">{{ $token->last_used_at ? \Carbon\Carbon::parse($token->last_used_at)->diffForHumans() : 'Never' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-3">No tokens created yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="print-only alert alert-info mb-0" style="display: none;">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Token Information:</strong> Untuk mendapatkan API token, silahkan hubungi administrator sistem.
    </div>
</div>

<!-- Endpoints -->
<div class="card mb-4">
    <h5 class="card-title"><i class="bi bi-signpost-2 text-success me-2"></i>API Endpoints</h5>
    
    <!-- GET Attendances -->
    <div class="border rounded-3 p-3 mb-3">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-primary me-2">GET</span>
            <code class="fs-6">/api/v1/hr/attendances</code>
        </div>
        <p class="text-secondary small mb-3">Mengambil daftar data absensi dengan filter tanggal.</p>
        
        <h6 class="small text-uppercase text-secondary">Parameters</h6>
        <table class="table table-sm">
            <thead>
                <tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr>
            </thead>
            <tbody>
                <tr><td><code>start_date</code></td><td>date</td><td><span class="badge bg-danger">Yes</span></td><td>Tanggal awal (YYYY-MM-DD)</td></tr>
                <tr><td><code>end_date</code></td><td>date</td><td><span class="badge bg-danger">Yes</span></td><td>Tanggal akhir (YYYY-MM-DD)</td></tr>
                <tr><td><code>employee_id</code></td><td>string</td><td><span class="badge bg-secondary">No</span></td><td>Filter by employee ID</td></tr>
                <tr><td><code>device_sn</code></td><td>string</td><td><span class="badge bg-secondary">No</span></td><td>Filter by device serial</td></tr>
                <tr><td><code>limit</code></td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>Max results (default: 100)</td></tr>
                <tr><td><code>offset</code></td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>Pagination offset</td></tr>
            </tbody>
        </table>
        
        <h6 class="small text-uppercase text-secondary mt-3">Example Request</h6>
        <div class="bg-dark text-light p-3 rounded-3">
            <pre class="mb-0"><code>curl -X GET "{{ url('/api/v1/hr/attendances') }}?start_date=2026-01-01&end_date=2026-01-31&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"</code></pre>
        </div>
        
        <h6 class="small text-uppercase text-secondary mt-3">Example Response</h6>
        <div class="bg-light p-3 rounded-3 border">
            <pre class="mb-0"><code>{
  "success": true,
  "data": [
    {
      "id": 564,
      "employee_id": 283,
      "timestamp": "2026-01-18T18:57:11+07:00",
      "device_sn": "SPK7245000764",
      "status": {
        "status1": true,
        "status2": false,
        "status3": false,
        "status4": false,
        "status5": false
      },
      "created_at": "2026-01-18T18:57:19+07:00"
    }
  ],
  "meta": {
    "total": 523,
    "count": 10,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 53
  }
}</code></pre>
        </div>
    </div>
    
    <!-- GET Single Attendance -->
    <div class="border rounded-3 p-3 mb-3">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-primary me-2">GET</span>
            <code class="fs-6">/api/v1/hr/attendances/{id}</code>
        </div>
        <p class="text-secondary small mb-0">Mengambil detail absensi berdasarkan ID.</p>
    </div>
    
    <!-- GET By Employee -->
    <div class="border rounded-3 p-3">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-primary me-2">GET</span>
            <code class="fs-6">/api/v1/hr/attendances/employee/{employee_id}</code>
        </div>
        <p class="text-secondary small mb-0">Mengambil semua absensi untuk karyawan tertentu.</p>
    </div>
</div>

<!-- Webhooks -->
<div class="card mb-4">
    <h5 class="card-title"><i class="bi bi-arrow-repeat text-info me-2"></i>Webhooks (Realtime Push)</h5>
    <p class="text-secondary mb-3">Sistem dapat mengirimkan notifikasi otomatis ke URL Anda setiap ada data absensi baru masuk.</p>
    
    <h6 class="small text-uppercase text-secondary">Payload Format</h6>
    <div class="bg-light p-3 rounded-3 border">
        <pre class="mb-0"><code>{
  "event": "attendance.created",
  "timestamp": "2026-01-19T10:00:00+07:00",
  "data": {
    "id": 999,
    "employee_id": "123",
    "timestamp": "2026-01-19T09:55:00+07:00",
    "device_sn": "SPK7245000764",
    "status": { ... }
  }
}</code></pre>
    </div>
    
    <div class="alert alert-info mt-3 mb-0">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Security:</strong> Webhook dilengkapi dengan HMAC-SHA256 signature di header <code>X-Webhook-Signature</code> untuk verifikasi.
    </div>
</div>

<!-- Error Codes -->
<div class="card">
    <h5 class="card-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Error Codes</h5>
    
    <table class="table table-sm mb-0">
        <thead>
            <tr><th>HTTP Code</th><th>Error Code</th><th>Description</th></tr>
        </thead>
        <tbody>
            <tr><td><code>401</code></td><td>UNAUTHORIZED</td><td>Token tidak valid atau expired</td></tr>
            <tr><td><code>403</code></td><td>FORBIDDEN</td><td>Token tidak memiliki akses</td></tr>
            <tr><td><code>404</code></td><td>RESOURCE_NOT_FOUND</td><td>Data tidak ditemukan</td></tr>
            <tr><td><code>422</code></td><td>VALIDATION_ERROR</td><td>Parameter tidak valid</td></tr>
            <tr><td><code>429</code></td><td>RATE_LIMITED</td><td>Terlalu banyak request (60/menit)</td></tr>
            <tr><td><code>500</code></td><td>SERVER_ERROR</td><td>Internal server error</td></tr>
        </tbody>
    </table>
</div>
@endsection
