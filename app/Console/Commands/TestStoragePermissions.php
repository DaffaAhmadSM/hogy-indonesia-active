<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestStoragePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:storage-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test storage write permissions for export functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=================================================');
        $this->info('  Storage Permission Diagnostic Tool');
        $this->info('=================================================');
        $this->newLine();

        // 1. Display current user and environment info
        $this->info('1. SYSTEM INFORMATION');
        $this->info('-------------------');
        $this->line('PHP User: ' . get_current_user());
        $this->line('PHP Version: ' . PHP_VERSION);
        $this->line('OS: ' . PHP_OS);
        
        if (function_exists('exec')) {
            $whoami = '';
            @exec('whoami 2>&1', $output, $return);
            if ($return === 0 && !empty($output)) {
                $whoami = implode("\n", $output);
            }
            $this->line('Process User: ' . ($whoami ?: 'Unable to determine (exec disabled or command failed)'));
        } else {
            $this->line('Process User: Unable to determine (exec function disabled)');
        }
        
        $this->newLine();

        // 2. Test storage paths
        $this->info('2. STORAGE PATHS');
        $this->info('-------------------');
        
        $paths = [
            'Storage Base' => storage_path(),
            'Storage App' => storage_path('app'),
            'Storage Public' => storage_path('app/public'),
            'Reports Directory' => storage_path('app/public/reports'),
            'Logs Directory' => storage_path('logs'),
        ];

        foreach ($paths as $label => $path) {
            $exists = file_exists($path);
            $writable = $exists && is_writable($path);
            $readable = $exists && is_readable($path);
            
            $this->line($label . ': ' . $path);
            $this->line('  Exists: ' . ($exists ? '✓ Yes' : '✗ No'));
            
            if ($exists) {
                $this->line('  Readable: ' . ($readable ? '✓ Yes' : '✗ No'));
                $this->line('  Writable: ' . ($writable ? '✓ Yes' : '✗ No'));
                
                // Get permissions
                $perms = fileperms($path);
                $this->line('  Permissions: ' . substr(sprintf('%o', $perms), -4));
            }
            $this->newLine();
        }

        // 3. Test creating reports directory if it doesn't exist
        $this->info('3. REPORTS DIRECTORY TEST');
        $this->info('-------------------');
        $reportsPath = storage_path('app/public/reports');
        
        if (!file_exists($reportsPath)) {
            $this->warn('Reports directory does not exist. Attempting to create...');
            try {
                if (!Storage::disk('public')->exists('reports')) {
                    Storage::disk('public')->makeDirectory('reports');
                    $this->info('✓ Successfully created reports directory');
                } else {
                    $this->info('✓ Reports directory already exists via Storage facade');
                }
            } catch (\Exception $e) {
                $this->error('✗ Failed to create reports directory: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('✓ Reports directory exists');
        }
        $this->newLine();

        // 4. Test file write operations
        $this->info('4. FILE WRITE TESTS');
        $this->info('-------------------');
        
        $testFileName = 'test-permissions-' . time() . '.txt';
        $testPath = 'reports/' . $testFileName;
        $testContent = 'Test content created at ' . date('Y-m-d H:i:s') . ' by user: ' . get_current_user();
        
        $allTestsPassed = true;

        // Test 1: Write file
        $this->line('Test 1: Writing test file...');
        try {
            Storage::disk('public')->put($testPath, $testContent);
            $this->info('  ✓ Successfully wrote file: ' . $testFileName);
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to write file: ' . $e->getMessage());
            $this->error('  Stack trace: ' . $e->getTraceAsString());
            $allTestsPassed = false;
        }

        if ($allTestsPassed) {
            // Test 2: Read file
            $this->line('Test 2: Reading test file...');
            try {
                $content = Storage::disk('public')->get($testPath);
                if ($content === $testContent) {
                    $this->info('  ✓ Successfully read file with correct content');
                } else {
                    $this->warn('  ! File read but content mismatch');
                    $allTestsPassed = false;
                }
            } catch (\Exception $e) {
                $this->error('  ✗ Failed to read file: ' . $e->getMessage());
                $allTestsPassed = false;
            }

            // Test 3: Check file exists
            $this->line('Test 3: Checking file exists...');
            if (Storage::disk('public')->exists($testPath)) {
                $this->info('  ✓ File exists check passed');
            } else {
                $this->error('  ✗ File exists check failed');
                $allTestsPassed = false;
            }

            // Test 4: Get file size
            $this->line('Test 4: Getting file size...');
            try {
                $size = Storage::disk('public')->size($testPath);
                $this->info('  ✓ File size: ' . $size . ' bytes');
            } catch (\Exception $e) {
                $this->error('  ✗ Failed to get file size: ' . $e->getMessage());
                $allTestsPassed = false;
            }

            // Test 5: Delete file
            $this->line('Test 5: Deleting test file...');
            try {
                Storage::disk('public')->delete($testPath);
                $this->info('  ✓ Successfully deleted file');
            } catch (\Exception $e) {
                $this->error('  ✗ Failed to delete file: ' . $e->getMessage());
                $allTestsPassed = false;
            }

            // Test 6: Verify deletion
            $this->line('Test 6: Verifying file deletion...');
            if (!Storage::disk('public')->exists($testPath)) {
                $this->info('  ✓ File deletion verified');
            } else {
                $this->error('  ✗ File still exists after deletion');
                $allTestsPassed = false;
            }
        }

        $this->newLine();

        // 5. Test Excel export simulation
        $this->info('5. EXCEL EXPORT SIMULATION');
        $this->info('-------------------');
        
        $excelTestFile = 'reports/test-export-' . time() . '.xlsx';
        $this->line('Simulating Excel export to: ' . $excelTestFile);
        
        try {
            // Create a simple test file to simulate Excel export
            $dummyContent = 'This simulates an Excel export file';
            Storage::disk('public')->put($excelTestFile, $dummyContent);
            $this->info('  ✓ Successfully created simulated Excel file');
            
            // Clean up
            Storage::disk('public')->delete($excelTestFile);
            $this->info('  ✓ Successfully cleaned up test file');
        } catch (\Exception $e) {
            $this->error('  ✗ Excel export simulation failed: ' . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->newLine();

        // 6. Final summary
        $this->info('=================================================');
        if ($allTestsPassed) {
            $this->info('✓ ALL TESTS PASSED!');
            $this->info('Storage permissions are correctly configured.');
            $this->info('Export functionality should work properly.');
        } else {
            $this->error('✗ SOME TESTS FAILED!');
            $this->error('There are permission issues that need to be resolved.');
            $this->newLine();
            $this->warn('RECOMMENDED ACTIONS FOR WINDOWS SERVER:');
            $this->warn('1. Run this command as Administrator');
            $this->warn('2. Grant permissions using: icacls storage /grant IIS_IUSRS:(OI)(CI)F /T');
            $this->warn('3. Grant permissions using: icacls storage /grant "NETWORK SERVICE":(OI)(CI)F /T');
            $this->warn('4. Or grant to Everyone for testing: icacls storage /grant Everyone:(OI)(CI)F /T');
            $this->warn('5. Restart your queue worker after fixing permissions');
        }
        $this->info('=================================================');

        return $allTestsPassed ? 0 : 1;
    }
}