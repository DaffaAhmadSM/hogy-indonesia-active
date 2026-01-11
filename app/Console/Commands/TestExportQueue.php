<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use App\Exports\ExportEnvtInMain;
use App\Exports\ExportInvtOutMain;

class TestExportQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:export-queue {--type=in : Export type (in or out)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test export queue functionality to diagnose export issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('=================================================');
        $this->info('  Export Queue Diagnostic Tool');
        $this->info('=================================================');
        $this->newLine();

        // 1. System info
        $this->info('1. CURRENT ENVIRONMENT');
        $this->info('-------------------');
        $this->line('User: ' . get_current_user());
        $this->line('Date: ' . date('Y-m-d H:i:s'));
        $this->line('Export Type: ' . ($type === 'out' ? 'Inventory Out' : 'Inventory In'));
        $this->newLine();

        // 2. Setup test parameters
        $fromDate = Carbon::now()->subDays(7)->toDateString();
        $toDate = Carbon::now()->toDateString();
        $keywords = null;

        $this->info('2. TEST PARAMETERS');
        $this->info('-------------------');
        $this->line('From Date: ' . $fromDate);
        $this->line('To Date: ' . $toDate);
        $this->line('Keywords: ' . ($keywords ?? 'null'));
        $this->newLine();

        // 3. Setup file path
        $fileName = 'TEST_Export_' . ($type === 'out' ? 'Out' : 'In') . '_' . time() . '.xlsx';
        $path = 'reports/';
        $fullPathName = $path . $fileName;

        $this->info('3. FILE PATH SETUP');
        $this->info('-------------------');
        $this->line('File Name: ' . $fileName);
        $this->line('Path: ' . $path);
        $this->line('Full Path: ' . $fullPathName);
        $this->line('Absolute Path: ' . storage_path('app/public/' . $fullPathName));
        $this->newLine();

        // 4. Check directory
        $this->info('4. DIRECTORY CHECK');
        $this->info('-------------------');
        
        if (!Storage::disk('public')->exists($path)) {
            $this->warn('Reports directory does not exist. Creating...');
            try {
                Storage::disk('public')->makeDirectory($path);
                $this->info('✓ Directory created successfully');
            } catch (\Exception $e) {
                $this->error('✗ Failed to create directory: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('✓ Reports directory exists');
        }
        $this->newLine();

        // 5. Delete existing test file if present
        $this->info('5. CLEANUP EXISTING FILES');
        $this->info('-------------------');
        
        if (Storage::disk('public')->exists($fullPathName)) {
            $this->warn('Test file already exists. Deleting...');
            try {
                Storage::disk('public')->delete($fullPathName);
                $this->info('✓ Old file deleted');
            } catch (\Exception $e) {
                $this->error('✗ Failed to delete old file: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('✓ No existing file to clean up');
        }
        $this->newLine();

        // 6. Test direct export (synchronous)
        $this->info('6. SYNCHRONOUS EXPORT TEST');
        $this->info('-------------------');
        $this->line('Testing direct export without queue...');
        
        try {
            if ($type === 'out') {
                $export = new ExportInvtOutMain($fromDate, $toDate, $keywords);
            } else {
                $export = new ExportEnvtInMain($fromDate, $toDate, $keywords);
            }

            $this->line('Creating export instance...');
            $this->info('✓ Export instance created');

            $this->line('Storing file directly (this may take a moment)...');
            $export->store($fullPathName, 'public');
            
            $this->info('✓ File stored successfully!');
            
            // Check if file exists
            if (Storage::disk('public')->exists($fullPathName)) {
                $size = Storage::disk('public')->size($fullPathName);
                $this->info('✓ File exists and is readable');
                $this->line('  File size: ' . number_format($size) . ' bytes (' . number_format($size / 1024, 2) . ' KB)');
                
                if ($size > 0) {
                    $this->info('✓ File has content (not empty)');
                } else {
                    $this->warn('! File is empty (0 bytes)');
                }
            } else {
                $this->error('✗ File was not created!');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('✗ Synchronous export failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
        
        $this->newLine();

        // 7. Test queued export
        $this->info('7. QUEUED EXPORT TEST');
        $this->info('-------------------');
        
        // Delete the sync test file first
        if (Storage::disk('public')->exists($fullPathName)) {
            Storage::disk('public')->delete($fullPathName);
        }
        
        $queueFileName = 'TEST_Queue_Export_' . ($type === 'out' ? 'Out' : 'In') . '_' . time() . '.xlsx';
        $queueFullPath = $path . $queueFileName;
        
        $this->line('Queue file: ' . $queueFileName);
        $this->line('Testing queued export...');
        
        try {
            if ($type === 'out') {
                $export = new ExportInvtOutMain($fromDate, $toDate, $keywords);
            } else {
                $export = new ExportEnvtInMain($fromDate, $toDate, $keywords);
            }
            
            $pendingDispatch = $export->queue($queueFullPath, 'public');
            
            $this->info('✓ Export job queued successfully');
            $this->warn('! Job is now in queue. Run "php artisan queue:work" to process it.');
            $this->line('  File will be created at: ' . storage_path('app/public/' . $queueFullPath));
            
        } catch (\Exception $e) {
            $this->error('✗ Failed to queue export!');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        $this->newLine();

        // 8. Cleanup test files
        $this->info('8. CLEANUP');
        $this->info('-------------------');
        
        $confirm = $this->confirm('Do you want to delete the test files created?', true);
        
        if ($confirm) {
            $deleted = 0;
            
            if (Storage::disk('public')->exists($fullPathName)) {
                Storage::disk('public')->delete($fullPathName);
                $this->line('✓ Deleted: ' . $fileName);
                $deleted++;
            }
            
            if (Storage::disk('public')->exists($queueFullPath)) {
                Storage::disk('public')->delete($queueFullPath);
                $this->line('✓ Deleted: ' . $queueFileName);
                $deleted++;
            }
            
            if ($deleted > 0) {
                $this->info('✓ Cleaned up ' . $deleted . ' test file(s)');
            } else {
                $this->line('No test files to clean up');
            }
        } else {
            $this->info('Test files kept for inspection');
        }
        
        $this->newLine();

        // 9. Summary
        $this->info('=================================================');
        $this->info('✓ DIAGNOSTIC COMPLETED SUCCESSFULLY!');
        $this->info('=================================================');
        $this->newLine();
        
        $this->info('SUMMARY:');
        $this->line('• Synchronous export works: YES');
        $this->line('• Queue dispatch works: YES');
        $this->line('• Storage permissions: OK');
        $this->newLine();
        
        $this->warn('NEXT STEPS:');
        $this->line('1. Make sure queue worker is running: php artisan queue:work');
        $this->line('2. Check logs at: storage/logs/laravel.log');
        $this->line('3. Monitor queue jobs: php artisan queue:listen --verbose');
        $this->newLine();
        
        $this->info('If queued export fails but sync works, the issue is likely:');
        $this->line('• Queue worker running under different user account');
        $this->line('• Queue worker lacks write permissions');
        $this->line('• Memory/timeout issues in queue worker');
        
        return 0;
    }
}