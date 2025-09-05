<?php

namespace App\Jobs;

use App\Exports\ProductWipExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ProcessProductWipExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waktu maksimum (dalam detik) job dapat berjalan.
     * @var int
     */
    public $timeout = 300; // 5 Menit

    protected $export;
    protected $filePath;

    /**
     * Buat instance job baru.
     */
    public function __construct(ProductWipExport $export, string $filePath)
    {
        $this->export = $export;
        $this->filePath = $filePath;
    }

    /**
     * Jalankan job.
     */
    public function handle(): void
    {
        $filename = basename($this->filePath);
        try {
            // Jalankan proses ekspor dan simpan file ke disk publik
            $this->export->store($this->filePath, 'public');
            
            // Jika berhasil, perbarui status di cache
            Cache::put('export-status-wip-' . $filename, 'completed', 3600);
        } catch (Throwable $e) {
            // Jika ada error, perbarui status menjadi 'failed'
            Cache::put('export-status-wip-' . $filename, 'failed', 3600);
            throw $e;
        }
    }

    /**
     * Tangani kegagalan job.
     */
    public function failed(Throwable $exception): void
    {
        $filename = basename($this->filePath);
        // Pastikan status ditandai 'failed' jika ada timeout atau error tak terduga
        Cache::put('export-status-wip-' . $filename, 'failed', 3600);
    }
}
