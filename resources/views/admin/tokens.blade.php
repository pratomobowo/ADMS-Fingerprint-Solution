@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>API Token Management</h2>
            <p class="text-secondary mb-0">Generate and manage API tokens for external integrations</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTokenModal">
                <i class="bi bi-plus-circle me-1"></i> New Token
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('newToken'))
    <div class="alert alert-warning mb-4">
        <i class="bi bi-key me-2"></i>
        <strong>Simpan token ini sekarang!</strong> Token tidak akan ditampilkan lagi.
        <div class="mt-2">
            <code class="bg-dark text-light p-2 rounded d-block" style="word-break: break-all;">{{ session('newToken') }}</code>
        </div>
    </div>
@endif

<div class="card">
    <h5 class="card-title"><i class="bi bi-key-fill text-warning me-2"></i>Active Tokens</h5>
    
    <div class="table-responsive">
        <table class="table" id="tokensTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Token (Masked)</th>
                    <th>Status</th>
                    <th>Expires</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tokens as $token)
                <tr>
                    <td class="fw-semibold">{{ $token->name }}</td>
                    <td>
                        <code class="text-secondary">{{ substr($token->token, 0, 8) }}...{{ substr($token->token, -8) }}</code>
                    </td>
                    <td>
                        @if($token->is_active)
                            @if($token->expires_at && \Carbon\Carbon::parse($token->expires_at)->isPast())
                                <span class="badge bg-warning">Expired</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Revoked</span>
                        @endif
                    </td>
                    <td class="text-secondary small">
                        {{ $token->expires_at ? \Carbon\Carbon::parse($token->expires_at)->format('M d, Y') : 'Never' }}
                    </td>
                    <td class="text-secondary small">
                        {{ $token->last_used_at ? \Carbon\Carbon::parse($token->last_used_at)->diffForHumans() : 'Never' }}
                    </td>
                    <td class="text-secondary small">
                        {{ \Carbon\Carbon::parse($token->created_at)->format('M d, Y H:i') }}
                    </td>
                    <td class="text-end">
                        @if($token->is_active)
                            <form action="{{ route('admin.tokens.revoke', $token->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Revoke token ini?')">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.tokens.delete', $token->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus token ini secara permanen?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-secondary">
                        <i class="bi bi-key fs-1 d-block mb-3"></i>
                        Belum ada token. Klik "New Token" untuk membuat token API pertama.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Token Modal -->
<div class="modal fade" id="createTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tokens.create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New API Token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Token Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. HR-System-Production" required>
                        <div class="form-text">Nama untuk mengidentifikasi token ini (misal: nama aplikasi eksternal)</div>
                    </div>
                    <div class="mb-3">
                        <label for="expires_days" class="form-label fw-semibold">Expires In (Days)</label>
                        <input type="number" class="form-control" id="expires_days" name="expires_days" value="365" min="1" max="3650">
                        <div class="form-text">Token akan kedaluwarsa setelah jumlah hari ini. Kosongkan untuk 1 tahun.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Generate Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
