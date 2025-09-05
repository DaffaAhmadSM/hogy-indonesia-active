<?php

namespace App\Jobs;

use App\Exports\ExportProductBB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\ProductReportExport;

class ProcessProductBBExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $export;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(ExportProductBB $export, string $filePath)
    {
        $this->export = $export;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Jalankan proses ekspor dan simpan file ke disk publik
        $this->export->store($this->filePath, 'public');
    }
}

