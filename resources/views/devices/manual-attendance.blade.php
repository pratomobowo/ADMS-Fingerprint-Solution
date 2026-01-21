@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-keyboard me-2"></i>Input Absensi Manual</h2>
            <p class="text-secondary mb-0">Form untuk menambahkan data absensi secara manual</p>
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

<!-- SECTION 1: ORIGINAL MANUAL INPUT FORM -->
<div class="card mb-5">
    <h5 class="card-header bg-white py-3">
        <i class="bi bi-person-plus me-2 text-primary"></i>Form Absensi Manual
    </h5>
    <div class="card-body">
        <form action="{{ route('manual.attendance.store') }}" method="POST">
            @csrf
            
            <div class="row g-4">
                <!-- Employee ID -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">ID Karyawan <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" placeholder="Masukkan ID karyawan" inputmode="numeric" required>
                    <div class="form-text">Nomor ID yang terdaftar di sistem.</div>
                </div>
                
                <!-- Device -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Perangkat <span class="text-danger">*</span></label>
                    <select name="sn" class="form-select" required>
                        <option value="">-- Pilih Perangkat --</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->no_sn }}" {{ ($selectedDevice ?? '') == $device->no_sn ? 'selected' : '' }}>
                                {{ $device->nama ?? 'Unnamed' }} ({{ $device->no_sn }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Date -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Tanggal Absen <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="date" name="check_date" id="attendance_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('attendance_date').value = '{{ now()->format('Y-m-d') }}'">
                            Today
                        </button>
                    </div>
                </div>
                
                <!-- Time -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Jam Absen <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="time" name="check_time" id="attendance_time" class="form-control" value="{{ now()->format('H:i') }}" required>
                        <button class="btn btn-outline-secondary" type="button" id="btn_now">
                            Now
                        </button>
                    </div>
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Status (S1) <span class="text-danger">*</span></label>
                    <select name="status1" class="form-select" required>
                        <option value="0">0 - Check In</option>
                        <option value="1">1 - Check Out</option>
                        <option value="2">2 - Break Out</option>
                        <option value="3">3 - Break In</option>
                        <option value="4">4 - OT In</option>
                        <option value="5">5 - OT Out</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Metode (S2) <span class="text-danger">*</span></label>
                    <select name="status2" class="form-select" required>
                        <option value="1">1 - Fingerprint</option>
                        <option value="15">15 - Face Recognition</option>
                        <option value="2">2 - Password</option>
                        <option value="3">3 - Card</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Data Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SECTION 2: NEW EDIT HISTORY SECTION -->
<div class="card mb-5">
    <h5 class="card-header bg-white py-3">
        <i class="bi bi-pencil-square me-2 text-primary"></i>Koreksi / Edit History Absen
    </h5>
    <div class="card-body">
        
            <!-- Search Bar -->
            <form action="{{ route('manual.attendance') }}" method="GET" class="row align-items-end g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-secondary">CARI ID KARYAWAN</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="employee_id" class="form-control" placeholder="Contoh: 191" value="{{ $employee_id ?? '' }}" inputmode="numeric" required>
                        <button class="btn btn-primary" type="submit">Cari History</button>
                    </div>
                    <div class="form-text">Cari ID karyawan untuk melihat dan mengedit data lama.</div>
                </div>
            </form>
            
            <!-- Result Table -->
            @if(isset($history))
                @if($history->count() > 0)
                    <div class="alert alert-light border shadow-sm mb-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div>
                            Menampilkan history <strong>1 Bulan Terakhir</strong> untuk ID: <strong>{{ $employee_id }}</strong>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal & Jam</th>
                                    <th>Status</th>
                                    <th class="d-none d-sm-table-cell">Source</th>
                                    <th class="d-none d-md-table-cell">Device</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $h)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ \Carbon\Carbon::parse($h->timestamp)->format('d M Y') }}</div>
                                            <span class="font-monospace text-primary">{{ \Carbon\Carbon::parse($h->timestamp)->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusLabels = [0=>'Check In', 1=>'Check Out', 2=>'Break Out', 3=>'Break In', 4=>'OT In', 5=>'OT Out'];
                                            @endphp
                                            <span class="badge bg-light text-dark border">
                                                {{ $statusLabels[$h->status1] ?? $h->status1 }}
                                            </span>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            @if($h->stamp == 'MANUAL')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-keyboard"></i> Manual</span>
                                            @else
                                                <span class="badge bg-light text-secondary">Mesin</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted d-none d-md-table-cell">{{ $h->sn }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $h->id }}">
                                                <i class="bi bi-pencil-square"></i> <span class="d-none d-sm-inline">Edit Waktu</span>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $h->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('manual.attendance.update', $h->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Jam Absen</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label text-muted small">Waktu Saat Ini</label>
                                                            <input type="text" class="form-control-plaintext fw-bold" value="{{ $h->timestamp }}" readonly>
                                                        </div>
                                                        <hr>
                                                        <div class="row g-2">
                                                            <div class="col-7">
                                                                <label class="form-label fw-semibold">Tanggal Baru</label>
                                                                <input type="date" name="check_date" class="form-control" value="{{ \Carbon\Carbon::parse($h->timestamp)->format('Y-m-d') }}" required>
                                                            </div>
                                                            <div class="col-5">
                                                                <label class="form-label fw-semibold">Jam Baru</label>
                                                                <input type="time" name="check_time" class="form-control" value="{{ \Carbon\Carbon::parse($h->timestamp)->format('H:i') }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light w-100 w-sm-auto mb-2 mb-sm-0" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary w-100 w-sm-auto">Simpan</button>
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
                <div class="text-center py-4 text-muted border rounded bg-light">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    Belum ada data absensi dalam 30 hari terakhir untuk ID ini.
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('btn_now').addEventListener('click', function() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('attendance_time').value = `${hours}:${minutes}`;
    });
</script>
@endpush
