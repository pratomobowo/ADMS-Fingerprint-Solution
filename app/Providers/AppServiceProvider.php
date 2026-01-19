<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Observers\AttendanceObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        
        // Register AttendanceObserver
        Attendance::observe(AttendanceObserver::class);
    }
}
