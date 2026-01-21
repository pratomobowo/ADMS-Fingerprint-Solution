@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-keyboard me-2"></i>Input Absensi Manual</h2>
            <p class="text-secondary mb-0">Kelola data absensi manual dan koreksi history karyawan</p>
        </div>
        <div>
            <span class="badge bg-secondary">
                <i class="bi bi-shield-lock me-1"></i> Admin Only
            </span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
            <div>{{ session('error') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Search / Add Section -->
    <div class="col-md-4">
        <div class="card mb-4">
            <h5 class="card-header bg-white"><i class="bi bi-search me-2 text-primary"></i>Cari / Input</h5>
            <div class="card-body">
                <!-- Search Form -->
                <form action="{{ route('manual.attendance') }}" method="GET" class="mb-4">
                    <label class="form-label fw-bold small text-secondary">CARI KARYAWAN</label>
                    <div class="input-group">
                        <input type="text" name="employee_id" class="form-control" placeholder="Masukkan ID..." value="{{ $employee_id ?? '' }}" required>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <div class="form-text">Cari untuk melihat history & input baru.</div>
                </form>

                <hr>

                <!-- Add Manual Form -->
                <form action="{{ route('manual.attendance.store') }}" method="POST">
                    @csrf
                    <label class="form-label fw-bold small text-secondary mt-2">TAMBAH ABSEN BARU</label>
                    
                    <div class="mb-3">
                        <label class="form-label">ID Karyawan</label>
                        <input type="text" name="employee_id" class="form-control bg-light" value="{{ $employee_id ?? '' }}" placeholder="ID..." required {{ isset($employee_id) ? 'readonly' : '' }}>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Perangkat</label>
                        <select name="sn" class="form-select" required>
                            @foreach($devices as $device)
                                <option value="{{ $device->no_sn }}" {{ $selectedDevice == $device->no_sn ? 'selected' : '' }}>
                                    {{ $device->nama ?? $device->no_sn }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal & Jam</label>
                        <input type="date" name="check_date" class="form-control mb-2" value="{{ now()->format('Y-m-d') }}" required>
                        <input type="time" name="check_time" class="form-control" value="{{ now()->format('H:i') }}" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i> Simpan Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- History / List Section -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>History Absensi (1 Bulan)</h5>
                @if(isset($employee_id))
                    <span class="badge bg-info text-dark">ID: {{ $employee_id }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if(isset($history) && $history->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu Absen</th>
                                    <th>Status (S1)</th>
                                    <th>Metode</th>
                                    <th>Device</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $h)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ \Carbon\Carbon::parse($h->timestamp)->format('d M Y') }}</div>
                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($h->timestamp)->format('H:i:s') }}</div>
                                        </td>
                                        <td>
                                            @php
                                                $statusLabels = [0=>'Check In', 1=>'Check Out', 2=>'Break Out', 3=>'Break In', 4=>'OT In', 5=>'OT Out'];
                                            @endphp
                                            <span class="badge bg-light text-dark border">
                                                {{ $statusLabels[$h->status1] ?? 'Unknown ('.$h->status1.')' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($h->stamp == 'MANUAL')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-keyboard"></i> Manual</span>
                                            @else
                                                <span class="badge bg-light text-secondary"><i class="bi bi-cpu"></i> Mesin</span>
                                            @endif
                                        </td>
                                        <td class="small text-secondary">{{ $h->sn }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $h->id }}">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $h->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form action="{{ route('manual.attendance.update', $h->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Waktu Absen</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info small py-2">
                                                            Mengubah data ID: <strong>{{ $h->id }}</strong> (Original: {{ $h->timestamp }})
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Tanggal Baru</label>
                                                            <input type="date" name="check_date" class="form-control" value="{{ \Carbon\Carbon::parse($h->timestamp)->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jam Baru</label>
                                                            <input type="time" name="check_time" class="form-control" value="{{ \Carbon\Carbon::parse($h->timestamp)->format('H:i') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        @if(isset($employee_id))
                            <i class="bi bi-calendar-x fs-1 text-muted opacity-25"></i>
                            <p class="text-secondary mt-2">Tidak ada data absensi 1 bulan terakhir untuk ID: {{ $employee_id }}</p>
                        @else
                            <i class="bi bi-search fs-1 text-muted opacity-25"></i>
                            <p class="text-secondary mt-2">Cari ID Karyawan untuk melihat history.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
