<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMS Server - Minimalist Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="{{ asset('css/style-custom.css') }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <i class="bi bi-fingerprint me-2"></i>
                ADMS
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-3"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <!-- Devices Group -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('devices.index', 'devices.DeviceLog', 'devices.FingerLog') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-hdd-network me-1"></i> Devices
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('devices.index') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                                    <i class="bi bi-list-ul me-2"></i>Overview
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('devices.DeviceLog') ? 'active' : '' }}" href="{{ route('devices.DeviceLog') }}">
                                    <i class="bi bi-activity me-2"></i>Device Logs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('devices.FingerLog') ? 'active' : '' }}" href="{{ route('devices.FingerLog') }}">
                                    <i class="bi bi-clock-history me-2"></i>Finger Logs
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Attendance -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('devices.Attendance') ? 'active' : '' }}" href="{{ route('devices.Attendance') }}">
                            <i class="bi bi-calendar-check me-1"></i> Attendance
                        </a>
                    </li>

                    <!-- System Group (API & Users) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/*') || request()->routeIs('api.docs') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i> System
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('api.docs') ? 'active' : '' }}" href="{{ route('api.docs') }}">
                                    <i class="bi bi-book me-2"></i>API Documentation
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.tokens') ? 'active' : '' }}" href="{{ route('admin.tokens') }}">
                                    <i class="bi bi-key me-2"></i>Token Management
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                                    <i class="bi bi-people me-2"></i>User Management
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="navbar-text d-none d-lg-flex align-items-center text-secondary small">
                        <i class="bi bi-clock me-2"></i>
                        <span id="realtimeClock">{{ now()->format('H:i:s') }}</span>
                    </div>
                    
                    @auth
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        @yield('content')
    </main>

    <footer class="container py-4 text-center">
        <p class="text-secondary small mb-0">ADMS Solution &copy; {{ date('Y') }} • Crafted with simplicity</p>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- DataTables Buttons for Export -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    @stack('scripts')
    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('realtimeClock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
    </script>
</body>
</html>