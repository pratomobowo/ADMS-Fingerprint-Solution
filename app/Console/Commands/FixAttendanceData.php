<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAttendanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fix-data {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix old attendance data where status1/status2 were incorrectly mapped';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('=== Attendance Data Fix Tool ===');
        $this->newLine();
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }
        
        // Count records to fix
        $count15 = DB::table('attendances')
            ->where('status1', 15)
            ->where('status2', 0)
            ->count();
            
        $count1 = DB::table('attendances')
            ->where('status1', 1)
            ->where('status2', 0)
            ->count();
        
        $this->info("Records with status1=15, status2=0: {$count15}");
        $this->info("Records with status1=1, status2=0: {$count1}");
        $this->newLine();
        
        if ($count15 == 0 && $count1 == 0) {
            $this->info('No records need fixing. All data is already correct!');
            return 0;
        }
        
        if (!$dryRun) {
            if (!$this->confirm('Do you want to proceed with fixing the data?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            // Fix status1=15 (Face verify mode in wrong column)
            $fixed15 = DB::table('attendances')
                ->where('status1', 15)
                ->where('status2', 0)
                ->update([
                    'status2' => 15,
                    'status1' => 0
                ]);
            $this->info("Fixed {$fixed15} records (status1=15 -> status2=15)");
            
            // Fix status1=1 (Finger verify mode in wrong column)
            $fixed1 = DB::table('attendances')
                ->where('status1', 1)
                ->where('status2', 0)
                ->update([
                    'status2' => 1,
                    'status1' => 0
                ]);
            $this->info("Fixed {$fixed1} records (status1=1 -> status2=1)");
            
            $this->newLine();
            $this->info('Data fix completed successfully!');
        } else {
            $this->info('Preview complete. Run without --dry-run to apply changes.');
        }
        
        return 0;
    }
}
