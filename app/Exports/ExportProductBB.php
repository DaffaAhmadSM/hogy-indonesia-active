<?php

namespace App\Exports;

use App\Models\ReportMutasiBarang;
use Illuminate\Bus\Queueable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;


class ExportProductBB implements FromQuery, WithMapping, ShouldQueue, WithEvents, WithCustomStartCell, WithTitle
{
    use Exportable, Queueable;

    protected $fromDate;
    protected $toDate;
    protected $warehouseId;
    protected $productType;
    protected $keyword;

    /**
     * Menerima semua filter dari controller
     */
    public function __construct(string $fromDate, string $toDate, string $warehouseId, array $productType, ?string $keyword)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->warehouseId = $warehouseId;
        $this->productType = $productType;
        $this->keyword = $keyword;
    }

    /**
     * Query using ReportMutasiBarang matching hxSearch logic
     */
    public function query()
    {
        $keyword = $this->keyword;
        $searchTerm = '%' . $keyword . '%';
        
        // Map productType array to reportType
        $reportType = '';
        if (in_array('BAHAN_BAKU_PENOLONG', $this->productType)) {
            $reportType = '04 Mutasi Bahan Baku';
        } elseif (in_array('MESIN_PERALATAN', $this->productType)) {
            $reportType = '05 Mutasi Mesin dan Peralatan';
        } elseif (in_array('BARANG_JADI', $this->productType)) {
            $reportType = '06 Mutasi Barang Jadi';
        } elseif (in_array('BARANG_REJECT_SCRAP', $this->productType)) {
            $reportType = ['07 Mutasi Barang Reject', '07 Mutasi Barang Scrap'];
        } else {
            $reportType = '04 Mutasi Bahan Baku';
        }

        $tableName = (new ReportMutasiBarang())->getTable();

        $query = ReportMutasiBarang::select([
                "KODEBARANG",
                "NAMABARANG",
                "SATUAN",
                "SALDOAWAL",
                "PEMASUKAN",
                "PENGELUARAN",
                "PENYESUAIAN",
                "SALDOBUKU",
                "STOCKOPNAME",
                "SELISIH"
            ])
            ->where("$tableName.REPORTTYPE", $reportType)
            ->orderBy("$tableName.KODEBARANG")
            ->whereBetween('TRANSDATE', [$this->fromDate, $this->toDate]);

        // Apply keyword search if provided
        if ($keyword) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('KODEBARANG', 'like', $searchTerm)
                    ->orWhere('NAMABARANG', 'like', $searchTerm)
                    ->orWhere('SATUAN', 'like', $searchTerm);
            });
        }

        return $query;
    }

    /**
     * Map each row for export
     */
    public function map($item): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        return [
            $rowNumber,
            $item->KODEBARANG,
            $item->NAMABARANG,
            $item->SATUAN,
            $item->SALDOAWAL,
            $item->PEMASUKAN,
            $item->PENGELUARAN,
            $item->PENYESUAIAN,
            $item->SALDOBUKU,
            $item->STOCKOPNAME,
            $item->SELISIH,
        ];
    }

    /**
     * Start cell for data
     */
    public function startCell(): string
    {
        return 'A9';
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        $typeLabel = '';
        if (in_array('BAHAN_BAKU_PENOLONG', $this->productType)) {
            $typeLabel = 'Bahan Baku';
        } elseif (in_array('MESIN_PERALATAN', $this->productType)) {
            $typeLabel = 'Mesin dan Peralatan';
        } elseif (in_array('BARANG_JADI', $this->productType)) {
            $typeLabel = 'Barang Jadi';
        } elseif (in_array('BARANG_REJECT_SCRAP', $this->productType)) {
            $typeLabel = 'Reject dan Scrap';
        } else {
            $typeLabel = 'Bahan Baku';
        }
        
        return 'Laporan ' . $typeLabel;
    }

    /**
     * Configure sheet events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
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
                $typeLabel = '';
                if (in_array('BAHAN_BAKU_PENOLONG', $this->productType)) {
                    $typeLabel = 'Bahan Baku';
                } elseif (in_array('MESIN_PERALATAN', $this->productType)) {
                    $typeLabel = 'Mesin dan Peralatan';
                } elseif (in_array('BARANG_JADI', $this->productType)) {
                    $typeLabel = 'Barang Jadi';
                } elseif (in_array('BARANG_REJECT_SCRAP', $this->productType)) {
                    $typeLabel = 'Reject dan Scrap';
                } else {
                    $typeLabel = 'Bahan Baku';
                }
                
                $sheet->setCellValue('A6', 'Laporan Pertanggungjawaban Barang ' . $typeLabel . ' WIP');
                $sheet->mergeCells('A6:F6');
                
                $sheet->setCellValue('A7', 'Period ' . $this->fromDate . ' to ' . $this->toDate);
                $sheet->mergeCells('A7:F7');
                
                // Table headers
                $sheet->setCellValue('A8', 'No');
                $sheet->setCellValue('B8', 'Kode barang');
                $sheet->setCellValue('C8', 'Nama barang');
                $sheet->setCellValue('D8', 'Satuan');
                $sheet->setCellValue('E8', 'Saldo awal');
                $sheet->setCellValue('F8', 'Pemasukan');
                $sheet->setCellValue('G8', 'Pengeluaran');
                $sheet->setCellValue('H8', 'Penyesuaian');
                $sheet->setCellValue('I8', 'Saldo buku');
                $sheet->setCellValue('J8', 'Stock opname');
                $sheet->setCellValue('K8', 'Selisih');
                $sheet->setCellValue('L8', 'Keterangan');
                
                // Style company header
                $sheet->getStyle('A1:A5')->getFont()->setBold(false)->setSize(9);
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A7')->getFont()->setBold(false)->setSize(9);
                
                // Header row styling
                $sheet->getStyle('A8:L8')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '9966CC'],
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
                
                // Auto-size columns
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Add borders to data rows
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 8) {
                    $sheet->getStyle('A9:L' . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}