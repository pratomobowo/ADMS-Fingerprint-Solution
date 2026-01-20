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

<div class="card">
    <h5 class="card-title mb-4"><i class="bi bi-person-plus me-2 text-primary"></i>Form Absensi Manual</h5>
    
    <form action="{{ route('manual.attendance.store') }}" method="POST">
        @csrf
        
        <div class="row g-4">
            <!-- Employee ID -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">ID Karyawan <span class="text-danger">*</span></label>
                <input type="text" name="employee_id" class="form-control" placeholder="Masukkan ID karyawan" required>
                <div class="form-text">Nomor ID yang terdaftar di sistem absensi.</div>
            </div>
            
            <!-- Device -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Perangkat <span class="text-danger">*</span></label>
                <select name="sn" class="form-select" required>
                    <option value="">-- Pilih Perangkat --</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->no_sn }}">
                            {{ $device->nama ?? 'Unnamed' }} ({{ $device->no_sn }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Date -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Absen <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="date" name="date" id="attendance_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('attendance_date').value = '{{ now()->format('Y-m-d') }}'">
                        Today
                    </button>
                </div>
            </div>
            
            <!-- Time -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Jam Absen <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="time" name="time" id="attendance_time" class="form-control" value="{{ now()->format('H:i') }}" required>
                    <button class="btn btn-outline-secondary" type="button" id="btn_now">
                        Now
                    </button>
                </div>
            </div>
            
            <!-- S1: Status Check In/Out -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Status (S1) <span class="text-danger">*</span></label>
                <select name="status1" class="form-select" required>
                    <option value="0">0 - Check In</option>
                    <option value="1">1 - Check Out</option>
                    <option value="2">2 - Break Out</option>
                    <option value="3">3 - Break In</option>
                    <option value="4">4 - OT In</option>
                    <option value="5">5 - OT Out</option>
                </select>
                <div class="form-text">Status kehadiran karyawan</div>
            </div>
            
            <!-- S2: Verify Mode -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Metode Verifikasi (S2) <span class="text-danger">*</span></label>
                <select name="status2" class="form-select" required>
                    <option value="1">1 - Fingerprint</option>
                    <option value="15">15 - Face Recognition</option>
                    <option value="2">2 - Password</option>
                    <option value="3">3 - Card</option>
                </select>
                <div class="form-text">Metode verifikasi yang digunakan</div>
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <a href="{{ route('devices.Attendance') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Attendance
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i> Simpan Absensi
            </button>
        </div>
    </form>
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
