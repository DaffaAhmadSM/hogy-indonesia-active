<?php

namespace App\Exports;

use App\Models\ReportPemasukan;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportEnvtInMain implements FromQuery, WithMapping, ShouldQueue, WithTitle, WithChunkReading, WithEvents
{

    use Exportable, Queueable;

    public $timeout = 1200; // 20 minutes

    protected $fromDate;
    protected $toDate;
    protected $keywords;
    private $rowNumber = 0;


    public function __construct(String $fromDate, String $toDate, $keywords)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->keywords = $keywords;
    }

    /**
     * Query for export data
     */
    public function query()
    {
        $keyword = $this->keywords;
        $fromDate = $this->fromDate;
        $toDate = $this->toDate;

        $tableName = (new ReportPemasukan)->getTable();
        $query = ReportPemasukan::selectRaw("CASE 
                WHEN BCTYPE = 9 THEN 'BC40'
                WHEN BCTYPE = 10 THEN 'BC27'
                WHEN BCTYPE = 11 THEN 'BC23'
                WHEN BCTYPE = 12 THEN 'BC262'
                WHEN BCTYPE = 13 THEN 'BC30'
                WHEN BCTYPE = 14 THEN 'BC25'
                WHEN BCTYPE = 15 THEN 'BC261'
                WHEN BCTYPE = 16 THEN 'BC27'
                WHEN BCTYPE = 17 THEN 'BC41'
                ELSE 'UNKNOWN'
            END as BC_CODE_NAME")
            ->addSelect([
                "NOMORDAFTAR",
                "TANGGALDAFTAR",
                "NOMORPENERIMAAN",
                "TANGGALPENERIMAAN",
                "PENGIRIM",
                "KODEBARANG",
                "NAMABARANG",
                "JUMLAH",
                "SATUAN",
                "NILAI",
            ])
            ->orderBy("$tableName.NOMORDAFTAR", "desc")
            ->orderBy("$tableName.KODEBARANG")
            ->whereBetween("$tableName.TANGGALDAFTAR", [$fromDate, $toDate]);

        if ($keyword != null) {
            $query->when($keyword, function ($query, $keyword) use ($tableName) {
                $query->where(function ($q) use ($keyword, $tableName) {
                    $q->where("$tableName.NOMORDAFTAR", 'like', "%$keyword%")
                        ->orWhere("$tableName.KODEBARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NAMABARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NOMORPENERIMAAN", 'like', "%$keyword%")
                        ->orWhere("$tableName.PENGIRIM", 'like', "%$keyword%");
                });
            });
        }

        return $query;
    }

    /**
     * Map each row for export
     */
    public function map($item): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $item->BC_CODE_NAME,
            $item->NOMORDAFTAR,
            $item->TANGGALDAFTAR,
            $item->NOMORPENERIMAAN,
            $item->TANGGALPENERIMAAN,
            $item->PENGIRIM,
            $item->KODEBARANG,
            $item->NAMABARANG,
            $item->JUMLAH,
            $item->SATUAN,
            $item->NILAI,
        ];
    }
    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Laporan Pemasukan';
    }

    /**
     * Chunk size for reading data
     */
    public function chunkSize(): int
    {
        return 1000;
    }

     public function batchSize(): int
    {
        return 1000;
    }

    /**
     * Configure sheet events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->insertNewRowBefore(1, 10);

                $lastRow = $sheet->getHighestRow();
                for ($i = 11; $i <= $lastRow; $i++) {
                    $sheet->setCellValue('A' . $i, $i - 10);
                }
                
                // Company header
                $sheet->setCellValue('A1', 'HOGY');
                $sheet->mergeCells('A1:F1');
                
                $sheet->setCellValue('A2', 'PT Hogy Indonesia');
                $sheet->mergeCells('A2:F2');
                
                $sheet->setCellValue('A3', 'MM 2100 Industrial Town, Blok M3-1');
                $sheet->mergeCells('A3:F3');
                
                $sheet->setCellValue('A4', 'Cikarang Barat, Bekasi 17520');
                $sheet->mergeCells('A4:F4');
                
                $sheet->setCellValue('A5', 'P: +62 21 8980165, F: +62 21 8980166, E: purchasing@hogy.co.id');
                $sheet->mergeCells('A5:F5');
                
                // Report title
                $sheet->setCellValue('A6', 'Laporan Pemasukan Barang Per Dokumen Pabean');
                $sheet->mergeCells('A6:F6');
                
                $sheet->setCellValue('A7', 'Period ' . $this->fromDate . ' to ' . $this->toDate);
                $sheet->mergeCells('A7:F7');
                
                // Table headers - Row 9 (first header row)
                $sheet->setCellValue('A9', 'No');
                $sheet->setCellValue('B9', 'Dokumen pabean');
                $sheet->setCellValue('E9', 'Bukti penerimaan barang');
                $sheet->setCellValue('G9', 'Pengirim barang');
                $sheet->setCellValue('H9', 'Kode barang');
                $sheet->setCellValue('I9', 'Nama barang');
                $sheet->setCellValue('J9', 'Jumlah');
                $sheet->setCellValue('K9', 'Satuan');
                $sheet->setCellValue('L9', 'Nilai');
                
                // Table headers - Row 10 (second header row)
                $sheet->setCellValue('B10', 'Jenis');
                $sheet->setCellValue('C10', 'Nomor');
                $sheet->setCellValue('D10', 'Tanggal');
                $sheet->setCellValue('E10', 'Nomor');
                $sheet->setCellValue('F10', 'Tanggal');
                
                // Style company header
                $sheet->getStyle('A1:A5')->getFont()->setBold(false)->setSize(9);
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A7')->getFont()->setBold(false)->setSize(9);
                
                // Header row styling (row 9-10)
                $sheet->getStyle('A9:L10')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C0504D'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                
                // Merge cells for headers
                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:D9');
                $sheet->mergeCells('E9:F9');
                $sheet->mergeCells('G9:G10');
                $sheet->mergeCells('H9:H10');
                $sheet->mergeCells('I9:I10');
                $sheet->mergeCells('J9:J10');
                $sheet->mergeCells('K9:K10');
                $sheet->mergeCells('L9:L10');

                // Auto-size columns
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Set minimum widths
                // $sheet->getColumnDimension('A')->setWidth(5);
                // $sheet->getColumnDimension('I')->setWidth(30);
                
                // Add borders to data rows
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 10) {
                    $sheet->getStyle('A11:L' . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Format Jumlah and Nilai columns with thousand separator and 2 decimals
                    $sheet->getStyle('J11:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('L11:L' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Add Grand Total row
                    $grandTotalRow = $lastRow + 1;
                    $sheet->setCellValue('I' . $grandTotalRow, 'GRAND TOTAL');
                    $sheet->setCellValue('J' . $grandTotalRow, '=SUM(J11:J' . $lastRow . ')');
                    $sheet->setCellValue('L' . $grandTotalRow, '=SUM(L11:L' . $lastRow . ')');
                    
                    // Style Grand Total row with header color
                    $sheet->getStyle('A' . $grandTotalRow . ':L' . $grandTotalRow)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'C0504D'],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Format Grand Total numbers with thousand separator and 2 decimals
                    $sheet->getStyle('J' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('L' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Merge cells for "GRAND TOTAL" label
                    $sheet->mergeCells('A' . $grandTotalRow . ':I' . $grandTotalRow);
                }
            },
        ];
    }
}
