<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ADMS Server</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/style-custom.css') }}" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #718096;
            font-size: 0.875rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                <h1>ADMS Server</h1>
                <p>Masuk ke dashboard admin</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" value="{{ old('email') }}" placeholder="admin@adms.local" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small text-secondary" for="remember">
                            Ingat saya
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-secondary">ADMS Fingerprint Solution v1.0</small>
            </div>
        </div>
    </div>
</body>
</html>
